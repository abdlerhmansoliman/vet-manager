<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

final readonly class ReceiveStockDTO
{
    public function __construct(
        public string $productId,
        public string $batchNumber,
        public string $expirationDate,
        public ?string $costPerBaseUnit = null,
        public ?string $packageBuyPrice = null,
        public int $containersCount = 1,
        public ?string $baseQuantityPerContainer = null
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
            packageBuyPrice: isset($data['package_buy_price']) ? (string) $data['package_buy_price'] : null,
            containersCount: (int) ($data['containers_count'] ?? 1),
            baseQuantityPerContainer: isset($data['base_quantity_per_container']) ? (string) $data['base_quantity_per_container'] : null,
        );
    }
}
