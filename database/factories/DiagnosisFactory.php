<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiagnosisStatus;
use App\Models\ClinicalCase;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'case_id' => ClinicalCase::factory(),
            'diagnosis' => fake()->randomElement([
                'Canine Parvoviral Enteritis',
                'Feline Lower Urinary Tract Disease (FLUTD)',
                'Equine Colic (Spasmodic)',
                'Bovine Respiratory Disease Complex',
                'Chronic Kidney Disease (Stage 2)',
                'Cruciate Ligament Rupture',
            ]),
            'description' => fake()->paragraph(),
            'status' => DiagnosisStatus::Active,
            'notes' => fake()->optional()->sentence(),
            'diagnosed_at' => now(),
        ];
    }
}
