<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Species;

use App\Http\Controllers\Controller;
use App\Http\Requests\Species\CreateSpeciesRequest;
use App\Models\Species;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SpeciesController extends Controller
{
    /**
     * List all active animal species for the current tenant practice.
     */
    public function index(): JsonResponse
    {
        $species = Species::query()
            ->where('is_active', true)
            ->orderBy('is_system_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => $species,
        ]);
    }

    /**
     * Register a new custom species for the current tenant practice.
     */
    public function store(CreateSpeciesRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        // Check if species with this code already exists for this tenant
        $existing = Species::withTrashed()
            ->where('code', $dto->code)
            ->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update([
                'name' => $dto->name,
                'is_active' => true,
                'icon' => $dto->icon ?? $existing->icon,
            ]);

            return response()->json([
                'message' => 'Species restored and updated successfully.',
                'data' => $existing,
            ], Response::HTTP_OK);
        }

        $species = Species::query()->create([
            'id' => $dto->id ?? (string) Str::uuid(),
            'name' => $dto->name,
            'code' => $dto->code,
            'icon' => $dto->icon,
            'is_system_default' => false,
            'is_active' => $dto->isActive,
        ]);

        return response()->json([
            'message' => 'Species registered successfully.',
            'data' => $species,
        ], Response::HTTP_CREATED);
    }
}
