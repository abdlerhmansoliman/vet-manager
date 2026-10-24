<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalCase extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'cases';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_number',
        'patient_id',
        'primary_veterinarian_id',
        'case_type',
        'status',
        'priority',
        'chief_complaint',
        'provisional_diagnosis',
        'final_diagnosis',
        'admitted_at',
        'discharged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'case_type' => CaseType::class,
            'status' => CaseStatus::class,
            'priority' => CasePriority::class,
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function primaryVeterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_veterinarian_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'case_id');
    }

    /**
     * Diagnostic and clinical case images (radiographs, wounds, ultrasounds).
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    public function isOpen(): bool
    {
        return $this->status->isActive();
    }
}
