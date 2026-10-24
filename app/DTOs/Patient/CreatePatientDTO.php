<?php

declare(strict_types=1);

namespace App\DTOs\Patient;

use App\Enums\PatientGender;
use App\Enums\PatientStatus;

final readonly class CreatePatientDTO
{
    public function __construct(
        public string $clientId,
        public string $nameOrTag,
        public string $species,
        public PatientGender $gender,
        public ?string $breed = null,
        public ?int $age = null,
        public ?string $dateOfBirth = null,
        public bool $isApproximateAge = false,
        public ?string $microchipNumber = null,
        public ?string $earTagId = null,
        public ?string $coatColorMarkings = null,
        public ?string $currentWeightKg = null,
        public ?string $history = null,
        public ?string $notes = null,
        public PatientStatus $status = PatientStatus::Active,
        /** @var list<string>|null */
        public ?array $allergies = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $gender = $data['gender'] instanceof PatientGender
            ? $data['gender']
            : PatientGender::from((string) $data['gender']);

        $status = isset($data['status'])
            ? ($data['status'] instanceof PatientStatus
                ? $data['status']
                : PatientStatus::from((string) $data['status']))
            : PatientStatus::Active;

        return new self(
            clientId: (string) $data['client_id'],
            nameOrTag: trim((string) $data['name_or_tag']),
            species: trim((string) $data['species']),
            gender: $gender,
            breed: isset($data['breed']) ? trim((string) $data['breed']) : null,
            age: isset($data['age']) ? (int) $data['age'] : null,
            dateOfBirth: isset($data['date_of_birth']) ? (string) $data['date_of_birth'] : null,
            isApproximateAge: isset($data['is_approximate_age']) ? (bool) $data['is_approximate_age'] : false,
            microchipNumber: isset($data['microchip_number']) ? (string) $data['microchip_number'] : null,
            earTagId: isset($data['ear_tag_id']) ? (string) $data['ear_tag_id'] : null,
            coatColorMarkings: isset($data['coat_color_markings']) ? (string) $data['coat_color_markings'] : null,
            currentWeightKg: isset($data['current_weight_kg']) ? (string) $data['current_weight_kg'] : null,
            history: isset($data['history']) ? (string) $data['history'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            status: $status,
            allergies: isset($data['allergies']) ? (array) $data['allergies'] : null,
        );
    }
}
