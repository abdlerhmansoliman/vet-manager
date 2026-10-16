<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class WasteStockDTO
{
    public function __construct(
        public string $productId,
        public string $quantity,
        public string $reason,
        public ?string $witnessId = null,
        public ?string $witnessPin = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (string) $data['product_id'],
            quantity: (string) $data['quantity'],
            reason: (string) $data['reason'],
            witnessId: isset($data['witness_id']) ? (string) $data['witness_id'] : null,
            witnessPin: isset($data['witness_pin']) ? (string) $data['witness_pin'] : null,
        );
    }
}
