<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\VerifyPinDTO;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyPinAction
{
    /**
     * Verify a secondary clinician's 4-digit PIN for controlled substance witnessing.
     *
     * @throws ValidationException
     */
    public function execute(VerifyPinDTO $dto): User
    {
        $query = User::query()->where('is_active', true);

        if ($dto->witnessId !== null) {
            $query->where('id', $dto->witnessId);
        } else {
            $query->where('email', $dto->witnessEmail);
        }

        $witness = $query->first();

        if (! $witness instanceof User) {
            throw ValidationException::withMessages([
                'witness' => ['Witness staff member not found or is inactive.'],
            ]);
        }

        if (! $witness->role->canWitnessControlledSubstances()) {
            throw ValidationException::withMessages([
                'witness' => ["Staff member with role '{$witness->role->value}' is not authorized to witness controlled substances."],
            ]);
        }

        if (! $witness->verifyPin($dto->pin)) {
            throw ValidationException::withMessages([
                'pin' => ['Invalid witness verification PIN.'],
            ]);
        }

        return $witness;
    }
}
