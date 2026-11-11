<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cases;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cases\AdministerDoseRequest;
use App\Models\ClinicalCase;
use App\Models\DoseAdministration;
use App\Models\InventoryProduct;
use App\Models\User;
use App\Services\Inventory\BatchDeductionService;
use App\Transformers\DoseAdministrationTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CaseDoseAdministrationController extends Controller
{
    /**
     * Administer a medication dose to a patient encounter and deduct physical stock via batch-only FEFO.
     */
    public function store(
        AdministerDoseRequest $request,
        ClinicalCase $case,
        BatchDeductionService $deductionService
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $idempotencyKey = $request->header('Idempotency-Key');

        $productId = (string) $request->input('product_id');
        $doseAmount = number_format((float) $request->input('dose_amount_base_units'), 4, '.', '');
        $routeAdministered = $request->input('route_administered');
        $clinicalNotes = $request->input('clinical_notes');

        $result = DB::connection('tenant')->transaction(function () use (
            $case,
            $actor,
            $productId,
            $doseAmount,
            $routeAdministered,
            $clinicalNotes,
            $idempotencyKey,
            $deductionService
        ): array {
            $product = InventoryProduct::query()->findOrFail($productId);

            // 1. Create the DoseAdministration record linking encounter, patient, product, and clinician
            $doseAdministration = DoseAdministration::query()->create([
                'case_id' => $case->id,
                'patient_id' => $case->patient_id,
                'product_id' => $product->id,
                'administered_by_user_id' => $actor->id,
                'dose_amount_base_units' => $doseAmount,
                'route_administered' => $routeAdministered,
                'clinical_notes' => $clinicalNotes,
            ]);

            // 2. Perform Batch-only FEFO stock deduction
            $reason = "Case #{$case->case_number}: Administered {$doseAmount} {$product->base_unit->value}".($routeAdministered ? " via {$routeAdministered}" : '');
            $deduction = $deductionService->deduct(
                product: $product,
                doseAmountBaseUnits: $doseAmount,
                actor: $actor,
                movementType: StockMovementType::DoseAdministration,
                reason: $reason,
                idempotencyKey: $idempotencyKey
            );

            return [
                'dose_administration' => $doseAdministration,
                'product' => $product,
                'deduction' => $deduction,
            ];
        });

        return response()->json([
            'message' => 'Medication dose administered and batch stock deducted successfully.',
            'data' => DoseAdministrationTransformer::item($result['dose_administration']),
            'inventory' => [
                'total_deducted' => $result['deduction']['total_deducted'],
                'remaining_stock' => $result['deduction']['remaining_stock'],
                'batches_depleted' => count($result['deduction']['batches_affected']),
            ],
        ], 201);
    }

    /**
     * Retrieve medication administration history for a clinical case encounter.
     */
    public function index(ClinicalCase $case): JsonResponse
    {
        $doses = $case->doseAdministrations()
            ->with(['product', 'administeredBy'])
            ->get();

        return response()->json([
            'data' => DoseAdministrationTransformer::items($doses),
        ]);
    }
}
