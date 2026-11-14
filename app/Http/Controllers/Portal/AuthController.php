<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Auth\ResolveTenantsAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display the clinic login ingress.
     */
    public function create(): Response|RedirectResponse
    {
        if (session()->has('active_tenant_id')) {
            $tenant = $this->tenantManager->resolve((string) session('active_tenant_id'));
            if ($tenant instanceof Tenant && $tenant->isActive() && ! $tenant->isSuspended()) {
                $this->tenantManager->switch($tenant);
                if (Auth::guard('web')->check()) {
                    return redirect()->route('portal.dashboard');
                }
            } else {
                session()->forget('active_tenant_id');
            }
        }

        return Inertia::render('Auth/Login', [
            'step' => 'discover_email',
            'discoveredClinics' => [],
            'selectedTenant' => null,
            'discoveredEmail' => '',
        ]);
    }

    /**
     * Step 1: Discover tenant clinics associated with the email address.
     */
    public function discover(Request $request, ResolveTenantsAction $action): Response|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $tenants = $action->execute($email);

        if ($tenants->isEmpty()) {
            return back()->withInput()->withErrors([
                'email' => __('No veterinary clinics found associated with this email address.'),
            ]);
        }

        $clinics = $tenants->map(fn (Tenant $t): array => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'subdomain' => $t->slug,
            'plan' => is_object($t->plan) ? $t->plan->value : (string) $t->plan,
            'role' => $t->pivot_role ?? 'Doctor',
        ])->values()->all();

        $isSingle = $tenants->count() === 1;

        return Inertia::render('Auth/Login', [
            'step' => $isSingle ? 'enter_password' : 'select_clinic',
            'selectedTenant' => $isSingle ? $tenants->first() : null,
            'discoveredClinics' => $clinics,
            'discoveredEmail' => $email,
        ]);
    }

    /**
     * Step 2A: Confirm selected clinic when user belongs to multiple facilities.
     */
    public function clinicSelected(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'tenant_id' => ['required', 'string'],
        ]);

        $tenant = $this->tenantManager->resolve((string) $validated['tenant_id']);

        if (! $tenant instanceof Tenant || ! $tenant->isActive()) {
            return back()->withErrors(['tenant_id' => __('Selected clinic is not active.')]);
        }

        return Inertia::render('Auth/Login', [
            'step' => 'enter_password',
            'selectedTenant' => $tenant,
            'discoveredClinics' => [[
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'subdomain' => $tenant->slug,
                'plan' => is_object($tenant->plan) ? $tenant->plan->value : (string) $tenant->plan,
            ]],
            'discoveredEmail' => strtolower(trim((string) $validated['email'])),
        ]);
    }

    /**
     * Step 2B: Authenticate against the isolated tenant database.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'tenant_id' => ['required', 'string'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $tenant = $this->tenantManager->resolve((string) $validated['tenant_id']);

        if (! $tenant instanceof Tenant || ! $tenant->isActive() || $tenant->isSuspended()) {
            return back()->withErrors(['email' => __('Clinic account is suspended or unavailable.')]);
        }

        // Switch to the target tenant database
        $this->tenantManager->switch($tenant);

        if (! Auth::guard('web')->attempt([
            'email' => $email,
            'password' => (string) $validated['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            return back()->withInput(['email' => $email])->withErrors([
                'password' => __('Invalid credentials for this veterinary clinic.'),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('active_tenant_id', $tenant->id);

        return redirect()->intended(route('portal.dashboard'))->with(
            'success',
            __('Welcome back, :name.', ['name' => Auth::guard('web')->user()->name])
        );
    }

    /**
     * Seamlessly switch to another clinic the practitioner belongs to.
     */
    public function switchClinic(Request $request, ?Tenant $tenant = null): RedirectResponse
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::guard('web')->user();

        if ($currentUser === null) {
            return redirect()->route('portal.login');
        }

        $tenantId = $tenant?->id ?? (string) $request->input('tenant_id');
        $targetTenant = $this->tenantManager->resolve($tenantId);

        if (! $targetTenant instanceof Tenant) {
            return back()->withErrors(['switch' => __('Clinic not found.')]);
        }

        $email = strtolower(trim($currentUser->email));

        // Verify staff association with the target clinic
        $hasAccess = TenantStaffLookup::query()
            ->where('tenant_id', $targetTenant->id)
            ->where('email', $email)
            ->exists();

        if (! $hasAccess || ! $targetTenant->isActive() || $targetTenant->isSuspended()) {
            return back()->withErrors(['switch' => __('You do not have active access to that clinic.')]);
        }

        // Switch tenant context and login into the target clinic's user record
        $this->tenantManager->switch($targetTenant);

        $targetUser = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $targetUser instanceof User) {
            return back()->withErrors(['switch' => __('User profile not found in target clinic.')]);
        }

        Auth::guard('web')->login($targetUser);
        $request->session()->put('active_tenant_id', $targetTenant->id);

        return redirect()->route('portal.dashboard')->with(
            'success',
            __('Switched active practice to :clinic.', ['clinic' => $targetTenant->name])
        );
    }

    /**
     * Terminate the practitioner session and reset context.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget('active_tenant_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->tenantManager->reset();

        return redirect()->route('portal.login')->with(
            'status',
            __('You have been signed out.')
        );
    }
}
