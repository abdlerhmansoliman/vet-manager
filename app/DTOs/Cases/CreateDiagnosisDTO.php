<?php

declare(strict_types=1);

namespace App\DTOs\Cases;

use App\Enums\DiagnosisStatus;

final readonly class CreateDiagnosisDTO
{
    public function __construct(
        public string $diagnosis,
        public DiagnosisStatus $status = DiagnosisStatus::Tentative,
        public ?string $description = null,
        public ?string $notes = null,
        public ?string $diagnosedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status'])
            ? ($data['status'] instanceof DiagnosisStatus
                ? $data['status']
                : DiagnosisStatus::from((string) $data['status']))
            : DiagnosisStatus::Tentative;

        return new self(
            diagnosis: trim((string) $data['diagnosis']),
            status: $status,
            description: isset($data['description']) ? trim((string) $data['description']) : null,
            notes: isset($data['notes']) ? trim((string) $data['notes']) : null,
            diagnosedAt: isset($data['diagnosed_at']) ? (string) $data['diagnosed_at'] : null,
        );
    }
}
