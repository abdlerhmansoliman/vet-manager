<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Services\Inventory\BatchDeductionService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:inventory_products,sku'],
            'base_unit' => ['required', Rule::enum(MeasurementBaseUnit::class)],
            'controlled_schedule' => ['required', Rule::enum(ControlledSubstanceSchedule::class)],
            'reorder_threshold' => ['nullable', 'numeric', 'min:0'],
            'package_description' => ['nullable', 'string', 'max:255'],
            'packaging_factor' => ['nullable', 'numeric', 'min:1'],
        ]);

        $isControlled = ControlledSubstanceSchedule::from((string) $validated['controlled_schedule'])->isControlled();

        $product = InventoryProduct::query()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'base_unit' => $validated['base_unit'],
            'is_controlled' => $isControlled,
            'controlled_schedule' => $validated['controlled_schedule'],
            'reorder_threshold' => isset($validated['reorder_threshold']) ? (string) $validated['reorder_threshold'] : '0.0000',
            'package_description' => $validated['package_description'] ?? null,
            'packaging_factor' => isset($validated['packaging_factor']) ? (string) $validated['packaging_factor'] : '1.0000',
        ]);

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
    public function storeReceive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:inventory_products,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'expiration_date' => ['required', 'date', 'after:today'],
            'initial_base_quantity' => ['required', 'numeric', 'gt:0'],
            'cost_per_base_unit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $qty = number_format((float) $validated['initial_base_quantity'], 4, '.', '');
        $cost = isset($validated['cost_per_base_unit']) ? number_format((float) $validated['cost_per_base_unit'], 4, '.', '') : '0.0000';
        $actor = Auth::guard('web')->user();

        DB::connection('tenant')->transaction(function () use ($validated, $qty, $cost, $actor): void {
            $batch = InventoryBatch::query()->create([
                'product_id' => $validated['product_id'],
                'batch_number' => $validated['batch_number'],
                'expiration_date' => $validated['expiration_date'],
                'cost_per_base_unit' => $cost,
                'initial_base_quantity' => $qty,
                'current_base_balance' => $qty,
            ]);

            $product = InventoryProduct::query()->findOrFail($validated['product_id']);
            $newTotal = $product->calculateTotalStock();

            InventoryMovementLedger::query()->create([
                'idempotency_key' => (string) Str::uuid(),
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'movement_type' => StockMovementType::Receive,
                'quantity' => $qty,
                'balance_after' => $newTotal,
                'reason' => 'Stock intake received via portal console',
                'recorded_by_user_id' => $actor->id,
                'created_at' => now(),
            ]);
        });

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
    public function storeWaste(Request $request, BatchDeductionService $deductionService): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:inventory_products,id'],
            'amount_base_units' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $product = InventoryProduct::query()->findOrFail($validated['product_id']);
        $wasteAmount = number_format((float) $validated['amount_base_units'], 4, '.', '');
        $actor = Auth::guard('web')->user();

        $deductionService->deduct(
            product: $product,
            doseAmountBaseUnits: $wasteAmount,
            actor: $actor,
            movementType: StockMovementType::WastageDiscard,
            reason: (string) $validated['reason'],
            idempotencyKey: (string) Str::uuid()
        );

        return redirect()->route('portal.inventory.index')->with(
            'success',
            __('Drug waste of :amount :unit logged.', [
                'amount' => $wasteAmount,
                'unit' => is_object($product->base_unit) ? $product->base_unit->value : $product->base_unit,
            ])
        );
    }
}
