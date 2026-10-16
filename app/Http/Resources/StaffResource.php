<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'profile' => new VeterinarianProfileResource($this->whenLoaded('profile')),
            'active_cases_count' => 0, // Placeholder until Phase 4 (Clinical Cases)
            'capabilities' => $this->role->capabilities(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
