<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Patient;

class PatientTransformer extends Transformer
{
    /**
     * @param  Patient  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'client_id' => $item->client_id,
            'client' => $item->relationLoaded('client') ? ClientTransformer::item($item->client) : null,
            'name_or_tag' => $item->name_or_tag,
            'species' => $item->species,
            'breed' => $item->breed,
            'gender' => $item->gender->value,
            'gender_label' => $item->gender->label(),
            'age' => $item->age,
            'date_of_birth' => $item->date_of_birth?->toDateString(),
            'is_approximate_age' => $item->is_approximate_age,
            'microchip_number' => $item->microchip_number,
            'ear_tag_id' => $item->ear_tag_id,
            'coat_color_markings' => $item->coat_color_markings,
            'current_weight_kg' => $item->current_weight_kg,
            'weight_recorded_at' => $item->weight_recorded_at?->toIso8601String(),
            'history' => $item->history,
            'notes' => $item->notes,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'deceased_at' => $item->deceased_at?->toIso8601String(),
            'allergies' => $item->allergies ?? [],
            'active_cases_count' => $item->active_cases_count ?? 0,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
