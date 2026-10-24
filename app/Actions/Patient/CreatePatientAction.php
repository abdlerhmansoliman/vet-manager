<?php

declare(strict_types=1);

namespace App\Actions\Patient;

use App\DTOs\Patient\CreatePatientDTO;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

class CreatePatientAction
{
    /**
     * Register a new patient under an existing client.
     *
     * @throws ValidationException
     */
    public function execute(CreatePatientDTO $dto): Patient
    {
        if ($dto->microchipNumber !== null) {
            $duplicate = Patient::query()
                ->where('microchip_number', $dto->microchipNumber)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'microchip_number' => ['A patient with this microchip number is already registered.'],
                ]);
            }
        }

        return Patient::create([
            'client_id' => $dto->clientId,
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
            'current_weight_kg' => $dto->currentWeightKg,
            'weight_recorded_at' => $dto->currentWeightKg !== null ? now() : null,
            'history' => $dto->history,
            'notes' => $dto->notes,
            'status' => $dto->status,
            'allergies' => $dto->allergies,
        ]);
    }
}
