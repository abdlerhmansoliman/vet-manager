<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Patient\CreatePatientAction;
use App\Actions\Patient\UpdatePatientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\CreatePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Client;
use App\Models\Patient;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $stats = Patient::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN species = 'Canine' THEN 1 ELSE 0 END) as canine")
            ->selectRaw("SUM(CASE WHEN species = 'Feline' THEN 1 ELSE 0 END) as feline")
            ->selectRaw("SUM(CASE WHEN species = 'Equine' THEN 1 ELSE 0 END) as equine")
            ->selectRaw("SUM(CASE WHEN species = 'Bovine' THEN 1 ELSE 0 END) as bovine")
            ->selectRaw("SUM(CASE WHEN species = 'Ovine' THEN 1 ELSE 0 END) as ovine")
            ->first();

        $speciesStats = [
            'total' => (int) ($stats->total ?? 0),
            'canine' => (int) ($stats->canine ?? 0),
            'feline' => (int) ($stats->feline ?? 0),
            'equine' => (int) ($stats->equine ?? 0),
            'bovine' => (int) ($stats->bovine ?? 0),
            'ovine' => (int) ($stats->ovine ?? 0),
            'other' => (int) max(0, (int) ($stats->total ?? 0) - (
                (int) ($stats->canine ?? 0) +
                (int) ($stats->feline ?? 0) +
                (int) ($stats->equine ?? 0) +
                (int) ($stats->bovine ?? 0) +
                (int) ($stats->ovine ?? 0)
            )),
        ];

        $formattedPatients = collect($patients->items())->map(fn (Patient $p): array => [
            'id' => $p->id,
            'name_or_tag' => $p->name_or_tag,
            'species' => $p->species,
            'breed' => $p->breed,
            'gender' => is_object($p->gender) ? $p->gender->value : (string) $p->gender,
            'current_weight_kg' => $p->current_weight_kg,
            'microchip_number' => $p->microchip_number,
            'ear_tag_id' => $p->ear_tag_id,
            'status' => is_object($p->status) ? $p->status->value : (string) $p->status,
            'allergies' => $p->allergies ?? [],
            'client' => $p->client ? [
                'id' => $p->client->id,
                'name' => $p->client->name,
                'client_type' => $p->client->client_type,
            ] : null,
            'cases_count' => $p->cases->count(),
        ])->all();

        return Inertia::render('Patients/Index', [
            'patients' => $formattedPatients,
            'species_stats' => $speciesStats,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'species' => (string) $request->input('species', ''),
                'status' => (string) $request->input('status', ''),
            ],
        ]);
    }

    /**
     * Show registration form for a new patient.
     */
    public function create(Request $request): Response
    {
        $clients = Client::query()->orderBy('name')->get(['id', 'name', 'phone_primary']);

        return Inertia::render('Patients/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store newly registered patient in tenant database.
     */
    public function store(CreatePatientRequest $request, CreatePatientAction $action): RedirectResponse
    {
        $patient = $action->execute($request->toDTO());

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
        $clients = Client::query()->orderBy('name')->get(['id', 'name', 'phone_primary']);

        return Inertia::render('Patients/Edit', [
            'patient' => $patient->load('client'),
            'clients' => $clients,
        ]);
    }

    /**
     * Update patient demographics, weight, microchip, high-risk allergies.
     */
    public function update(Patient $patient, UpdatePatientRequest $request, UpdatePatientAction $action): RedirectResponse
    {
        $updated = $action->execute($patient, $request->toDTO());

        return redirect()->route('portal.patients.show', $updated->id)->with(
            'success',
            __('Patient record updated successfully.')
        );
    }
}
