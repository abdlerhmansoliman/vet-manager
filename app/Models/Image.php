<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'images';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'imageable_type',
        'imageable_id',
        'url',
        'title',
        'mime_type',
        'file_size_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * Get the owning imageable model (User, ClinicalCase, Patient, etc.).
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
