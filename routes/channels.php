<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.user.{userId}', function (User $user, string $tenantId, string $userId): bool {
    return (string) $user->id === (string) $userId;
});
