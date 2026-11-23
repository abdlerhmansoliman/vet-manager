<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display searchable client directory.
     */
    public function index(Request $request): Response
    {
        $query = Client::query()->withCount('patients')->latest();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('phone_primary', 'like', $search)
                    ->orWhere('contact_person', 'like', $search);
            });
        }

        $clients = $query->paginate(20)->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients->items(),
            'filters' => [
                'search' => (string) $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Show form to register a new client / owner.
     */
    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    /**
     * Store new client.
     */
    public function store(CreateClientRequest $request, CreateClientAction $action): RedirectResponse
    {
        $client = $action->execute($request->toDTO());

        return redirect()->route('portal.clients.show', $client->id)->with(
            'success',
            __('Client :name registered successfully.', ['name' => $client->name])
        );
    }

    /**
     * Display client details with list of their owned animal patients.
     */
    public function show(Client $client): Response
    {
        $client->load(['patients.cases']);

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'patients' => $client->patients,
        ]);
    }

    /**
     * Show form to edit client details.
     */
    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update client details.
     */
    public function update(Client $client, UpdateClientRequest $request, UpdateClientAction $action): RedirectResponse
    {
        $updated = $action->execute($client, $request->toDTO());

        return redirect()->route('portal.clients.show', $updated->id)->with(
            'success',
            __('Client :name updated successfully.', ['name' => $updated->name])
        );
    }
}
