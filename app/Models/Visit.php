<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Visit extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'visits';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'title',
        'visit_type',
        'status',
        'is_signed',
        'signed_at',
        'signed_by',
        'reason_for_visit',
        'visit_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
            'visit_date' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(ClinicalProgressNote::class, 'visit_id')
            ->orderByDesc('created_at');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
