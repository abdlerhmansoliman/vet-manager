<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PatientGender;
use App\Enums\PatientStatus;
use App\Models\Client;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $speciesList = ['Canine', 'Feline', 'Equine', 'Bovine', 'Ovine', 'Caprine'];
        $species = fake()->randomElement($speciesList);

        return [
            'client_id' => Client::factory(),
            'name_or_tag' => fake()->firstName(),
            'species' => $species,
            'breed' => fake()->word().' Cross',
            'gender' => fake()->randomElement(PatientGender::cases()),
            'age' => fake()->numberBetween(1, 14),
            'date_of_birth' => fake()->dateTimeBetween('-8 years', '-2 months')->format('Y-m-d'),
            'is_approximate_age' => fake()->boolean(20),
            'microchip_number' => fake()->numerify('985#############'),
            'ear_tag_id' => null,
            'coat_color_markings' => fake()->safeColorName().' with markings',
            'current_weight_kg' => (string) fake()->randomFloat(3, 2.5, 45.0),
            'weight_recorded_at' => now(),
            'history' => fake()->optional()->paragraph(),
            'notes' => fake()->optional()->sentence(),
            'status' => PatientStatus::Active,
            'deceased_at' => null,
            'allergies' => fake()->optional(0.3)->randomElements(['Penicillin', 'Sulfa drugs', 'NSAIDs'], 1),
        ];
    }

    public function equine(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => 'Equine',
            'breed' => fake()->randomElement(['Thoroughbred', 'Quarter Horse', 'Arabian', 'Warmblood']),
            'current_weight_kg' => (string) fake()->randomFloat(3, 400.0, 650.0),
        ]);
    }

    public function livestock(): static
    {
        return $this->state(fn (array $attributes) => [
            'species' => fake()->randomElement(['Bovine', 'Ovine']),
            'name_or_tag' => 'Tag #'.fake()->numerify('###'),
            'ear_tag_id' => 'EAR-'.fake()->numerify('US-######'),
            'current_weight_kg' => (string) fake()->randomFloat(3, 200.0, 850.0),
        ]);
    }
}
