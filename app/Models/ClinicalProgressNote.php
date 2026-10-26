<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ClinicalProgressNote extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'clinical_progress_notes';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
            'temperature_c' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'heart_rate_bpm' => 'integer',
            'respiratory_rate' => 'integer',
            'systolic_bp' => 'integer',
            'diastolic_bp' => 'integer',
        ];
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'clinical_case_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    /**
     * Visit and progress note examination images (ultrasounds, radiographs, photos).
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }
}
