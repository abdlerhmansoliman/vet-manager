<?php

declare(strict_types=1);

namespace App\Http\Controllers\Landlord;

use App\Actions\Landlord\GetLandlordMetricsAction;
use App\Actions\Landlord\MigrateAllTenantsAction;
use App\Actions\Landlord\MigrateTenantAction;
use App\Actions\Landlord\ProvisionTenantAction;
use App\Actions\Landlord\ToggleTenantStatusAction;
use App\Actions\Landlord\UpdateTenantAction;
use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\ProvisionTenantRequest;
use App\Http\Requests\Landlord\UpdateTenantRequest;
use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display the central platform landlord management dashboard.
     */
    public function index(Request $request, GetLandlordMetricsAction $metricsAction): View
    {
        $metrics = $metricsAction->execute();

        $query = Tenant::query()->withCount('staffLookups')->with('staffLookups');

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search)
                    ->orWhere('database_name', 'like', $search);
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = TenantStatus::tryFrom((string) $request->input('status'));
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('plan') && $request->input('plan') !== 'all') {
            $plan = TenantPlan::tryFrom((string) $request->input('plan'));
            if ($plan !== null) {
                $query->where('plan', $plan);
            }
        }

        $tenants = $query->latest()->paginate(10)->withQueryString();

        // Global staff lookups count and recent directory entries
        $globalStaffCount = TenantStaffLookup::query()->distinct('email')->count('email');

        return view('landlord.dashboard', [
            'tenants' => $tenants,
            'metrics' => $metrics,
            'currentPlan' => (string) $request->input('plan', 'all'),
            'currentStatus' => (string) $request->input('status', 'all'),
            'searchQuery' => (string) $request->input('search', ''),
            'globalStaffCount' => $globalStaffCount,
            'availablePlans' => TenantPlan::cases(),
            'availableStatuses' => TenantStatus::cases(),
        ]);
    }

    /**
     * Display the dedicated provision new clinic page.
     */
    public function create(): View
    {
        return view('landlord.create', [
            'availablePlans' => TenantPlan::cases(),
        ]);
    }

    /**
     * Provision a brand-new veterinary clinic tenant with isolated database.
     */
    public function store(ProvisionTenantRequest $request, ProvisionTenantAction $action): RedirectResponse
    {
        $tenant = $action->execute($request->toDTO());

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Clinic '{$tenant->name}' provisioned successfully! Database '{$tenant->database_name}' was created and migrated."
        );
    }

    /**
     * Retrieve tenant details and live clinical telemetry.
     * Returns JSON for API / testing or dedicated full Blade view for web users.
     */
    public function show(Tenant $tenant, Request $request): JsonResponse|View
    {
        $tenant->load('staffLookups');

        $clinicalMetrics = [
            'database_connected' => false,
            'patients_count' => 0,
            'cases_count' => 0,
            'visits_count' => 0,
            'species_count' => 0,
            'species_list' => [],
            'inventory_count' => 0,
            'images_count' => 0,
            'storage_used_bytes' => 0,
        ];

        try {
            $this->tenantManager->switch($tenant);
            $schema = DB::connection('tenant')->getSchemaBuilder();
            $clinicalMetrics['database_connected'] = true;

            if ($schema->hasTable('patients')) {
                $clinicalMetrics['patients_count'] = (int) DB::connection('tenant')->table('patients')->count();
            }

            if ($schema->hasTable('cases')) {
                $clinicalMetrics['cases_count'] = (int) DB::connection('tenant')->table('cases')->count();
            }

            if ($schema->hasTable('visits')) {
                $clinicalMetrics['visits_count'] = (int) DB::connection('tenant')->table('visits')->count();
            }

            if ($schema->hasTable('inventory_batches')) {
                $clinicalMetrics['inventory_count'] = (int) DB::connection('tenant')->table('inventory_batches')->count();
            }

            if ($schema->hasTable('images')) {
                $clinicalMetrics['images_count'] = (int) DB::connection('tenant')->table('images')->count();
                $actualStorageUsed = (int) DB::connection('tenant')->table('images')->sum('file_size_bytes');
                $clinicalMetrics['storage_used_bytes'] = $actualStorageUsed;

                if ($tenant->storage_used_bytes !== $actualStorageUsed) {
                    $tenant->update(['storage_used_bytes' => $actualStorageUsed]);
                    $tenant->refresh();
                }
            }

            if ($schema->hasTable('species')) {
                $speciesRows = DB::connection('tenant')->table('species')
                    ->where('is_active', true)
                    ->select('name', 'code', 'icon', 'is_system_default')
                    ->orderBy('name')
                    ->get();

                $clinicalMetrics['species_count'] = $speciesRows->count();
                $clinicalMetrics['species_list'] = $speciesRows->map(fn ($s) => [
                    'name' => $s->name,
                    'code' => $s->code,
                    'icon' => $s->icon,
                    'is_system_default' => (bool) $s->is_system_default,
                ])->all();
            }
        } catch (Throwable $e) {
            $clinicalMetrics['error'] = $e->getMessage();
        } finally {
            $this->tenantManager->reset();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'database_name' => $tenant->database_name,
                'plan' => $tenant->plan->value,
                'status' => $tenant->status->value,
                'storage_limit_bytes' => $tenant->storage_limit_bytes,
                'storage_used_bytes' => $tenant->storage_used_bytes,
                'storage_limit_formatted' => $tenant->formattedStorageLimit(),
                'storage_used_formatted' => $tenant->formattedStorageUsed(),
                'storage_percentage' => $tenant->storageUsagePercentage(),
                'staff_count' => $tenant->staffLookups->count(),
                'staff_emails' => $tenant->staffLookups->pluck('email')->values()->all(),
                'created_at' => $tenant->created_at?->format('M d, Y H:i UTC'),
                'updated_at' => $tenant->updated_at?->format('M d, Y H:i UTC'),
                'clinical_metrics' => $clinicalMetrics,
            ]);
        }

        return view('landlord.show', [
            'tenant' => $tenant,
            'clinicalMetrics' => $clinicalMetrics,
        ]);
    }

    /**
     * Display the dedicated edit clinic configuration page.
     */
    public function edit(Tenant $tenant): View
    {
        return view('landlord.edit', [
            'tenant' => $tenant,
            'availablePlans' => TenantPlan::cases(),
        ]);
    }

    /**
     * Update clinic subscription plan or storage quota.
     */
    public function update(Tenant $tenant, UpdateTenantRequest $request, UpdateTenantAction $action): RedirectResponse
    {
        $action->execute($tenant, $request->toDTO());

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Clinic '{$tenant->name}' configuration updated successfully."
        );
    }

    /**
     * Toggle tenant status between Active and Suspended.
     */
    public function toggleStatus(Tenant $tenant, ToggleTenantStatusAction $action): RedirectResponse
    {
        $action->execute($tenant);

        $statusLabel = $tenant->isActive() ? 'activated' : 'suspended';

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Clinic '{$tenant->name}' has been {$statusLabel}."
        );
    }

    /**
     * Run migrations on tenant's isolated database schema.
     */
    public function migrate(Tenant $tenant, MigrateTenantAction $action): RedirectResponse
    {
        $action->execute($tenant);

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Database migrations executed successfully for '{$tenant->name}'."
        );
    }

    /**
     * Batch migrate all active tenant schemas across the platform.
     */
    public function migrateAll(MigrateAllTenantsAction $action): RedirectResponse
    {
        $result = $action->execute();

        $msg = "Batch migrations executed: {$result['migrated']} of {$result['total']} active clinics migrated successfully.";
        if ($result['failed'] > 0) {
            $msg .= " ({$result['failed']} clinics encountered errors; check system logs).";
        }

        return redirect()->route('landlord.dashboard')->with('success', $msg);
    }

    /**
     * Purge clinic and drop its isolated database.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $name = $tenant->name;
        $this->tenantManager->dropDatabase($tenant);
        $tenant->delete();

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Clinic '{$name}' and its isolated database have been completely purged."
        );
    }
}
