<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Inventory\CreateProductAction;
use App\Actions\Inventory\ReceiveStockAction;
use App\Actions\Inventory\WasteStockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateProductRequest;
use App\Http\Requests\Inventory\ReceiveStockRequest;
use App\Http\Requests\Inventory\WasteStockRequest;
use App\Models\InventoryProduct;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Display pharmaceutical products directory with live fractional balances.
     */
    public function index(Request $request): Response
    {
        $query = InventoryProduct::query()->with('batches')->latest();

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search);
            });
        }

        $products = $query->paginate(20)->withQueryString();

        $formattedProducts = collect($products->items())->map(fn (InventoryProduct $p): array => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'base_unit' => is_object($p->base_unit) ? $p->base_unit->value : (string) $p->base_unit,
            'is_controlled' => (bool) $p->is_controlled,
            'controlled_schedule' => is_object($p->controlled_schedule) ? $p->controlled_schedule->value : (string) $p->controlled_schedule,
            'current_stock' => $p->calculateTotalStock(),
            'reorder_threshold' => $p->reorder_threshold,
            'batches_count' => $p->batches->count(),
        ])->all();

        return Inertia::render('Inventory/Index', [
            'products' => $formattedProducts,
            'filters' => [
                'search' => (string) $request->input('search', ''),
            ],
        ]);
    }

    /**
     * Show form to add a new pharmaceutical product.
     */
    public function create(): Response
    {
        return Inertia::render('Inventory/Create');
    }

    /**
     * Store new inventory product.
     */
    public function store(CreateProductRequest $request, CreateProductAction $action): RedirectResponse
    {
        $product = $action->execute($request->toDTO());

        return redirect()->route('portal.inventory.index')->with(
            'success',
            __('Product :name added to clinic catalog.', ['name' => $product->name])
        );
    }

    /**
     * Show form to receive stock intake for a product.
     */
    public function receive(Request $request): Response
    {
        $products = InventoryProduct::query()->orderBy('name')->get(['id', 'name', 'sku', 'base_unit']);
        $preselectedId = (string) $request->input('product_id', '');

        return Inertia::render('Inventory/Receive', [
            'products' => $products,
            'selectedProductId' => $preselectedId,
        ]);
    }

    /**
     * Process stock intake and record batch & ledger entry.
     */
    public function storeReceive(ReceiveStockRequest $request, ReceiveStockAction $action): RedirectResponse
    {
        $action->execute($request->toDTO(), $request->user());

        return redirect()->route('portal.inventory.index')->with(
            'success',
            __('Stock intake recorded successfully.')
        );
    }

    /**
     * Show form to log drug waste / discarded volume.
     */
    public function waste(Request $request): Response
    {
        $products = InventoryProduct::query()
            ->with('batches')
            ->orderBy('name')
            ->get()
            ->filter(fn (InventoryProduct $p): bool => (float) $p->calculateTotalStock() > 0)
            ->map(fn (InventoryProduct $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'base_unit' => is_object($p->base_unit) ? $p->base_unit->value : (string) $p->base_unit,
                'current_stock' => $p->calculateTotalStock(),
            ])
            ->values();

        return Inertia::render('Inventory/Waste', [
            'products' => $products,
        ]);
    }

    /**
     * Process drug waste deduction.
     */
    public function storeWaste(WasteStockRequest $request, WasteStockAction $action): RedirectResponse
    {
        $result = $action->execute($request->toDTO(), $request->user());

        return redirect()->route('portal.inventory.index')->with(
            'success',
            __('Drug waste of :amount :unit logged.', [
                'amount' => $result['total_deducted'],
                'unit' => is_object($result['product']->base_unit) ? $result['product']->base_unit->value : $result['product']->base_unit,
            ])
        );
    }
}
