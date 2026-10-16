<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContainerStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryContainer extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'inventory_containers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'product_id',
        'batch_id',
        'container_identifier',
        'initial_base_quantity',
        'current_base_balance',
        'status',
        'opened_at',
        'opened_by_user_id',
        'depleted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContainerStatus::class,
            'initial_base_quantity' => 'string',
            'current_base_balance' => 'string',
            'opened_at' => 'datetime',
            'depleted_at' => 'datetime',
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

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }
}
