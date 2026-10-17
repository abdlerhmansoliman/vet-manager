<?php

declare(strict_types=1);

namespace App\Http\Controllers\Landlord;

use App\Actions\Landlord\GetLandlordMetricsAction;
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
use Illuminate\View\View;

class DashboardController extends Controller
{
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
     * Retrieve tenant details as JSON for inspection modal.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('staffLookups');

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
     * Purge clinic and drop its isolated database.
     */
    public function destroy(Tenant $tenant, TenantManager $tenantManager): RedirectResponse
    {
        $name = $tenant->name;
        $tenantManager->dropDatabase($tenant);
        $tenant->delete();

        return redirect()->route('landlord.dashboard')->with(
            'success',
            "Clinic '{$name}' and its isolated database have been completely purged."
        );
    }
}
