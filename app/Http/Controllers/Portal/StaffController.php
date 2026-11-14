<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VeterinarianProfile;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(StaffRole::class)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'pin' => ['nullable', 'string', 'size:4'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => strtolower(trim((string) $validated['email'])),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'pin' => $validated['pin'] ?? null,
            'is_active' => true,
        ]);

        if (StaffRole::from((string) $validated['role'])->isDoctor()) {
            VeterinarianProfile::query()->create([
                'user_id' => $user->id,
                'is_available' => true,
            ]);
        }

        return redirect()->route('portal.staff.index')->with(
            'success',
            __('Staff member :name added successfully.', ['name' => $user->name])
        );
    }

    /**
     * Toggle practitioner on-duty / availability status.
     */
    public function toggleAvailability(User $user): RedirectResponse
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $profile = $user->profile;
        if ($profile instanceof VeterinarianProfile) {
            $profile->update([
                'is_available' => $user->is_active,
            ]);
        }

        return back()->with('success', __('Staff availability updated.'));
    }
}
