<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\DeleteClientAction;
use App\Actions\Client\ListClientsAction;
use App\Actions\Client\UpdateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\ListClientsRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Transformers\ClientTransformer;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * List clinic clients with optional type filter and search.
     */
    public function index(ListClientsRequest $request, ListClientsAction $action): JsonResponse
    {
        $paginator = $action->execute($request->toDTO());

        return response()->json([
            'data' => ClientTransformer::items($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Register a new clinic client.
     */
    public function store(CreateClientRequest $request, CreateClientAction $action): JsonResponse
    {
        $client = $action->execute($request->toDTO());

        return response()->json([
            'data' => ClientTransformer::item($client),
        ], 201);
    }

    /**
     * Display a specific clinic client with patient summary.
     */
    public function show(Client $client): JsonResponse
    {
        $client->loadCount('activePatients');

        return response()->json([
            'data' => ClientTransformer::item($client),
        ]);
    }

    /**
     * Update an existing clinic client.
     */
    public function update(Client $client, UpdateClientRequest $request, UpdateClientAction $action): JsonResponse
    {
        $updated = $action->execute($client, $request->toDTO());

        return response()->json([
            'data' => ClientTransformer::item($updated),
        ]);
    }

    /**
     * Soft-delete a clinic client (blocked when active patients exist).
     */
    public function destroy(Client $client, DeleteClientAction $action): JsonResponse
    {
        $action->execute($client);

        return response()->json([
            'message' => 'Client record archived successfully.',
        ]);
    }
}
