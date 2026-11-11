<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\DTOs\Visits\CreateVisitDTO;
use App\Models\ClinicalCase;
use App\Models\Visit;
use Illuminate\Support\Str;

class CreateVisitAction
{
    /**
     * Create a new visit under a specific clinical case.
     */
    public function execute(ClinicalCase $case, CreateVisitDTO $dto): Visit
    {
        $attributes = [
            'case_id' => $case->id,
            'title' => $dto->title,
            'visit_type' => $dto->visitType,
            'status' => $dto->status,
            'reason_for_visit' => $dto->reasonForVisit,
            'visit_date' => $dto->visitDate ?? now(),
        ];

        if ($dto->id !== null) {
            $attributes['id'] = $dto->id;
        } else {
            $attributes['id'] = (string) Str::uuid();
        }

        return Visit::create($attributes);
    }
}
