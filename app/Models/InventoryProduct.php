<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContainerStatus;
use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryProduct extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'inventory_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'sku',
        'base_unit',
        'is_controlled',
        'controlled_schedule',
        'reorder_threshold',
        'package_description',
        'packaging_factor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_unit' => MeasurementBaseUnit::class,
            'controlled_schedule' => ControlledSubstanceSchedule::class,
            'is_controlled' => 'boolean',
            'reorder_threshold' => 'string',
            'packaging_factor' => 'string',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'product_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(InventoryContainer::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovementLedger::class, 'product_id');
    }

    public function openContainers(): HasMany
    {
        return $this->hasMany(InventoryContainer::class, 'product_id')
            ->where('status', ContainerStatus::Open);
    }

    public function sealedContainers(): HasMany
    {
        return $this->hasMany(InventoryContainer::class, 'product_id')
            ->where('status', ContainerStatus::Sealed);
    }

    /**
     * Calculate total physical base unit balance in stock using BCMath.
     */
    public function calculateTotalStock(): string
    {
        $containers = $this->containers()
            ->whereIn('status', [ContainerStatus::Open, ContainerStatus::Sealed])
            ->get();

        $total = '0.0000';
        foreach ($containers as $container) {
            $total = bcadd($total, (string) $container->current_base_balance, 4);
        }

        return $total;
    }
}
