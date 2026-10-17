<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DTOs\Staff\ListStaffDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListStaffAction
{
    /**
     * List clinic staff with profile details and optional role/search filtering.
     *
     * @return Collection<int, User>
     */
    public function execute(ListStaffDTO $dto): Collection
    {
        return User::query()
            ->with('profile')
            ->when($dto->role !== null, fn ($query) => $query->where('role', $dto->role->value))
            ->when($dto->isActive !== null, fn ($query) => $query->where('is_active', $dto->isActive))
            ->when($dto->search !== null, function ($query) use ($dto): void {
                $query->where(function ($q) use ($dto): void {
                    $q->where('name', 'like', "%{$dto->search}%")
                        ->orWhere('email', 'like', "%{$dto->search}%")
                        ->orWhere('phone', 'like', "%{$dto->search}%");
                });
            })
            ->orderBy('name')
            ->get();
    }
}
