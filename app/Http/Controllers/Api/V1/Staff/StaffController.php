<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Staff;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\UpdateAvailabilityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Http\Requests\Staff\UpdateAvailabilityRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffController extends Controller
{
    /**
     * List clinic staff with profiles, availability, and active case counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $staff = User::query()->with('profile')->get();

        return StaffResource::collection($staff);
    }

    /**
     * Create a new staff member and clinical profile (Owner/HeadVet only).
     */
    public function store(CreateStaffRequest $request, CreateStaffAction $action): JsonResponse
    {
        $user = $action->execute($request->toDTO());

        return (new StaffResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Toggle or update availability status for a staff member.
     */
    public function updateAvailability(
        User $user,
        UpdateAvailabilityRequest $request,
        UpdateAvailabilityAction $action
    ): StaffResource {
        $updatedUser = $action->execute($user, $request->toDTO());

        return new StaffResource($updatedUser);
    }
}
