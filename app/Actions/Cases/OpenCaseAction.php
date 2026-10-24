<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\DTOs\Cases\OpenCaseDTO;
use App\Models\ClinicalCase;

class OpenCaseAction
{
    /**
     * Open a new clinical case for a patient, assigning a unique case number.
     */
    public function execute(OpenCaseDTO $dto): ClinicalCase
    {
        $caseNumber = $this->generateCaseNumber();

        return ClinicalCase::create([
            'case_number' => $caseNumber,
            'patient_id' => $dto->patientId,
            'primary_veterinarian_id' => $dto->primaryVeterinarianId,
            'case_type' => $dto->caseType,
            'priority' => $dto->priority,
            'chief_complaint' => $dto->chiefComplaint,
            'provisional_diagnosis' => $dto->provisionalDiagnosis,
            'admitted_at' => now(),
        ]);
    }

    private function generateCaseNumber(): string
    {
        $prefix = 'C-'.date('Ymd');
        $last = ClinicalCase::query()
            ->where('case_number', 'like', $prefix.'-%')
            ->orderByDesc('case_number')
            ->value('case_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
