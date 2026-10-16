<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\User;

class WitnessVerificationTransformer extends Transformer
{
    /**
     * @param  User  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'witness_id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'role' => $item->role->value,
            'verified' => true,
        ];
    }
}
