<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\User;

class UserTransformer extends Transformer
{
    /**
     * @param  User  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'role' => $item->role->value,
            'phone' => $item->phone,
            'is_active' => $item->is_active,
            'profile' => $item->relationLoaded('profile') && $item->profile
                ? VeterinarianProfileTransformer::item($item->profile)
                : null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
