<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\DTOs\Cases\UpdateCaseDTO;
use App\Enums\CaseStatus;
use App\Models\ClinicalCase;

class UpdateCaseAction
{
    /**
     * Update clinical case details, status, and assigned veterinarian.
     */
    public function execute(ClinicalCase $case, UpdateCaseDTO $dto): ClinicalCase
    {
        $changes = [
            'primary_veterinarian_id' => $dto->primaryVeterinarianId,
            'chief_complaint' => $dto->chiefComplaint,
            'case_type' => $dto->caseType,
            'status' => $dto->status,
            'priority' => $dto->priority,
            'provisional_diagnosis' => $dto->provisionalDiagnosis,
            'final_diagnosis' => $dto->finalDiagnosis,
        ];

        $isDischarging = $dto->status === CaseStatus::Discharged
            && ! in_array($case->status, [CaseStatus::Discharged, CaseStatus::Closed], true);

        if ($isDischarging) {
            $changes['discharged_at'] = now();
        }

        $case->update($changes);

        return $case->refresh()->load(['patient.client', 'primaryVeterinarian', 'diagnoses']);
    }
}
