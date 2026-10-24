<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateStaffStatusAction
{
    /**
     * Activate or deactivate a clinic staff member.
     */
    public function execute(User $user, bool $isActive, User $actor): User
    {
        if ($user->id === $actor->id && ! $isActive) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.'],
            ]);
        }

        $user->update(['is_active' => $isActive]);

        return $user->refresh()->load('profile');
    }
}
