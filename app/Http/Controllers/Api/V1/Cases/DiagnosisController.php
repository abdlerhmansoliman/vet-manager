<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cases;

use App\Actions\Cases\CreateDiagnosisAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\CreateDiagnosisRequest;
use App\Models\ClinicalCase;
use App\Models\User;
use App\Transformers\DiagnosisTransformer;
use Illuminate\Http\JsonResponse;

class DiagnosisController extends Controller
{
    /**
     * List all diagnoses recorded under a specific clinical case.
     */
    public function index(ClinicalCase $case): JsonResponse
    {
        $diagnoses = $case->diagnoses()->orderByDesc('diagnosed_at')->get();

        return response()->json([
            'data' => DiagnosisTransformer::items($diagnoses),
        ]);
    }

    /**
     * Record a new diagnosis entry under a clinical case.
     */
    public function store(ClinicalCase $case, CreateDiagnosisRequest $request, CreateDiagnosisAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $diagnosis = $action->execute($case, $request->toDTO(), $actor);

        return response()->json([
            'data' => DiagnosisTransformer::item($diagnosis),
        ], 201);
    }
}
