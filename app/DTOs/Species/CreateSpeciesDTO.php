<?php

declare(strict_types=1);

namespace App\DTOs\Species;

use Illuminate\Support\Str;

readonly class CreateSpeciesDTO
{
    public function __construct(
        public string $name,
        public ?string $code = null,
        public ?string $icon = null,
        public ?string $id = null,
        public bool $isSystemDefault = false,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = isset($data['code']) && trim((string) $data['code']) !== ''
            ? Str::slug((string) $data['code'], '_')
            : Str::slug($name, '_');

        return new self(
            name: $name,
            code: $code,
            icon: isset($data['icon']) ? (string) $data['icon'] : null,
            id: isset($data['id']) ? (string) $data['id'] : null,
            isSystemDefault: (bool) ($data['is_system_default'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
