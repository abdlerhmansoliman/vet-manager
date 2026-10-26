<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\ClinicalProgressNote;

class ClinicalProgressNoteTransformer extends Transformer
{
    /**
     * @param  ClinicalProgressNote  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'clinical_case_id' => $item->clinical_case_id,
            'vet_id' => $item->vet_id,
            'veterinarian' => $item->relationLoaded('veterinarian') ? StaffTransformer::item($item->veterinarian) : null,
            'temperature_c' => $item->temperature_c !== null ? (float) $item->temperature_c : null,
            'heart_rate_bpm' => $item->heart_rate_bpm,
            'respiratory_rate' => $item->respiratory_rate,
            'systolic_bp' => $item->systolic_bp,
            'diastolic_bp' => $item->diastolic_bp,
            'mucous_membrane' => $item->mucous_membrane,
            'weight_kg' => $item->weight_kg !== null ? (float) $item->weight_kg : null,
            'subjective_notes' => $item->subjective_notes,
            'objective_notes' => $item->objective_notes,
            'assessment_notes' => $item->assessment_notes,
            'plan_notes' => $item->plan_notes,
            'is_signed' => (bool) $item->is_signed,
            'signed_at' => $item->signed_at?->toIso8601String(),
            'images' => $item->relationLoaded('images') ? ImageTransformer::items($item->images) : [],
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
