<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteStaffAction
{
    /**
     * Delete a clinic staff member.
     */
    public function execute(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        $user->delete();
    }
}
