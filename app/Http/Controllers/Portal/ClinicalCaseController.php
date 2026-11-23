<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Cases\OpenCaseAction;
use App\Actions\Cases\UpdateCaseAction;
use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\OpenCaseRequest;
use App\Http\Requests\Cases\UpdateCaseRequest;
use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function store(OpenCaseRequest $request, OpenCaseAction $action): RedirectResponse
    {
        $case = $action->execute($request->toDTO());
        $case->loadMissing('patient');

        return redirect()->route('portal.cases.show', $case->id)->with(
            'success',
            __('Clinical Case :number opened for :patient.', [
                'number' => $case->case_number,
                'patient' => $case->patient?->name_or_tag ?? 'Patient',
            ])
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
    public function update(ClinicalCase $case, UpdateCaseRequest $request, UpdateCaseAction $action): RedirectResponse
    {
        $updated = $action->execute($case, $request->toDTO());

        return redirect()->route('portal.cases.show', $updated->id)->with(
            'success',
            __('Clinical Case :number updated successfully.', ['number' => $updated->case_number])
        );
    }
}
