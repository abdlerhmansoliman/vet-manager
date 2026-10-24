<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cases;

use App\Actions\Cases\OpenCaseAction;
use App\Actions\Cases\UpdateCaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\OpenCaseRequest;
use App\Http\Requests\Cases\UpdateCaseRequest;
use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Transformers\ClinicalCaseTransformer;
use Illuminate\Http\JsonResponse;

class ClinicalCaseController extends Controller
{
    /**
     * List all clinical cases for a given patient, newest first.
     */
    public function index(Patient $patient): JsonResponse
    {
        $cases = $patient->cases()
            ->with(['primaryVeterinarian', 'diagnoses'])
            ->orderByDesc('admitted_at')
            ->get();

        return response()->json([
            'data' => ClinicalCaseTransformer::items($cases),
        ]);
    }

    /**
     * Open a new clinical case for a patient.
     */
    public function store(OpenCaseRequest $request, OpenCaseAction $action): JsonResponse
    {
        $case = $action->execute($request->toDTO());
        $case->load(['patient.client', 'primaryVeterinarian', 'diagnoses']);

        return response()->json([
            'data' => ClinicalCaseTransformer::item($case),
        ], 201);
    }

    /**
     * Display a specific clinical case with patient, vet, and diagnoses.
     */
    public function show(ClinicalCase $case): JsonResponse
    {
        $case->load(['patient.client', 'primaryVeterinarian', 'diagnoses']);

        return response()->json([
            'data' => ClinicalCaseTransformer::item($case),
        ]);
    }

    /**
     * Update case details, status, priority, and assigned veterinarian.
     */
    public function update(ClinicalCase $case, UpdateCaseRequest $request, UpdateCaseAction $action): JsonResponse
    {
        $updated = $action->execute($case, $request->toDTO());

        return response()->json([
            'data' => ClinicalCaseTransformer::item($updated),
        ]);
    }
}
