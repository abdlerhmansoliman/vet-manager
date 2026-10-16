<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'inventory_batches';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'product_id',
        'batch_number',
        'expiration_date',
        'cost_per_base_unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'cost_per_base_unit' => 'string',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'product_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(InventoryContainer::class, 'batch_id');
    }
}
