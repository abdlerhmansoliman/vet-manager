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

    public function progressNotes(): HasMany
    {
        return $this->hasMany(ClinicalProgressNote::class, 'clinical_case_id')
            ->orderByDesc('created_at');
    }

    public function doseAdministrations(): HasMany
    {
        return $this->hasMany(DoseAdministration::class, 'case_id')
            ->orderByDesc('created_at');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'case_id')
            ->orderByDesc('visit_date');
    }

    public function isOpen(): bool
    {
        return $this->status->isActive();
    }
}
