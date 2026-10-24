<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\DTOs\Cases\CreateDiagnosisDTO;
use App\Models\ClinicalCase;
use App\Models\Diagnosis;
use App\Models\User;

class CreateDiagnosisAction
{
    /**
     * Record a new clinical diagnosis entry under a case.
     */
    public function execute(ClinicalCase $case, CreateDiagnosisDTO $dto, User $actor): Diagnosis
    {
        return Diagnosis::create([
            'patient_id' => $case->patient_id,
            'case_id' => $case->id,
            'user_id' => $actor->id,
            'diagnosis' => $dto->diagnosis,
            'status' => $dto->status,
            'description' => $dto->description,
            'notes' => $dto->notes,
            'diagnosed_at' => $dto->diagnosedAt ?? now()->toDateTimeString(),
        ]);
    }
}
