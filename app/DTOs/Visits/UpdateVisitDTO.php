<?php

declare(strict_types=1);

namespace App\DTOs\Visits;

final readonly class UpdateVisitDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $visitType = null,
        public ?string $status = null,
        public ?string $reasonForVisit = null,
        public ?bool $isSigned = null,
        public ?string $signedBy = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: array_key_exists('title', $data) ? trim((string) $data['title']) : null,
            visitType: array_key_exists('visit_type', $data) ? (string) $data['visit_type'] : null,
            status: array_key_exists('status', $data) ? (string) $data['status'] : null,
            reasonForVisit: array_key_exists('reason_for_visit', $data) ? (is_null($data['reason_for_visit']) ? null : trim((string) $data['reason_for_visit'])) : null,
            isSigned: array_key_exists('is_signed', $data) ? (bool) $data['is_signed'] : null,
            signedBy: array_key_exists('signed_by', $data) ? (is_null($data['signed_by']) ? null : (string) $data['signed_by']) : null,
        );
    }
}
