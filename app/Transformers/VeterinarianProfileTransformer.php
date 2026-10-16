<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\VeterinarianProfile;

class VeterinarianProfileTransformer extends Transformer
{
    /**
     * @param  VeterinarianProfile  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'job_title' => $item->job_title,
            'specialty' => $item->specialty,
            'license_number' => $item->license_number,
            'date_of_birth' => $item->date_of_birth?->format('Y-m-d'),
            'bio' => $item->bio,
            'is_available' => $item->is_available,
        ];
    }
}
