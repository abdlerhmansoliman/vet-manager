<?php

declare(strict_types=1);

namespace App\DTOs\Visits;

final readonly class CreateVisitDTO
{
    public function __construct(
        public ?string $id = null,
        public string $title = 'Visit',
        public string $visitType = 'outpatient',
        public string $status = 'open',
        public ?string $reasonForVisit = null,
        public ?string $visitDate = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && (string) $data['id'] !== '' ? (string) $data['id'] : null,
            title: isset($data['title']) && trim((string) $data['title']) !== '' ? trim((string) $data['title']) : 'Visit',
            visitType: isset($data['visit_type']) && (string) $data['visit_type'] !== '' ? (string) $data['visit_type'] : 'outpatient',
            status: isset($data['status']) && (string) $data['status'] !== '' ? (string) $data['status'] : 'open',
            reasonForVisit: isset($data['reason_for_visit']) ? trim((string) $data['reason_for_visit']) : null,
            visitDate: isset($data['visit_date']) && (string) $data['visit_date'] !== '' ? (string) $data['visit_date'] : null,
        );
    }
}
