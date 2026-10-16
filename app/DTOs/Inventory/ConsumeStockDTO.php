<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\Enums\StockMovementType;

final readonly class ConsumeStockDTO
{
    public function __construct(
        public string $productId,
        public string $quantity,
        public StockMovementType $movementType = StockMovementType::DoseAdministration,
        public ?string $witnessId = null,
        public ?string $witnessPin = null,
        public ?string $reason = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $movementType = isset($data['movement_type'])
            ? ($data['movement_type'] instanceof StockMovementType
                ? $data['movement_type']
                : StockMovementType::from((string) $data['movement_type']))
            : StockMovementType::DoseAdministration;

        return new self(
            productId: (string) $data['product_id'],
            quantity: (string) $data['quantity'],
            movementType: $movementType,
            witnessId: isset($data['witness_id']) ? (string) $data['witness_id'] : null,
            witnessPin: isset($data['witness_pin']) ? (string) $data['witness_pin'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }
}
