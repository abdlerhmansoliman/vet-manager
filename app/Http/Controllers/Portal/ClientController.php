<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\ClientType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_type' => ['nullable', Rule::enum(ClientType::class)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'phone_primary' => ['nullable', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $client = Client::query()->create([
            'name' => $validated['name'],
            'client_type' => $validated['client_type'] ?? ClientType::Individual,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone_primary' => $validated['phone'] ?? $validated['phone_primary'] ?? 'N/A',
            'phone_secondary' => $validated['phone_secondary'] ?? null,
            'physical_address' => $validated['address'] ?? $validated['physical_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

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
    public function update(Client $client, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_type' => ['nullable', Rule::enum(ClientType::class)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'phone_primary' => ['nullable', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $client->update([
            'name' => $validated['name'],
            'client_type' => $validated['client_type'] ?? $client->client_type,
            'contact_person' => $validated['contact_person'] ?? $client->contact_person,
            'phone_primary' => $validated['phone'] ?? $validated['phone_primary'] ?? $client->phone_primary,
            'phone_secondary' => $validated['phone_secondary'] ?? $client->phone_secondary,
            'physical_address' => $validated['address'] ?? $validated['physical_address'] ?? $client->physical_address,
            'notes' => $validated['notes'] ?? $client->notes,
        ]);

        return redirect()->route('portal.clients.show', $client->id)->with(
            'success',
            __('Client :name updated successfully.', ['name' => $client->name])
        );
    }
}
