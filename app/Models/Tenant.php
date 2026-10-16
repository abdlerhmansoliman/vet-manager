<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'landlord';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'database_name',
        'storage_limit_bytes',
        'storage_used_bytes',
        'status',
        'plan',
    ];

    /**
     * Bootstrap the model and its events.
     */
    protected static function booted(): void
    {
        static::saved(function (Tenant $tenant): void {
            app(TenantManager::class)->forgetCache($tenant);

            if ($tenant->wasChanged('slug') && $tenant->getOriginal('slug')) {
                app(TenantManager::class)->forgetCache((string) $tenant->getOriginal('slug'));
            }
        });

        static::deleted(function (Tenant $tenant): void {
            app(TenantManager::class)->forgetCache($tenant);
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
            'status' => TenantStatus::class,
            'plan' => TenantPlan::class,
            'storage_limit_bytes' => 'integer',
            'storage_used_bytes' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    /**
     * Determine if tenant has sufficient storage quota for an upcoming upload.
     */
    public function hasStorageAvailable(int $incomingBytes): bool
    {
        return ($this->storage_used_bytes + $incomingBytes) <= $this->storage_limit_bytes;
    }

    /**
     * Storage consumption percentage.
     */
    public function storageUsagePercentage(): float
    {
        if ($this->storage_limit_bytes <= 0) {
            return 100.0;
        }

        return round(($this->storage_used_bytes / $this->storage_limit_bytes) * 100, 2);
    }
}
