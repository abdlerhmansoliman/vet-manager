<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\DoseAdministration;
use App\Models\InventoryProduct;
use App\Models\Visit;
use App\Services\Inventory\BatchDeductionService;
use App\Services\Media\MediaService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Show form to initiate a new clinical visit encounter under a case.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $caseId = (string) $request->input('case_id');
        $case = ClinicalCase::query()->with(['patient.client'])->find($caseId);

        if (! $case instanceof ClinicalCase) {
            return redirect()->route('portal.patients.index')->withErrors([
                'case' => __('Please select a clinical case before starting a visit.'),
            ]);
        }

        return Inertia::render('Visits/Create', [
            'case' => $case,
        ]);
    }

    /**
     * Store newly created clinical visit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_id' => ['required', 'exists:cases,id'],
            'title' => ['required', 'string', 'max:255'],
            'visit_type' => ['required', 'string', 'max:100'],
            'reason_for_visit' => ['nullable', 'string', 'max:1000'],
            'visit_date' => ['nullable', 'date'],
        ]);

        $case = ClinicalCase::query()->findOrFail($validated['case_id']);

        $visit = Visit::query()->create([
            'case_id' => $case->id,
            'title' => $validated['title'],
            'visit_type' => $validated['visit_type'],
            'status' => 'in_progress',
            'is_signed' => false,
            'reason_for_visit' => $validated['reason_for_visit'] ?? $case->chief_complaint,
            'visit_date' => $validated['visit_date'] ?? now(),
        ]);

        return redirect()->route('portal.visits.show', $visit->id)->with(
            'success',
            __('Visit ":title" started successfully.', ['title' => $visit->title])
        );
    }

    /**
     * Level 3: Visit Detail Screen.
     * The EXCLUSIVE workspace for SOAP examinations, vitals, eMAR dosing, and MinIO S3 media attachments.
     */
    public function show(Visit $visit, MediaService $mediaService): Response
    {
        $visit->load([
            'case.patient.client',
            'case.primaryVeterinarian',
            'progressNotes.veterinarian',
        ]);

        $latestNote = $visit->progressNotes->first();

        // Dose administrations for this case
        $doses = DoseAdministration::query()
            ->with(['product', 'administeredBy'])
            ->where('case_id', $visit->case_id)
            ->latest()
            ->get();

        // Pharmacy products with stock available for dosing
        $pharmacyProducts = InventoryProduct::query()
            ->with('batches')
            ->orderBy('name')
            ->get()
            ->filter(fn (InventoryProduct $p): bool => (float) $p->calculateTotalStock() > 0)
            ->map(fn (InventoryProduct $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'base_unit' => is_object($p->base_unit) ? $p->base_unit->value : (string) $p->base_unit,
                'total_stock' => $p->calculateTotalStock(),
            ])
            ->values();

        return Inertia::render('Visits/Show', [
            'visit' => $visit,
            'case' => $visit->case,
            'patient' => $visit->case->patient,
            'latestNote' => $latestNote,
            'administeredDoses' => $doses,
            'mediaImages' => $mediaService->getImagesForModel($visit),
            'products' => $pharmacyProducts,
        ]);
    }

    /**
     * Record or update SOAP clinical examination note under Level 3 Visit.
     */
    public function storeSoap(Visit $visit, Request $request): RedirectResponse
    {
        if ($visit->is_signed) {
            return back()->withErrors(['soap' => __('Cannot modify SOAP examination: Visit is locked and signed.')]);
        }

        $validated = $request->validate([
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'temperature_c' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'heart_rate_bpm' => ['nullable', 'integer', 'min:10', 'max:400'],
            'respiratory_rate' => ['nullable', 'integer', 'min:2', 'max:200'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'systolic_bp' => ['nullable', 'integer', 'min:30', 'max:300'],
            'diastolic_bp' => ['nullable', 'integer', 'min:20', 'max:200'],
        ]);

        $existingNote = $visit->progressNotes()->first();

        $noteData = [
            'clinical_case_id' => $visit->case_id,
            'visit_id' => $visit->id,
            'vet_id' => Auth::guard('web')->id(),
            'subjective_notes' => $validated['subjective'] ?? null,
            'objective_notes' => $validated['objective'] ?? null,
            'assessment_notes' => $validated['assessment'] ?? null,
            'plan_notes' => $validated['plan'] ?? null,
            'temperature_c' => $validated['temperature_c'] ?? null,
            'heart_rate_bpm' => $validated['heart_rate_bpm'] ?? null,
            'respiratory_rate' => $validated['respiratory_rate'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'systolic_bp' => $validated['systolic_bp'] ?? null,
            'diastolic_bp' => $validated['diastolic_bp'] ?? null,
        ];

        if ($existingNote instanceof ClinicalProgressNote) {
            $existingNote->update($noteData);
        } else {
            ClinicalProgressNote::query()->create($noteData);
        }

        // Update patient weight if entered
        if (isset($validated['weight_kg'])) {
            $visit->case->patient->update([
                'current_weight_kg' => (string) $validated['weight_kg'],
                'weight_recorded_at' => now(),
            ]);
        }

        return back()->with('success', __('SOAP Clinical Examination saved successfully.'));
    }

    /**
     * Administer medication dose and deduct stock via BatchDeductionService under Level 3 Visit.
     */
    public function administerDose(
        Visit $visit,
        Request $request,
        BatchDeductionService $deductionService
    ): RedirectResponse {
        if ($visit->is_signed) {
            return back()->withErrors(['dose' => __('Cannot administer medication: Visit is locked and signed.')]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:inventory_products,id'],
            'dose_amount_base_units' => ['required', 'numeric', 'gt:0'],
            'route_administered' => ['nullable', 'string', 'max:50'],
            'clinical_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $productId = (string) $validated['product_id'];
        $doseAmount = number_format((float) $validated['dose_amount_base_units'], 4, '.', '');
        $actor = Auth::guard('web')->user();

        DB::connection('tenant')->transaction(function () use (
            $visit,
            $actor,
            $productId,
            $doseAmount,
            $validated,
            $deductionService
        ): void {
            $product = InventoryProduct::query()->findOrFail($productId);

            // 1. Log DoseAdministration record
            $dose = DoseAdministration::query()->create([
                'case_id' => $visit->case_id,
                'patient_id' => $visit->case->patient_id,
                'product_id' => $product->id,
                'administered_by_user_id' => $actor->id,
                'dose_amount_base_units' => $doseAmount,
                'route_administered' => $validated['route_administered'] ?? null,
                'clinical_notes' => $validated['clinical_notes'] ?? null,
            ]);

            // 2. Perform atomic batch FEFO deduction
            $deductionService->deduct(
                product: $product,
                doseAmountBaseUnits: $doseAmount,
                actor: $actor,
                movementType: StockMovementType::DoseAdministration,
                reason: "eMAR dose administered during visit: {$visit->title}",
                idempotencyKey: (string) Str::uuid()
            );
        });

        return back()->with('success', __('Medication dose administered and deducted from batch inventory.'));
    }

    /**
     * Upload diagnostic media (radiograph, ultrasound, etc.) to MinIO S3 via MediaService under Level 3 Visit.
     */
    public function uploadMedia(
        Visit $visit,
        Request $request,
        MediaService $mediaService
    ): RedirectResponse {
        if ($visit->is_signed) {
            return back()->withErrors(['media' => __('Cannot upload media: Visit is locked and signed.')]);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:20480'], // max 20MB
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $file = $request->file('image');
        $title = $request->input('title') ?: $file->getClientOriginalName();

        $image = $mediaService->uploadForVisit(
            visit: $visit,
            file: $file,
            title: (string) $title,
        );

        return back()->with('success', __('Diagnostic radiograph attached to visit successfully.'));
    }

    /**
     * Digitally sign and permanently lock the clinical visit record.
     */
    public function sign(Visit $visit): RedirectResponse
    {
        if ($visit->is_signed) {
            return back()->withErrors(['sign' => __('Visit is already signed and legally locked.')]);
        }

        $actor = Auth::guard('web')->user();
        $now = now();

        DB::connection('tenant')->transaction(function () use ($visit, $actor, $now): void {
            $visit->update([
                'is_signed' => true,
                'signed_at' => $now,
                'signed_by' => $actor->name,
                'status' => 'completed',
            ]);

            // Sign all progress notes under this visit
            foreach ($visit->progressNotes as $note) {
                if (! $note->is_signed) {
                    $note->update([
                        'is_signed' => true,
                        'signed_at' => $now,
                    ]);
                }
            }
        });

        return back()->with('success', __('Clinical visit successfully signed and permanently locked.'));
    }

    /**
     * Show form to edit visit metadata before signing.
     */
    public function edit(Visit $visit): Response|RedirectResponse
    {
        if ($visit->is_signed) {
            return redirect()->route('portal.visits.show', $visit->id)->withErrors([
                'edit' => __('Cannot edit a signed and locked clinical visit.'),
            ]);
        }

        return Inertia::render('Visits/Edit', [
            'visit' => $visit,
        ]);
    }

    /**
     * Update visit metadata before signing.
     */
    public function update(Visit $visit, Request $request): RedirectResponse
    {
        if ($visit->is_signed) {
            return redirect()->route('portal.visits.show', $visit->id)->withErrors([
                'edit' => __('Cannot edit a signed and locked clinical visit.'),
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'visit_type' => ['required', 'string', 'max:100'],
            'reason_for_visit' => ['nullable', 'string', 'max:1000'],
            'visit_date' => ['required', 'date'],
        ]);

        $visit->update($validated);

        return redirect()->route('portal.visits.show', $visit->id)->with(
            'success',
            __('Visit metadata updated successfully.')
        );
    }
}
