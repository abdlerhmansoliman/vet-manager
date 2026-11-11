<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Visit;

class VisitTransformer extends Transformer
{
    /**
     * @param  Visit  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'case_id' => $item->case_id,
            'title' => $item->title,
            'visit_type' => $item->visit_type,
            'status' => $item->status,
            'is_signed' => (bool) $item->is_signed,
            'signed_at' => $item->signed_at?->toIso8601String(),
            'signed_by' => $item->signed_by,
            'reason_for_visit' => $item->reason_for_visit,
            'visit_date' => $item->visit_date?->toIso8601String(),
            'progress_notes' => $item->relationLoaded('progressNotes')
                ? ClinicalProgressNoteTransformer::items($item->progressNotes)
                : [],
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
