<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Tenant;

class TenantDiscoveryTransformer extends Transformer
{
    /**
     * @param  Tenant  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'logo_url' => null,
            'status' => $item->status->value,
            'plan' => $item->plan->value,
        ];
    }
}
