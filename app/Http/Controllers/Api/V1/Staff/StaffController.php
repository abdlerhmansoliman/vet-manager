<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Staff;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\DeleteStaffAction;
use App\Actions\Staff\ListStaffAction;
use App\Actions\Staff\UpdateAvailabilityAction;
use App\Actions\Staff\UpdateStaffAction;
use App\Actions\Staff\UpdateStaffStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Http\Requests\Staff\ListStaffRequest;
use App\Http\Requests\Staff\UpdateAvailabilityRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Requests\Staff\UpdateStaffStatusRequest;
use App\Models\User;
use App\Transformers\StaffTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * List clinic staff with profiles, availability, and active case counts.
     */
    public function index(ListStaffRequest $request, ListStaffAction $action): JsonResponse
    {
        $staff = $action->execute($request->toDTO());

        return response()->json([
            'data' => StaffTransformer::items($staff),
        ]);
    }

    /**
     * Create a new staff member and clinical profile (Owner/HeadVet only).
     */
    public function store(CreateStaffRequest $request, CreateStaffAction $action): JsonResponse
    {
        $user = $action->execute($request->toDTO());

        return response()->json([
            'data' => StaffTransformer::item($user),
        ], 201);
    }

    /**
     * Display the specified clinic staff member.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('profile');

        return response()->json([
            'data' => StaffTransformer::item($user),
        ]);
    }

    /**
     * Update clinic staff details, role, and profile information (Owner/HeadVet only).
     */
    public function update(
        User $user,
        UpdateStaffRequest $request,
        UpdateStaffAction $action
    ): JsonResponse {
        $updatedUser = $action->execute($user, $request->toDTO());

        return response()->json([
            'data' => StaffTransformer::item($updatedUser),
        ]);
    }

    /**
     * Toggle or update active status for a staff member (Owner/HeadVet only).
     */
    public function updateStatus(
        User $user,
        UpdateStaffStatusRequest $request,
        UpdateStaffStatusAction $action
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedUser = $action->execute($user, $request->toDTO(), $actor);

        return response()->json([
            'data' => StaffTransformer::item($updatedUser),
        ]);
    }

    /**
     * Toggle or update availability status for a staff member.
     */
    public function updateAvailability(
        User $user,
        UpdateAvailabilityRequest $request,
        UpdateAvailabilityAction $action
    ): JsonResponse {
        $updatedUser = $action->execute($user, $request->toDTO());

        return response()->json([
            'data' => StaffTransformer::item($updatedUser),
        ]);
    }

    /**
     * Delete a staff member from the clinic (Owner/HeadVet only).
     */
    public function destroy(User $user, Request $request, DeleteStaffAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($user, $actor);

        return response()->json([
            'message' => 'Staff member deleted successfully.',
        ]);
    }
}
