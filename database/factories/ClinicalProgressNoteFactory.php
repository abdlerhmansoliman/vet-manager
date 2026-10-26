<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalProgressNote>
 */
class ClinicalProgressNoteFactory extends Factory
{
    protected $model = ClinicalProgressNote::class;

    public function definition(): array
    {
        return [
            'clinical_case_id' => ClinicalCase::factory(),
            'vet_id' => User::factory(),
            'temperature_c' => fake()->randomFloat(1, 37.5, 40.5),
            'heart_rate_bpm' => fake()->numberBetween(60, 180),
            'respiratory_rate' => fake()->numberBetween(15, 45),
            'systolic_bp' => fake()->numberBetween(100, 160),
            'diastolic_bp' => fake()->numberBetween(60, 100),
            'mucous_membrane' => fake()->randomElement(['Pink, moist', 'Pale pink', 'Slightly tacky', 'Congested']),
            'weight_kg' => fake()->randomFloat(2, 2.0, 55.0),
            'subjective_notes' => fake()->paragraph(),
            'objective_notes' => fake()->paragraph(),
            'assessment_notes' => fake()->paragraph(),
            'plan_notes' => fake()->paragraph(),
            'is_signed' => false,
            'signed_at' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_signed' => true,
            'signed_at' => now(),
        ]);
    }
}
