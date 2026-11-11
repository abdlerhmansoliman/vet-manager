<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoseAdministration extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'tenant';

    protected $table = 'dose_administrations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'patient_id',
        'product_id',
        'administered_by_user_id',
        'dose_amount_base_units',
        'route_administered',
        'clinical_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dose_amount_base_units' => 'string',
        ];
    }

    public function clinicalCase(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'product_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by_user_id');
    }
}
