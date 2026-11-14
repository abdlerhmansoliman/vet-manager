<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\PatientGender;
use App\Enums\PatientStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Patient;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display searchable patient registry.
     */
    public function index(Request $request): Response
    {
        $query = Patient::query()->with(['client', 'cases'])->latest();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name_or_tag', 'like', $search)
                    ->orWhere('microchip_number', 'like', $search)
                    ->orWhere('breed', 'like', $search)
                    ->orWhereHas('client', function ($cq) use ($search): void {
                        $cq->where('name', 'like', $search);
                    });
            });
        }

        if ($request->filled('species') && $request->input('species') !== 'all') {
            $query->where('species', (string) $request->input('species'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', (string) $request->input('status'));
        }

        $patients = $query->paginate(20)->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients->items(),
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'species' => (string) $request->input('species', ''),
            ],
        ]);
    }

    /**
     * Show registration form for a new patient.
     */
    public function create(Request $request): Response
    {
        $clients = Client::query()->orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Patients/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store newly registered patient in tenant database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name_or_tag' => ['required', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:150'],
            'gender' => ['required', Rule::enum(PatientGender::class)],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'microchip_number' => ['nullable', 'string', 'max:50'],
            'current_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'high_risk_allergies' => ['nullable', 'string'],
            'allergies' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $allergyInput = $validated['high_risk_allergies'] ?? $validated['allergies'] ?? null;
        $allergiesArray = null;
        if (! empty($allergyInput)) {
            $allergiesArray = is_array($allergyInput)
                ? array_values(array_filter($allergyInput))
                : array_values(array_filter(array_map('trim', explode(',', (string) $allergyInput))));
        }

        $patient = Patient::query()->create([
            'client_id' => $validated['client_id'],
            'name_or_tag' => $validated['name_or_tag'],
            'species' => $validated['species'],
            'breed' => $validated['breed'] ?? null,
            'gender' => $validated['gender'],
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'microchip_number' => $validated['microchip_number'] ?? null,
            'current_weight_kg' => isset($validated['current_weight_kg']) ? (string) $validated['current_weight_kg'] : null,
            'weight_recorded_at' => isset($validated['current_weight_kg']) ? now() : null,
            'allergies' => $allergiesArray,
            'notes' => $validated['notes'] ?? null,
            'status' => PatientStatus::Active,
        ]);

        return redirect()->route('portal.patients.show', $patient->id)->with(
            'success',
            __('Patient :name registered successfully.', ['name' => $patient->name_or_tag])
        );
    }

    /**
     * Level 1: Patient Profile Screen.
     * Strictly displays Clinical Cases ONLY (zero visits, zero SOAP, zero media).
     */
    public function show(Patient $patient): Response
    {
        $patient->load([
            'client',
            'cases' => fn ($q) => $q->withCount('visits')->with(['primaryVeterinarian', 'diagnoses'])->latest(),
        ]);

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
            'cases' => $patient->cases,
        ]);
    }

    /**
     * Show form to edit patient demographics and medical alert information.
     */
    public function edit(Patient $patient): Response
    {
        $clients = Client::query()->orderBy('name')->get(['id', 'name', 'phone']);

        return Inertia::render('Patients/Edit', [
            'patient' => $patient->load('client'),
            'clients' => $clients,
        ]);
    }

    /**
     * Update patient demographics, weight, microchip, high-risk allergies.
     */
    public function update(Patient $patient, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name_or_tag' => ['required', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:100'],
            'breed' => ['nullable', 'string', 'max:150'],
            'gender' => ['required', Rule::enum(PatientGender::class)],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'microchip_number' => ['nullable', 'string', 'max:50'],
            'current_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(PatientStatus::class)],
            'high_risk_allergies' => ['nullable', 'string'],
            'allergies' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $allergyInput = $validated['high_risk_allergies'] ?? $validated['allergies'] ?? null;
        $allergiesArray = null;
        if (! empty($allergyInput)) {
            $allergiesArray = is_array($allergyInput)
                ? array_values(array_filter($allergyInput))
                : array_values(array_filter(array_map('trim', explode(',', (string) $allergyInput))));
        }

        $updateData = [
            'client_id' => $validated['client_id'],
            'name_or_tag' => $validated['name_or_tag'],
            'species' => $validated['species'],
            'breed' => $validated['breed'] ?? null,
            'gender' => $validated['gender'],
            'age' => $validated['age'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'microchip_number' => $validated['microchip_number'] ?? null,
            'status' => $validated['status'],
            'allergies' => $allergiesArray,
            'notes' => $validated['notes'] ?? null,
        ];

        if (isset($validated['current_weight_kg']) && (string) $validated['current_weight_kg'] !== (string) $patient->current_weight_kg) {
            $updateData['current_weight_kg'] = (string) $validated['current_weight_kg'];
            $updateData['weight_recorded_at'] = now();
        }

        $patient->update($updateData);

        return redirect()->route('portal.patients.show', $patient->id)->with(
            'success',
            __('Patient record updated successfully.')
        );
    }
}
