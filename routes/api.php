<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\ResolveTenantsController;
use App\Http\Controllers\Api\V1\Auth\VerifyPinController;
use App\Http\Controllers\Api\V1\Inventory\InventoryConsumeController;
use App\Http\Controllers\Api\V1\Inventory\InventoryReceiveController;
use App\Http\Controllers\Api\V1\Inventory\InventoryStockController;
use App\Http\Controllers\Api\V1\Inventory\InventoryWasteController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Public Landlord Routes (Central Discovery)
Route::prefix('v1')->group(function (): void {
    Route::post('/auth/resolve-tenants', ResolveTenantsController::class);
});

// Tenant-Scoped Routes
Route::middleware('tenant')->group(function (): void {
    // Diagnostic / Health Route
    Route::get('/tenant/info', function (Request $request): JsonResponse {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'active_database' => DB::connection()->getDatabaseName(),
            'default_connection' => DB::getDefaultConnection(),
            'storage_limit_bytes' => $tenant->storage_limit_bytes,
            'storage_used_bytes' => $tenant->storage_used_bytes,
        ]);
    });

    Route::prefix('v1')->group(function (): void {
        // Tenant Auth Ingress
        Route::post('/auth/login', LoginController::class);

        // Authenticated Tenant Routes
        Route::middleware(['auth:sanctum', 'tenant.token'])->group(function (): void {
            Route::get('/auth/me', MeController::class);
            Route::post('/auth/logout', LogoutController::class);
            Route::post('/auth/verify-pin', VerifyPinController::class);

            // Staff & Veterinarian Profile Management (Full CRUD)
            Route::get('/staff', [StaffController::class, 'index']);
            Route::post('/staff', [StaffController::class, 'store']);
            Route::get('/staff/{user}', [StaffController::class, 'show']);
            Route::put('/staff/{user}', [StaffController::class, 'update']);
            Route::patch('/staff/{user}/status', [StaffController::class, 'updateStatus']);
            Route::patch('/staff/{user}/availability', [StaffController::class, 'updateAvailability']);
            Route::delete('/staff/{user}', [StaffController::class, 'destroy']);

            // High-Precision Fractional Inventory Engine
            Route::get('/inventory/products', [ProductController::class, 'index']);
            Route::post('/inventory/products', [ProductController::class, 'store']);
            Route::get('/inventory/products/{product}/stock', InventoryStockController::class);
            Route::post('/inventory/receive', InventoryReceiveController::class);

            // State-Modifying Inventory Movements (Protected by Idempotency Key)
            Route::middleware('idempotent')->group(function (): void {
                Route::post('/inventory/consume', InventoryConsumeController::class);
                Route::post('/inventory/waste', InventoryWasteController::class);
            });
        });
    });
});
