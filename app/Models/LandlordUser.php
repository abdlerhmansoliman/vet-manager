<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LandlordRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class LandlordUser extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $connection = 'landlord';

    protected $table = 'landlord_users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => LandlordRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
