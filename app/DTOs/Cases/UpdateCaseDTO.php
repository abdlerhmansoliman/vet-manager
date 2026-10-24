<?php

declare(strict_types=1);

namespace App\DTOs\Cases;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;

final readonly class UpdateCaseDTO
{
    public function __construct(
        public string $primaryVeterinarianId,
        public string $chiefComplaint,
        public CaseType $caseType,
        public CaseStatus $status,
        public CasePriority $priority,
        public ?string $provisionalDiagnosis = null,
        public ?string $finalDiagnosis = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $caseType = $data['case_type'] instanceof CaseType
            ? $data['case_type']
            : CaseType::from((string) $data['case_type']);

        $status = $data['status'] instanceof CaseStatus
            ? $data['status']
            : CaseStatus::from((string) $data['status']);

        $priority = $data['priority'] instanceof CasePriority
            ? $data['priority']
            : CasePriority::from((string) $data['priority']);

        return new self(
            primaryVeterinarianId: (string) $data['primary_veterinarian_id'],
            chiefComplaint: trim((string) $data['chief_complaint']),
            caseType: $caseType,
            status: $status,
            priority: $priority,
            provisionalDiagnosis: isset($data['provisional_diagnosis']) ? trim((string) $data['provisional_diagnosis']) : null,
            finalDiagnosis: isset($data['final_diagnosis']) ? trim((string) $data['final_diagnosis']) : null,
        );
    }
}
