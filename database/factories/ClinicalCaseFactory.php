<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalCase>
 */
class ClinicalCaseFactory extends Factory
{
    protected $model = ClinicalCase::class;

    public function definition(): array
    {
        return [
            'case_number' => 'CAS-'.date('Y').'-'.fake()->unique()->numerify('#####'),
            'patient_id' => Patient::factory(),
            'primary_veterinarian_id' => User::factory(),
            'case_type' => fake()->randomElement(CaseType::cases()),
            'status' => CaseStatus::Open,
            'priority' => fake()->randomElement(CasePriority::cases()),
            'chief_complaint' => fake()->sentence(4),
            'provisional_diagnosis' => fake()->optional()->sentence(3),
            'final_diagnosis' => null,
            'admitted_at' => now(),
            'discharged_at' => null,
        ];
    }
}
