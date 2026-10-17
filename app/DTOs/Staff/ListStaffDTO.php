<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

use App\Enums\StaffRole;

final readonly class ListStaffDTO
{
    public function __construct(
        public ?StaffRole $role = null,
        public ?string $search = null,
        public ?bool $isActive = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $role = null;
        if (isset($data['role']) && $data['role'] !== '') {
            $role = $data['role'] instanceof StaffRole
                ? $data['role']
                : StaffRole::tryFrom((string) $data['role']);
        }

        $isActive = null;
        if (isset($data['is_active']) && $data['is_active'] !== '') {
            $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new self(
            role: $role,
            search: isset($data['search']) && trim((string) $data['search']) !== ''
                ? trim((string) $data['search'])
                : null,
            isActive: $isActive
        );
    }
}
