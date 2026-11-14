<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\DiagnosisStatus;
use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClinicalCaseController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Show form to open a new clinical case under a patient.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $patientId = (string) $request->input('patient_id');
        $patient = Patient::query()->with('client')->find($patientId);

        if (! $patient instanceof Patient) {
            return redirect()->route('portal.patients.index')->withErrors([
                'patient' => __('Please select a patient before opening a case.'),
            ]);
        }

        $vets = User::query()
            ->where('is_active', true)
            ->whereIn('role', [StaffRole::Owner, StaffRole::Doctor, StaffRole::HeadVet, StaffRole::FieldDoctor])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return Inertia::render('Cases/Create', [
            'patient' => $patient,
            'vets' => $vets,
            'priorities' => ['routine', 'urgent', 'emergency_critical'],
            'caseTypes' => ['outpatient', 'inpatient', 'field_farm_visit'],
        ]);
    }

    /**
     * Store new clinical case in tenant database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'chief_complaint' => ['required', 'string', 'max:1000'],
            'priority' => ['required', Rule::enum(CasePriority::class)],
            'case_type' => ['required', Rule::enum(CaseType::class)],
            'primary_veterinarian_id' => ['nullable', 'exists:users,id'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:255'],
        ]);

        $patient = Patient::query()->findOrFail($validated['patient_id']);

        // Generate systematic clinical case number
        $year = date('Y');
        $countThisYear = ClinicalCase::query()->whereYear('created_at', (int) $year)->count() + 1;
        $caseNumber = sprintf('CAS-%s-%04d', $year, $countThisYear);

        $case = ClinicalCase::query()->create([
            'case_number' => $caseNumber,
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $validated['primary_veterinarian_id'] ?? Auth::guard('web')->id(),
            'case_type' => $validated['case_type'],
            'status' => CaseStatus::Open,
            'priority' => $validated['priority'],
            'chief_complaint' => $validated['chief_complaint'],
            'provisional_diagnosis' => $validated['provisional_diagnosis'] ?? null,
            'admitted_at' => now(),
        ]);

        if (! empty($validated['provisional_diagnosis'])) {
            Diagnosis::query()->create([
                'case_id' => $case->id,
                'patient_id' => $patient->id,
                'user_id' => $case->primary_veterinarian_id,
                'diagnosis' => $validated['provisional_diagnosis'],
                'status' => DiagnosisStatus::Tentative,
                'diagnosed_at' => now(),
            ]);
        }

        return redirect()->route('portal.cases.show', $case->id)->with(
            'success',
            __('Clinical Case :number opened for :patient.', ['number' => $case->case_number, 'patient' => $patient->name_or_tag])
        );
    }

    /**
     * Level 2: Case Detail Screen.
     * Problem umbrella. Displays case details and timeline of visits as compact VisitCard tiles.
     * Strictly ZERO media galleries allowed here!
     */
    public function show(ClinicalCase $case): Response
    {
        $case->load([
            'patient.client',
            'primaryVeterinarian',
            'diagnoses',
            'visits' => fn ($q) => $q->withCount('progressNotes')->orderByDesc('visit_date'),
        ]);

        return Inertia::render('Cases/Show', [
            'case' => $case,
            'patient' => $case->patient,
            'visits' => $case->visits,
            'diagnoses' => $case->diagnoses,
        ]);
    }

    /**
     * Show form to edit case details, priority, and diagnoses.
     */
    public function edit(ClinicalCase $case): Response
    {
        $case->load(['patient', 'primaryVeterinarian']);

        $vets = User::query()
            ->where('is_active', true)
            ->whereIn('role', [StaffRole::Owner, StaffRole::Doctor, StaffRole::HeadVet, StaffRole::FieldDoctor])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return Inertia::render('Cases/Edit', [
            'case' => $case,
            'patient' => $case->patient,
            'vets' => $vets,
            'priorities' => ['routine', 'urgent', 'emergency_critical'],
            'caseTypes' => ['outpatient', 'inpatient', 'field_farm_visit'],
            'statuses' => ['open', 'under_observation', 'critical_care', 'discharged', 'closed'],
        ]);
    }

    /**
     * Update case details, diagnoses, triage priority, and status.
     */
    public function update(ClinicalCase $case, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chief_complaint' => ['required', 'string', 'max:1000'],
            'priority' => ['required', Rule::enum(CasePriority::class)],
            'case_type' => ['required', Rule::enum(CaseType::class)],
            'status' => ['required', Rule::enum(CaseStatus::class)],
            'primary_veterinarian_id' => ['nullable', 'exists:users,id'],
            'provisional_diagnosis' => ['nullable', 'string', 'max:255'],
            'final_diagnosis' => ['nullable', 'string', 'max:255'],
        ]);

        $case->update([
            'chief_complaint' => $validated['chief_complaint'],
            'priority' => $validated['priority'],
            'case_type' => $validated['case_type'],
            'status' => $validated['status'],
            'primary_veterinarian_id' => $validated['primary_veterinarian_id'] ?? $case->primary_veterinarian_id,
            'provisional_diagnosis' => $validated['provisional_diagnosis'] ?? null,
            'final_diagnosis' => $validated['final_diagnosis'] ?? null,
            'discharged_at' => in_array($validated['status'], [CaseStatus::Discharged->value, CaseStatus::Closed->value], true) ? now() : null,
        ]);

        // If final diagnosis added and not in table, add diagnosis record
        if (! empty($validated['final_diagnosis'])) {
            $alreadyExists = Diagnosis::query()
                ->where('case_id', $case->id)
                ->where('diagnosis', $validated['final_diagnosis'])
                ->exists();

            if (! $alreadyExists) {
                Diagnosis::query()->create([
                    'case_id' => $case->id,
                    'patient_id' => $case->patient_id,
                    'user_id' => $case->primary_veterinarian_id,
                    'diagnosis' => $validated['final_diagnosis'],
                    'status' => DiagnosisStatus::Active,
                    'diagnosed_at' => now(),
                ]);
            }
        }

        return redirect()->route('portal.cases.show', $case->id)->with(
            'success',
            __('Clinical Case :number updated successfully.', ['number' => $case->case_number])
        );
    }
}
