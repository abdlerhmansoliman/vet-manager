<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LandlordRole;
use App\Models\LandlordUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LandlordUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LandlordUser::query()->updateOrCreate(
            ['email' => 'admin@vetmanager.io'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Platform Superadmin',
                'password' => Hash::make('Password123!'),
                'role' => LandlordRole::SuperAdmin,
                'is_active' => true,
            ]
        );
    }
}
