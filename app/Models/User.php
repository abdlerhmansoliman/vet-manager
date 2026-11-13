<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantStaffSyncService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
        'age',
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
            'age' => 'integer',
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
     * Clinical cases where this user is the primary attending veterinarian.
     */
    public function cases(): HasMany
    {
        return $this->hasMany(ClinicalCase::class, 'primary_veterinarian_id');
    }

    /**
     * Diagnoses recorded by this veterinarian / staff member.
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'user_id');
    }

    /**
     * Polymorphic image attachment collection (profile photo, badges, credentials).
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Single primary avatar / profile photo.
     */
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    /**
     * Verify a 4-digit PIN for controlled substance witnessing with brute-force lockout.
     */
    public function verifyPin(string $pin): bool
    {
        if ($this->pin === null || $this->pin === '') {
            return false;
        }

        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->current()?->id ?? 'global';
        $lockoutKey = "pin_lockout:{$tenantId}:{$this->id}";
        $attemptsKey = "pin_attempts:{$tenantId}:{$this->id}";

        if (Cache::has($lockoutKey)) {
            throw ValidationException::withMessages([
                'pin' => ['Witness PIN is temporarily locked due to too many failed attempts. Try again in 15 minutes.'],
                'witness_pin' => ['Witness PIN is temporarily locked due to too many failed attempts. Try again in 15 minutes.'],
            ]);
        }

        if (! Hash::check($pin, $this->pin)) {
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            if ($attempts >= 5) {
                Cache::put($lockoutKey, true, now()->addMinutes(15));
                Cache::forget($attemptsKey);
                throw ValidationException::withMessages([
                    'pin' => ['Witness PIN is temporarily locked due to too many failed attempts. Try again in 15 minutes.'],
                    'witness_pin' => ['Witness PIN is temporarily locked due to too many failed attempts. Try again in 15 minutes.'],
                ]);
            }

            Cache::put($attemptsKey, $attempts, now()->addMinutes(15));

            return false;
        }

        Cache::forget($attemptsKey);
        Cache::forget($lockoutKey);

        return true;
    }
}
