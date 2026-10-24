<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\ClinicalCase;

class ClinicalCaseTransformer extends Transformer
{
    /**
     * @param  ClinicalCase  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'case_number' => $item->case_number,
            'patient_id' => $item->patient_id,
            'patient' => $item->relationLoaded('patient') ? PatientTransformer::item($item->patient) : null,
            'primary_veterinarian_id' => $item->primary_veterinarian_id,
            'primary_veterinarian' => $item->relationLoaded('primaryVeterinarian') ? StaffTransformer::item($item->primaryVeterinarian) : null,
            'case_type' => $item->case_type->value,
            'case_type_label' => $item->case_type->label(),
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'priority' => $item->priority->value,
            'priority_label' => $item->priority->label(),
            'chief_complaint' => $item->chief_complaint,
            'provisional_diagnosis' => $item->provisional_diagnosis,
            'final_diagnosis' => $item->final_diagnosis,
            'diagnoses' => $item->relationLoaded('diagnoses') ? DiagnosisTransformer::items($item->diagnoses) : [],
            'admitted_at' => $item->admitted_at?->toIso8601String(),
            'discharged_at' => $item->discharged_at?->toIso8601String(),
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
