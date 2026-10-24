<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'client_type' => fake()->randomElement(ClientType::cases()),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone_primary' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),
            'physical_address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => ClientType::Individual,
            'name' => fake()->name(),
            'contact_person' => null,
        ]);
    }

    public function farm(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => ClientType::FarmManager,
            'name' => fake()->lastName().' Family Farm & Stables',
            'contact_person' => fake()->name(),
        ]);
    }
}
