<?php

declare(strict_types=1);

namespace App\DTOs\Cases;

final readonly class CreateClinicalProgressNoteDTO
{
    /**
     * @param  list<array{url: string, title?: ?string, mime_type?: ?string, file_size_bytes?: ?int}>  $images
     */
    public function __construct(
        public ?string $vetId = null,
        public ?float $temperatureC = null,
        public ?int $heartRateBpm = null,
        public ?int $respiratoryRate = null,
        public ?int $systolicBp = null,
        public ?int $diastolicBp = null,
        public ?string $mucousMembrane = null,
        public ?float $weightKg = null,
        public ?string $subjectiveNotes = null,
        public ?string $objectiveNotes = null,
        public ?string $assessmentNotes = null,
        public ?string $planNotes = null,
        public array $images = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vetId: isset($data['vet_id']) ? (string) $data['vet_id'] : null,
            temperatureC: isset($data['temperature_c']) && $data['temperature_c'] !== '' ? (float) $data['temperature_c'] : null,
            heartRateBpm: isset($data['heart_rate_bpm']) && $data['heart_rate_bpm'] !== '' ? (int) $data['heart_rate_bpm'] : null,
            respiratoryRate: isset($data['respiratory_rate']) && $data['respiratory_rate'] !== '' ? (int) $data['respiratory_rate'] : null,
            systolicBp: isset($data['systolic_bp']) && $data['systolic_bp'] !== '' ? (int) $data['systolic_bp'] : null,
            diastolicBp: isset($data['diastolic_bp']) && $data['diastolic_bp'] !== '' ? (int) $data['diastolic_bp'] : null,
            mucousMembrane: isset($data['mucous_membrane']) ? trim((string) $data['mucous_membrane']) : null,
            weightKg: isset($data['weight_kg']) && $data['weight_kg'] !== '' ? (float) $data['weight_kg'] : null,
            subjectiveNotes: isset($data['subjective_notes']) ? trim((string) $data['subjective_notes']) : null,
            objectiveNotes: isset($data['objective_notes']) ? trim((string) $data['objective_notes']) : null,
            assessmentNotes: isset($data['assessment_notes']) ? trim((string) $data['assessment_notes']) : null,
            planNotes: isset($data['plan_notes']) ? trim((string) $data['plan_notes']) : null,
            images: isset($data['images']) && is_array($data['images']) ? $data['images'] : [],
        );
    }
}
