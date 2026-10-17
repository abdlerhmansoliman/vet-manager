<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DTOs\Staff\UpdateStaffDTO;
use App\Models\User;
use App\Models\VeterinarianProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateStaffAction
{
    /**
     * Update clinic staff details, role, and profile information.
     */
    public function execute(User $user, UpdateStaffDTO $dto): User
    {
        return DB::connection('tenant')->transaction(function () use ($user, $dto): User {
            $userAttributes = [
                'name' => $dto->name,
                'email' => $dto->email,
                'role' => $dto->role,
                'phone' => $dto->phone,
            ];

            if ($dto->password !== null && $dto->password !== '') {
                $userAttributes['password'] = Hash::make($dto->password);
            }

            if ($dto->pin !== null && $dto->pin !== '') {
                $userAttributes['pin'] = Hash::make($dto->pin);
            }

            $user->update($userAttributes);

            $profileAttributes = array_filter([
                'job_title' => $dto->jobTitle,
                'specialty' => $dto->specialty,
                'license_number' => $dto->licenseNumber,
                'date_of_birth' => $dto->dateOfBirth,
                'bio' => $dto->bio,
            ], fn ($value) => $value !== null);

            if ($dto->isAvailable !== null) {
                $profileAttributes['is_available'] = $dto->isAvailable;
            }

            if (! empty($profileAttributes)) {
                VeterinarianProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileAttributes
                );
            }

            return $user->refresh()->load('profile');
        });
    }
}
