<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Species extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'species';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'code',
        'icon',
        'is_system_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
