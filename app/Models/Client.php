<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientType;
use App\Enums\PatientStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'clients';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'client_type',
        'name',
        'contact_person',
        'phone_primary',
        'phone_secondary',
        'physical_address',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_type' => ClientType::class,
        ];
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'client_id');
    }

    public function activePatients(): HasMany
    {
        return $this->hasMany(Patient::class, 'client_id')->where('status', PatientStatus::Active);
    }
}
