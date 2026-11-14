<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\PatientStatus;
use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display clinic practitioner operations dashboard.
     */
    public function index(Request $request): Response
    {
        // Clinic metrics
        $activePatientsCount = Patient::query()
            ->where('status', PatientStatus::Active)
            ->count();

        $activeCasesQuery = ClinicalCase::query()
            ->whereIn('status', [
                CaseStatus::Open,
                CaseStatus::UnderObservation,
                CaseStatus::CriticalCare,
            ]);

        $activeCasesCount = (clone $activeCasesQuery)->count();

        $criticalCasesCount = ClinicalCase::query()
            ->where('priority', CasePriority::EmergencyCritical)
            ->whereIn('status', [
                CaseStatus::Open,
                CaseStatus::UnderObservation,
                CaseStatus::CriticalCare,
            ])
            ->count();

        // Recent active cases for triage board
        $recentCases = ClinicalCase::query()
            ->with(['patient.client', 'primaryVeterinarian'])
            ->whereIn('status', [
                CaseStatus::Open,
                CaseStatus::UnderObservation,
                CaseStatus::CriticalCare,
            ])
            ->orderByRaw("FIELD(priority, 'emergency_critical', 'urgent', 'routine')")
            ->latest('updated_at')
            ->take(8)
            ->get();

        // Low stock products alert
        $lowStockProducts = InventoryProduct::query()
            ->with('batches')
            ->get()
            ->filter(function (InventoryProduct $product): bool {
                $total = (float) $product->calculateTotalStock();
                $threshold = (float) $product->reorder_threshold;

                return $total <= $threshold && $threshold > 0;
            })
            ->take(5);

        return Inertia::render('Dashboard', [
            'stats' => [
                'activePatients' => $activePatientsCount,
                'openCases' => $activeCasesCount,
                'criticalCases' => $criticalCasesCount,
                'lowStockAlerts' => $lowStockProducts->count(),
            ],
            'triageCases' => $recentCases->map(fn (ClinicalCase $c): array => [
                'id' => $c->id,
                'case_number' => $c->case_number,
                'priority' => is_object($c->priority) ? $c->priority->value : (string) $c->priority,
                'status' => is_object($c->status) ? $c->status->value : (string) $c->status,
                'chief_complaint' => $c->chief_complaint,
                'patient' => $c->patient ? [
                    'id' => $c->patient->id,
                    'name_or_tag' => $c->patient->name_or_tag,
                    'species' => $c->patient->species,
                    'client' => $c->patient->client ? ['name' => $c->patient->client->name] : null,
                ] : null,
                'primary_veterinarian' => $c->primaryVeterinarian ? [
                    'name' => $c->primaryVeterinarian->name,
                ] : null,
            ])->values()->all(),
            'lowStockProducts' => $lowStockProducts->map(fn (InventoryProduct $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'base_unit' => is_object($p->base_unit) ? $p->base_unit->value : (string) $p->base_unit,
                'current_stock' => $p->calculateTotalStock(),
                'reorder_threshold' => $p->reorder_threshold,
            ])->values()->all(),
        ]);
    }
}
