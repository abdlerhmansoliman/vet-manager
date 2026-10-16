<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VeterinarianProfile;
use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantStaffSeeder extends Seeder
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    public function run(): void
    {
        $alphavet = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $equinecare = Tenant::query()->where('slug', 'equinecare')->firstOrFail();

        // ─── Seed Alpha Pet Hospital ───
        $this->tenantManager->switch($alphavet);

        $john = User::query()->firstOrCreate(
            ['email' => 'john.miller@alphavet.com'],
            [
                'name' => 'Dr. John Miller',
                'password' => Hash::make('password123'),
                'role' => StaffRole::Owner,
                'phone' => '+1-555-0101',
                'pin' => Hash::make('1234'),
                'is_active' => true,
            ]
        );

        VeterinarianProfile::query()->firstOrCreate(
            ['user_id' => $john->id],
            [
                'job_title' => 'Chief Medical Officer',
                'specialty' => 'Small Animal Surgery',
                'license_number' => 'VET-US-9901',
                'date_of_birth' => '1980-05-12',
                'bio' => '20 years of companion animal surgical experience.',
                'is_available' => true,
            ]
        );

        $sarahAlpha = User::query()->firstOrCreate(
            ['email' => 'sarah.connor@vetservices.com'],
            [
                'name' => 'Dr. Sarah Connor',
                'password' => Hash::make('password123'),
                'role' => StaffRole::FieldDoctor,
                'phone' => '+1-555-0202',
                'pin' => Hash::make('5678'),
                'is_active' => true,
            ]
        );

        VeterinarianProfile::query()->firstOrCreate(
            ['user_id' => $sarahAlpha->id],
            [
                'job_title' => 'Ambulatory Equine Specialist',
                'specialty' => 'Equine & Farm Medicine',
                'license_number' => 'VET-US-8842',
                'date_of_birth' => '1988-11-20',
                'bio' => 'Equine sports medicine and farm reproductive specialist.',
                'is_available' => true,
            ]
        );

        $this->tenantManager->reset();

        // ─── Seed Equine & Farm Ambulatory Care ───
        $this->tenantManager->switch($equinecare);

        $mark = User::query()->firstOrCreate(
            ['email' => 'mark.davis@equinecare.com'],
            [
                'name' => 'Dr. Mark Davis',
                'password' => Hash::make('password123'),
                'role' => StaffRole::Owner,
                'phone' => '+1-555-0303',
                'pin' => Hash::make('4321'),
                'is_active' => true,
            ]
        );

        VeterinarianProfile::query()->firstOrCreate(
            ['user_id' => $mark->id],
            [
                'job_title' => 'Clinic Director',
                'specialty' => 'Large Animal Surgery',
                'license_number' => 'VET-US-7714',
                'date_of_birth' => '1976-03-15',
                'bio' => 'Specializing in livestock herd health and equine orthopedic surgery.',
                'is_available' => true,
            ]
        );

        $sarahEquine = User::query()->firstOrCreate(
            ['email' => 'sarah.connor@vetservices.com'],
            [
                'name' => 'Dr. Sarah Connor',
                'password' => Hash::make('password123'),
                'role' => StaffRole::FieldDoctor,
                'phone' => '+1-555-0202',
                'pin' => Hash::make('5678'),
                'is_active' => true,
            ]
        );

        VeterinarianProfile::query()->firstOrCreate(
            ['user_id' => $sarahEquine->id],
            [
                'job_title' => 'Visiting Equine Specialist',
                'specialty' => 'Equine & Farm Medicine',
                'license_number' => 'VET-US-8842',
                'date_of_birth' => '1988-11-20',
                'bio' => 'Visiting specialist for equine field emergencies and performance horses.',
                'is_available' => true,
            ]
        );

        $this->tenantManager->reset();
    }
}
