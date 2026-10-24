<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiagnosisStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'diagnoses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'patient_id',
        'user_id',
        'case_id',
        'diagnosis',
        'description',
        'status',
        'notes',
        'diagnosed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DiagnosisStatus::class,
            'diagnosed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }
}
