<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DTOs\Staff\UpdateStaffStatusDTO;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateStaffStatusAction
{
    /**
     * Activate or deactivate a clinic staff member.
     */
    public function execute(User $user, UpdateStaffStatusDTO $dto, User $actor): User
    {
        if ($user->id === $actor->id && ! $dto->isActive) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.'],
            ]);
        }

        $user->update(['is_active' => $dto->isActive]);

        return $user->refresh()->load('profile');
    }
}
