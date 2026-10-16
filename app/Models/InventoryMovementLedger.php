<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementLedger extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $table = 'inventory_movement_ledger';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'idempotency_key',
        'product_id',
        'batch_id',
        'container_id',
        'movement_type',
        'quantity',
        'balance_after',
        'reason',
        'witnessed_by_user_id',
        'recorded_by_user_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity' => 'string',
            'balance_after' => 'string',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(InventoryContainer::class, 'container_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function witnessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witnessed_by_user_id');
    }
}
