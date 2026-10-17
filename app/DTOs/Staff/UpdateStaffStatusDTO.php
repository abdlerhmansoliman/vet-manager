<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

final readonly class UpdateStaffStatusDTO
{
    public function __construct(
        public bool $isActive
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isActive: (bool) $data['is_active']
        );
    }
}
