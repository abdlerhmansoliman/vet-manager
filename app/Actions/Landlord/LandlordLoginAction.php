<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\DTOs\Landlord\LandlordLoginDTO;
use App\Models\LandlordUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LandlordLoginAction
{
    public function execute(LandlordLoginDTO $dto): LandlordUser
    {
        /** @var LandlordUser|null $user */
        $user = LandlordUser::query()->where('email', $dto->email)->first();

        if (! $user instanceof LandlordUser || ! Auth::guard('landlord')->attempt(
            ['email' => $dto->email, 'password' => $dto->password],
            $dto->remember
        )) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            Auth::guard('landlord')->logout();

            throw ValidationException::withMessages([
                'email' => ['This platform administrator account has been deactivated.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
