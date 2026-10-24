<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaseStatus;
use App\Enums\PatientGender;
use App\Enums\PatientStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'patients';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'client_id',
        'name_or_tag',
        'species',
        'breed',
        'gender',
        'age',
        'date_of_birth',
        'is_approximate_age',
        'microchip_number',
        'ear_tag_id',
        'coat_color_markings',
        'current_weight_kg',
        'weight_recorded_at',
        'history',
        'notes',
        'status',
        'deceased_at',
        'allergies',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => PatientGender::class,
            'status' => PatientStatus::class,
            'age' => 'integer',
            'date_of_birth' => 'date',
            'is_approximate_age' => 'boolean',
            'weight_recorded_at' => 'datetime',
            'deceased_at' => 'datetime',
            'allergies' => 'array',
            'current_weight_kg' => 'string',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(ClinicalCase::class, 'patient_id');
    }

    public function activeCases(): HasMany
    {
        return $this->hasMany(ClinicalCase::class, 'patient_id')
            ->whereIn('status', [
                CaseStatus::Open,
                CaseStatus::UnderObservation,
                CaseStatus::CriticalCare,
            ]);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'patient_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    public function isActive(): bool
    {
        return $this->status === PatientStatus::Active;
    }

    public function isDeceased(): bool
    {
        return $this->status === PatientStatus::Deceased;
    }
}
