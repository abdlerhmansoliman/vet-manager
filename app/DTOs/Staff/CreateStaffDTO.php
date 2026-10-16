<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

use App\Enums\StaffRole;

final readonly class CreateStaffDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public StaffRole $role,
        public ?string $phone = null,
        public ?string $pin = null,
        public ?string $jobTitle = null,
        public ?string $specialty = null,
        public ?string $licenseNumber = null,
        public ?string $dateOfBirth = null,
        public ?string $bio = null,
        public bool $isAvailable = true
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $role = $data['role'] instanceof StaffRole
            ? $data['role']
            : StaffRole::from((string) $data['role']);

        return new self(
            name: (string) $data['name'],
            email: strtolower(trim((string) $data['email'])),
            password: (string) $data['password'],
            role: $role,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            pin: isset($data['pin']) ? (string) $data['pin'] : null,
            jobTitle: isset($data['job_title']) ? (string) $data['job_title'] : null,
            specialty: isset($data['specialty']) ? (string) $data['specialty'] : null,
            licenseNumber: isset($data['license_number']) ? (string) $data['license_number'] : null,
            dateOfBirth: isset($data['date_of_birth']) ? (string) $data['date_of_birth'] : null,
            bio: isset($data['bio']) ? (string) $data['bio'] : null,
            isAvailable: isset($data['is_available']) ? (bool) $data['is_available'] : true,
        );
    }
}
