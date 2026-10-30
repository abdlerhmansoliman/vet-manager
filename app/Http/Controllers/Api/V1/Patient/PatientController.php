<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Patient;

use App\Actions\Patient\CreatePatientAction;
use App\Actions\Patient\UpdatePatientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\CreatePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Client;
use App\Models\Patient;
use App\Transformers\PatientTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * List all patients across the clinic tenant with their owner clients.
     */
    public function all(Request $request): JsonResponse
    {
        $query = Patient::query()->with('client')->withCount('activeCases');

        if ($request->filled('species')) {
            $query->where('species', $request->query('species'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name_or_tag', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%")
                    ->orWhere('microchip_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('name_or_tag')->get();

        return response()->json([
            'data' => PatientTransformer::items($patients),
        ]);
    }

    /**
     * List all patients belonging to a specific client.
     */
    public function index(Client $client): JsonResponse
    {
        $patients = $client->patients()
            ->withCount('activeCases')
            ->orderBy('name_or_tag')
            ->get();

        return response()->json([
            'data' => PatientTransformer::items($patients),
        ]);
    }

    /**
     * Register a new patient under a clinic client.
     */
    public function store(CreatePatientRequest $request, CreatePatientAction $action): JsonResponse
    {
        $patient = $action->execute($request->toDTO());

        return response()->json([
            'data' => PatientTransformer::item($patient),
        ], 201);
    }

    /**
     * Display a specific patient with their owner client.
     */
    public function show(Patient $patient): JsonResponse
    {
        $patient->load('client')->loadCount('activeCases');

        return response()->json([
            'data' => PatientTransformer::item($patient),
        ]);
    }

    /**
     * Update a patient record.
     */
    public function update(Patient $patient, UpdatePatientRequest $request, UpdatePatientAction $action): JsonResponse
    {
        $updated = $action->execute($patient, $request->toDTO());

        return response()->json([
            'data' => PatientTransformer::item($updated),
        ]);
    }
}
