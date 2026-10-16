<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResolveTenantsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResolveTenantsRequest;
use App\Http\Resources\TenantDiscoveryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResolveTenantsController extends Controller
{
    /**
     * Resolve all active clinic tenants associated with a staff email address.
     */
    public function __invoke(ResolveTenantsRequest $request, ResolveTenantsAction $action): AnonymousResourceCollection
    {
        $tenants = $action->execute($request->toDTO());

        return TenantDiscoveryResource::collection($tenants);
    }
}
