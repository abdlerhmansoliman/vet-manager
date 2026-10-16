<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DTOs\Staff\CreateStaffDTO;
use App\Models\User;
use App\Models\VeterinarianProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateStaffAction
{
    /**
     * Create a new clinic staff member and their clinical veterinarian profile.
     */
    public function execute(CreateStaffDTO $dto): User
    {
        return DB::connection('tenant')->transaction(function () use ($dto): User {
            $user = User::query()->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'role' => $dto->role,
                'phone' => $dto->phone,
                'pin' => $dto->pin !== null && $dto->pin !== '' ? Hash::make($dto->pin) : null,
                'is_active' => true,
            ]);

            VeterinarianProfile::query()->create([
                'user_id' => $user->id,
                'job_title' => $dto->jobTitle,
                'specialty' => $dto->specialty,
                'license_number' => $dto->licenseNumber,
                'date_of_birth' => $dto->dateOfBirth,
                'bio' => $dto->bio,
                'is_available' => $dto->isAvailable,
            ]);

            return $user->load('profile');
        });
    }
}
