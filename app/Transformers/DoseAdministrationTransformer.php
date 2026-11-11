<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\DoseAdministration;

class DoseAdministrationTransformer extends Transformer
{
    /**
     * @param  DoseAdministration  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'case_id' => $item->case_id,
            'patient_id' => $item->patient_id,
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'base_unit' => $item->product?->base_unit?->value,
            'dose_amount_base_units' => (string) $item->dose_amount_base_units,
            'route_administered' => $item->route_administered,
            'clinical_notes' => $item->clinical_notes,
            'administered_by' => [
                'id' => $item->administeredBy?->id,
                'name' => $item->administeredBy?->name,
                'email' => $item->administeredBy?->email,
            ],
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
