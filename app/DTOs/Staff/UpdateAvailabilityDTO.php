<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

final readonly class UpdateAvailabilityDTO
{
    public function __construct(
        public bool $isAvailable
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isAvailable: (bool) $data['is_available'],
        );
    }
}
