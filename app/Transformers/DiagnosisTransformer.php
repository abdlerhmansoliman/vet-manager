<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Diagnosis;

class DiagnosisTransformer extends Transformer
{
    /**
     * @param  Diagnosis  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'case_id' => $item->case_id,
            'patient_id' => $item->patient_id,
            'recorded_by' => $item->user_id,
            'diagnosis' => $item->diagnosis,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'description' => $item->description,
            'notes' => $item->notes,
            'diagnosed_at' => $item->diagnosed_at?->toIso8601String(),
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
