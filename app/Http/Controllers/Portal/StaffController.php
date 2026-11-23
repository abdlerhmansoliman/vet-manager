<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\UpdateAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
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
        $staff = User::query()->with('profile')->latest()->get()->map(fn (User $u): array => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => is_object($u->role) ? $u->role->value : (string) $u->role,
            'is_active' => (bool) $u->is_active,
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
            return back()->with('error', __('You cannot deactivate yourself.'));
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);
        $action->execute($user, $newStatus);

        return back()->with('success', __('Staff availability updated.'));
    }
}
