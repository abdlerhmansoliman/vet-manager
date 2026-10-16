<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;

final readonly class CreateProductDTO
{
    public function __construct(
        public string $name,
        public string $sku,
        public MeasurementBaseUnit $baseUnit,
        public bool $isControlled = false,
        public ControlledSubstanceSchedule $controlledSchedule = ControlledSubstanceSchedule::Uncontrolled,
        public string $reorderThreshold = '0.0000',
        public ?string $packageDescription = null,
        public string $packagingFactor = '1.0000'
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $baseUnit = $data['base_unit'] instanceof MeasurementBaseUnit
            ? $data['base_unit']
            : MeasurementBaseUnit::from((string) $data['base_unit']);

        $controlledSchedule = isset($data['controlled_schedule'])
            ? ($data['controlled_schedule'] instanceof ControlledSubstanceSchedule
                ? $data['controlled_schedule']
                : ControlledSubstanceSchedule::from((string) $data['controlled_schedule']))
            : ControlledSubstanceSchedule::Uncontrolled;

        return new self(
            name: (string) $data['name'],
            sku: strtoupper(trim((string) $data['sku'])),
            baseUnit: $baseUnit,
            isControlled: isset($data['is_controlled']) ? (bool) $data['is_controlled'] : false,
            controlledSchedule: $controlledSchedule,
            reorderThreshold: isset($data['reorder_threshold']) ? (string) $data['reorder_threshold'] : '0.0000',
            packageDescription: isset($data['package_description']) ? (string) $data['package_description'] : null,
            packagingFactor: isset($data['packaging_factor']) ? (string) $data['packaging_factor'] : '1.0000',
        );
    }
}
