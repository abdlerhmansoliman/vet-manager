<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request) {
                    if (! app(TenantManager::class)->isResolved()) {
                        return null;
                    }

                    try {
                        $user = $request->user('web');
                    } catch (\Throwable) {
                        return null;
                    }

                    if (! $user) {
                        return null;
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => is_object($user->role) ? $user->role->value : (string) $user->role,
                        'is_active' => (bool) $user->is_active,
                    ];
                },
            ],
            'currentTenant' => function () {
                $tenant = app(TenantManager::class)->current();
                if (! $tenant) {
                    return null;
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => is_object($tenant->status) ? $tenant->status->value : (string) $tenant->status,
                    'plan' => is_object($tenant->plan) ? $tenant->plan->value : (string) $tenant->plan,
                ];
            },
            'userClinics' => function () use ($request) {
                if (! app(TenantManager::class)->isResolved()) {
                    return [];
                }

                try {
                    $user = $request->user('web');
                } catch (\Throwable) {
                    return [];
                }

                if (! $user) {
                    return [];
                }

                $tenantIds = TenantStaffLookup::query()->where('email', $user->email)->pluck('tenant_id');

                return Tenant::query()
                    ->whereIn('id', $tenantIds)
                    ->where('status', 'active')
                    ->get(['id', 'name', 'slug', 'plan'])
                    ->toArray();
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'locale' => fn () => app()->getLocale(),
            'dir' => fn () => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
        ];
    }
}
