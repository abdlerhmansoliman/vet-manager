<?php

declare(strict_types=1);

namespace App\DTOs\Cases;

use App\Enums\CasePriority;
use App\Enums\CaseType;

final readonly class OpenCaseDTO
{
    public function __construct(
        public string $patientId,
        public string $primaryVeterinarianId,
        public string $chiefComplaint,
        public CaseType $caseType = CaseType::Outpatient,
        public CasePriority $priority = CasePriority::Routine,
        public ?string $provisionalDiagnosis = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $caseType = isset($data['case_type'])
            ? ($data['case_type'] instanceof CaseType
                ? $data['case_type']
                : CaseType::from((string) $data['case_type']))
            : CaseType::Outpatient;

        $priority = isset($data['priority'])
            ? ($data['priority'] instanceof CasePriority
                ? $data['priority']
                : CasePriority::from((string) $data['priority']))
            : CasePriority::Routine;

        return new self(
            patientId: (string) $data['patient_id'],
            primaryVeterinarianId: (string) $data['primary_veterinarian_id'],
            chiefComplaint: trim((string) $data['chief_complaint']),
            caseType: $caseType,
            priority: $priority,
            provisionalDiagnosis: isset($data['provisional_diagnosis']) ? trim((string) $data['provisional_diagnosis']) : null,
        );
    }
}
