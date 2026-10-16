<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\VeterinarianProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VeterinarianProfile
 */
class VeterinarianProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_title' => $this->job_title,
            'specialty' => $this->specialty,
            'license_number' => $this->license_number,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'bio' => $this->bio,
            'is_available' => $this->is_available,
        ];
    }
}
