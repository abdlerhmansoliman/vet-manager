<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\User;
use App\Models\VeterinarianProfile;

class UpdateAvailabilityAction
{
    /**
     * Update the availability status for a clinic staff member.
     */
    public function execute(User $user, bool $isAvailable): User
    {
        VeterinarianProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['is_available' => $isAvailable]
        );

        return $user->load('profile');
    }
}
