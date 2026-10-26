<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cases;

use App\Actions\Cases\CreateClinicalProgressNoteAction;
use App\Actions\Cases\SignClinicalProgressNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\CreateClinicalProgressNoteRequest;
use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\User;
use App\Transformers\ClinicalProgressNoteTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicalProgressNoteController extends Controller
{
    /**
     * List all progress notes recorded under a specific clinical case (newest first).
     */
    public function index(ClinicalCase $case): JsonResponse
    {
        $notes = $case->progressNotes()
            ->with(['veterinarian', 'images'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => ClinicalProgressNoteTransformer::items($notes),
        ]);
    }

    /**
     * Record a new clinical progress note (timeline visit / re-examination) under a clinical case.
     */
    public function store(
        ClinicalCase $case,
        CreateClinicalProgressNoteRequest $request,
        CreateClinicalProgressNoteAction $action
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $note = $action->execute($case, $request->toDTO(), $actor);

        return response()->json([
            'data' => ClinicalProgressNoteTransformer::item($note),
        ], 201);
    }

    /**
     * Display a specific progress note entry with assigned vet and examination images.
     */
    public function show(ClinicalCase $case, ClinicalProgressNote $progressNote): JsonResponse
    {
        abort_if($progressNote->clinical_case_id !== $case->id, 404);

        $progressNote->load(['veterinarian', 'images']);

        return response()->json([
            'data' => ClinicalProgressNoteTransformer::item($progressNote),
        ]);
    }

    /**
     * Digitally sign and lock a clinical progress note.
     */
    public function sign(
        ClinicalCase $case,
        ClinicalProgressNote $progressNote,
        Request $request,
        SignClinicalProgressNoteAction $action
    ): JsonResponse {
        abort_if($progressNote->clinical_case_id !== $case->id, 404);

        /** @var User $actor */
        $actor = $request->user();
        $signedNote = $action->execute($progressNote, $actor);

        return response()->json([
            'data' => ClinicalProgressNoteTransformer::item($signedNote),
        ]);
    }
}
