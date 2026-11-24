<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\UpdateAvailabilityAction;
use App\Enums\CaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use BackedEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display clinic staff directory with roles and on-duty availability.
     */
    public function index(): Response
    {
        $activeStatuses = CaseStatus::activeValues();

        $staff = User::query()
            ->with('profile')
            ->withCount([
                'cases as active_cases_count' => fn ($q) => $q->whereIn('status', $activeStatuses),
            ])
            ->latest()
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role instanceof BackedEnum ? $u->role->value : (string) $u->role,
                'is_active' => (bool) $u->is_active,
                'profile' => $u->profile ? [
                    'job_title' => $u->profile->job_title,
                    'specialty' => $u->profile->specialty,
                    'license_number' => $u->profile->license_number,
                    'is_available' => (bool) $u->profile->is_available,
                ] : null,
                'active_cases_count' => (int) ($u->active_cases_count ?? 0),
            ]);

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
        ]);
    }

    /**
     * Show form to add a new practitioner or staff member.
     */
    public function create(): Response
    {
        return Inertia::render('Staff/Create');
    }

    /**
     * Store new staff member.
     */
    public function store(CreateStaffRequest $request, CreateStaffAction $action): RedirectResponse
    {
        $user = $action->execute($request->toDTO());

        return redirect()->route('portal.staff.index')->with(
            'success',
            __('Staff member :name added successfully.', ['name' => $user->name])
        );
    }

    /**
     * Toggle practitioner on-duty / availability status.
     */
    public function toggleAvailability(User $user, Request $request, UpdateAvailabilityAction $action): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            return back()->with('error', __('You cannot modify your own availability or active status.'));
        }

        if ($request->has('is_available')) {
            $newStatus = $request->boolean('is_available');
        } else {
            $currentAvailability = (bool) ($user->profile?->is_available ?? true);
            $newStatus = ! $currentAvailability;
        }

        $action->execute($user, $newStatus);

        return back()->with('success', __('Staff availability updated.'));
    }
}
