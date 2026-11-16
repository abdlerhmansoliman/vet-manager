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
        'reconciliation_deficit',
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
            'reconciliation_deficit' => 'string',
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
        $total = '0.0000';

        // 1. Primary: Sum batch balances if batches exist with current_base_balance tracked
        if ($this->batches()->whereNotNull('current_base_balance')->exists()) {
            foreach ($this->batches()->where('current_base_balance', '>', 0)->get() as $batch) {
                $total = bcadd($total, (string) $batch->current_base_balance, 4);
            }
        } else {
            // 2. Fallback: Sum physical containers for legacy records
            $containers = $this->containers()
                ->whereIn('status', [ContainerStatus::Open, ContainerStatus::Sealed])
                ->get();

            foreach ($containers as $container) {
                $total = bcadd($total, (string) $container->current_base_balance, 4);
            }
        }

        // 3. Deduct any recorded offline reconciliation deficits
        $deficit = (string) ($this->reconciliation_deficit ?? '0.0000');
        if (bccomp($deficit, '0.0000', 4) > 0) {
            $total = bcsub($total, $deficit, 4);
        }

        return $total;
    }
}
