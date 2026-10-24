<?php

declare(strict_types=1);

namespace App\Actions\Patient;

use App\DTOs\Patient\UpdatePatientDTO;
use App\Enums\PatientStatus;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

class UpdatePatientAction
{
    /**
     * Update an existing patient record.
     *
     * @throws ValidationException
     */
    public function execute(Patient $patient, UpdatePatientDTO $dto): Patient
    {
        if ($dto->microchipNumber !== null) {
            $duplicate = Patient::query()
                ->where('microchip_number', $dto->microchipNumber)
                ->where('id', '!=', $patient->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'microchip_number' => ['Another patient is already registered with this microchip number.'],
                ]);
            }
        }

        $changes = [
            'name_or_tag' => $dto->nameOrTag,
            'species' => $dto->species,
            'breed' => $dto->breed,
            'gender' => $dto->gender,
            'age' => $dto->age,
            'date_of_birth' => $dto->dateOfBirth,
            'is_approximate_age' => $dto->isApproximateAge,
            'microchip_number' => $dto->microchipNumber,
            'ear_tag_id' => $dto->earTagId,
            'coat_color_markings' => $dto->coatColorMarkings,
            'history' => $dto->history,
            'notes' => $dto->notes,
            'status' => $dto->status,
            'allergies' => $dto->allergies,
        ];

        if ($dto->currentWeightKg !== null && $dto->currentWeightKg !== (string) $patient->current_weight_kg) {
            $changes['current_weight_kg'] = $dto->currentWeightKg;
            $changes['weight_recorded_at'] = now();
        }

        if ($dto->status === PatientStatus::Deceased && $patient->status !== PatientStatus::Deceased) {
            $changes['deceased_at'] = now();
        }

        $patient->update($changes);

        return $patient->refresh();
    }
}
