<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\DTOs\Cases\CreateClinicalProgressNoteDTO;
use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateClinicalProgressNoteAction
{
    /**
     * Record a new clinical progress note (timeline visit / re-examination) under a clinical case.
     */
    public function execute(ClinicalCase $case, CreateClinicalProgressNoteDTO $dto, User $actor): ClinicalProgressNote
    {
        return DB::connection('tenant')->transaction(function () use ($case, $dto, $actor): ClinicalProgressNote {
            $vetId = $dto->vetId ?? $actor->id;

            /** @var ClinicalProgressNote $note */
            $note = ClinicalProgressNote::create([
                'clinical_case_id' => $case->id,
                'vet_id' => $vetId,
                'temperature_c' => $dto->temperatureC,
                'heart_rate_bpm' => $dto->heartRateBpm,
                'respiratory_rate' => $dto->respiratoryRate,
                'systolic_bp' => $dto->systolicBp,
                'diastolic_bp' => $dto->diastolicBp,
                'mucous_membrane' => $dto->mucousMembrane,
                'weight_kg' => $dto->weightKg,
                'subjective_notes' => $dto->subjectiveNotes,
                'objective_notes' => $dto->objectiveNotes,
                'assessment_notes' => $dto->assessmentNotes,
                'plan_notes' => $dto->planNotes,
                'is_signed' => false,
                'signed_at' => null,
            ]);

            // Synchronize patient's current weight if updated during this visit
            if ($dto->weightKg !== null) {
                $case->patient()->update([
                    'current_weight_kg' => (string) $dto->weightKg,
                    'weight_recorded_at' => now(),
                ]);
            }

            // Attach visit images (radiographs, ultrasound frames, wound progression photos)
            foreach ($dto->images as $imgData) {
                Image::create([
                    'imageable_type' => ClinicalProgressNote::class,
                    'imageable_id' => $note->id,
                    'url' => $imgData['url'],
                    'title' => $imgData['title'] ?? null,
                    'mime_type' => $imgData['mime_type'] ?? 'image/jpeg',
                    'file_size_bytes' => $imgData['file_size_bytes'] ?? null,
                ]);
            }

            return $note->load(['veterinarian', 'images']);
        });
    }
}
