<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use App\Services\Tenancy\TenantStaffSyncService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'pin',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    /**
     * Bootstrap the model and its events.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            app(TenantStaffSyncService::class)->syncUser($user);
        });

        static::deleted(function (User $user): void {
            app(TenantStaffSyncService::class)->deleteUser($user);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'role' => StaffRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Clinical veterinarian profile (specialty, license, bio, availability).
     */
    public function profile(): HasOne
    {
        return $this->hasOne(VeterinarianProfile::class, 'user_id');
    }

    /**
     * Verify a 4-digit PIN for controlled substance witnessing.
     */
    public function verifyPin(string $pin): bool
    {
        if ($this->pin === null || $this->pin === '') {
            return false;
        }

        return Hash::check($pin, $this->pin);
    }
}
