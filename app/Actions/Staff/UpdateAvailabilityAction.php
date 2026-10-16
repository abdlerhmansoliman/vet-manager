<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DTOs\Staff\UpdateAvailabilityDTO;
use App\Models\User;
use App\Models\VeterinarianProfile;

class UpdateAvailabilityAction
{
    /**
     * Update the availability status for a clinic staff member.
     */
    public function execute(User $user, UpdateAvailabilityDTO $dto): User
    {
        VeterinarianProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['is_available' => $dto->isAvailable]
        );

        return $user->load('profile');
    }
}
