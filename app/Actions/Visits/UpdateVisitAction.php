<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\DTOs\Visits\UpdateVisitDTO;
use App\Models\Visit;

class UpdateVisitAction
{
    /**
     * Update visit title, type, reason, or signing state.
     */
    public function execute(Visit $visit, UpdateVisitDTO $dto): Visit
    {
        $attributes = [];

        if ($dto->title !== null) {
            $attributes['title'] = $dto->title;
        }

        if ($dto->visitType !== null) {
            $attributes['visit_type'] = $dto->visitType;
        }

        if ($dto->status !== null) {
            $attributes['status'] = $dto->status;
        }

        if ($dto->reasonForVisit !== null) {
            $attributes['reason_for_visit'] = $dto->reasonForVisit;
        }

        if ($dto->isSigned !== null) {
            $attributes['is_signed'] = $dto->isSigned;
            if ($dto->isSigned && ! $visit->is_signed) {
                $attributes['signed_at'] = now();
                $attributes['signed_by'] = $dto->signedBy;
            }
        } elseif ($dto->signedBy !== null) {
            $attributes['signed_by'] = $dto->signedBy;
        }

        if (! empty($attributes)) {
            $visit->update($attributes);
        }

        return $visit->refresh();
    }
}
