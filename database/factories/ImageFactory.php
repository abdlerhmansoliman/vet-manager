<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClinicalProgressNote;
use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'imageable_type' => ClinicalProgressNote::class,
            'imageable_id' => ClinicalProgressNote::factory(),
            'url' => 'https://storage.vetmanager.io/tenants/images/'.fake()->uuid().'.jpg',
            'title' => fake()->sentence(3),
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => fake()->numberBetween(100000, 2500000),
        ];
    }
}
