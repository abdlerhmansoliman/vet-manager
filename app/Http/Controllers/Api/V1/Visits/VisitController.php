<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Visits;

use App\Actions\Visits\CreateVisitAction;
use App\Actions\Visits\UpdateVisitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Visits\CreateVisitRequest;
use App\Http\Requests\Visits\UpdateVisitRequest;
use App\Models\ClinicalCase;
use App\Models\Visit;
use App\Transformers\VisitTransformer;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    /**
     * List all visits recorded under a specific clinical case (newest first).
     */
    public function index(ClinicalCase $case): JsonResponse
    {
        $visits = $case->visits()
            ->with(['progressNotes'])
            ->orderByDesc('visit_date')
            ->get();

        return response()->json([
            'data' => VisitTransformer::items($visits),
        ]);
    }

    /**
     * Start a new visit under a specific clinical case.
     */
    public function store(
        ClinicalCase $case,
        CreateVisitRequest $request,
        CreateVisitAction $action
    ): JsonResponse {
        $visit = $action->execute($case, $request->toDTO());

        return response()->json([
            'data' => VisitTransformer::item($visit),
        ], 201);
    }

    /**
     * Display a specific visit with progress notes.
     */
    public function show(Visit $visit): JsonResponse
    {
        $visit->load(['progressNotes']);

        return response()->json([
            'data' => VisitTransformer::item($visit),
        ]);
    }

    /**
     * Update visit title, type, reason for visit, or status.
     */
    public function update(
        Visit $visit,
        UpdateVisitRequest $request,
        UpdateVisitAction $action
    ): JsonResponse {
        $updated = $action->execute($visit, $request->toDTO());

        return response()->json([
            'data' => VisitTransformer::item($updated),
        ]);
    }
}
