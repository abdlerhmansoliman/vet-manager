<?php

declare(strict_types=1);

namespace App\Actions\Visits;

use App\DTOs\Visits\UpdateVisitDTO;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Validation\ValidationException;

class UpdateVisitAction
{
    /**
     * Update visit title, type, reason, or signing state.
     *
     * @throws ValidationException
     */
    public function execute(Visit $visit, UpdateVisitDTO $dto, ?User $actor = null): Visit
    {
        if ($visit->is_signed) {
            throw ValidationException::withMessages([
                'visit' => 'Clinical visit is signed and locked (is_signed=true). Modifications are strictly rejected.',
            ]);
        }

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

        if ($dto->isSigned) {
            $attributes['is_signed'] = true;
            $attributes['signed_at'] = now();
            $attributes['signed_by'] = $actor ? (string) $actor->id : null;
        }

        if (! empty($attributes)) {
            $visit->update($attributes);
        }

        return $visit->refresh();
    }
}
