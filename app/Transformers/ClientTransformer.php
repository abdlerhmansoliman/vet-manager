<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Client;

class ClientTransformer extends Transformer
{
    /**
     * @param  Client  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'client_type' => $item->client_type->value,
            'client_type_label' => $item->client_type->label(),
            'name' => $item->name,
            'contact_person' => $item->contact_person,
            'phone_primary' => $item->phone_primary,
            'phone_secondary' => $item->phone_secondary,
            'physical_address' => $item->physical_address,
            'notes' => $item->notes,
            'active_patients_count' => $item->active_patients_count ?? 0,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
