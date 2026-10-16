<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class ReceiveStockDTO
{
    public function __construct(
        public string $productId,
        public string $batchNumber,
        public string $expirationDate,
        public ?string $costPerBaseUnit,
        public int $containersCount,
        public string $baseQuantityPerContainer
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (string) $data['product_id'],
            batchNumber: strtoupper(trim((string) $data['batch_number'])),
            expirationDate: (string) $data['expiration_date'],
            costPerBaseUnit: isset($data['cost_per_base_unit']) ? (string) $data['cost_per_base_unit'] : null,
            containersCount: (int) $data['containers_count'],
            baseQuantityPerContainer: (string) $data['base_quantity_per_container'],
        );
    }
}
