<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\DeleteAccountController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\ResolveTenantsController;
use App\Http\Controllers\Api\V1\Auth\VerifyPinController;
use App\Http\Controllers\Api\V1\Cases\CaseDoseAdministrationController;
use App\Http\Controllers\Api\V1\Cases\ClinicalCaseController;
use App\Http\Controllers\Api\V1\Cases\ClinicalProgressNoteController;
use App\Http\Controllers\Api\V1\Cases\DiagnosisController;
use App\Http\Controllers\Api\V1\Client\ClientController;
use App\Http\Controllers\Api\V1\Inventory\InventoryConsumeController;
use App\Http\Controllers\Api\V1\Inventory\InventoryReceiveController;
use App\Http\Controllers\Api\V1\Inventory\InventoryStockController;
use App\Http\Controllers\Api\V1\Inventory\InventoryWasteController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Media\MediaController;
use App\Http\Controllers\Api\V1\Patient\PatientController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use App\Http\Controllers\Api\V1\Sync\SyncController;
use App\Http\Controllers\Api\V1\Visits\VisitController;
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
            Route::delete('/auth/account', DeleteAccountController::class);

            // Staff & Veterinarian Profile Management (Full CRUD)
            Route::get('/staff', [StaffController::class, 'index']);
            Route::post('/staff', [StaffController::class, 'store']);
            Route::get('/staff/{user}', [StaffController::class, 'show']);
            Route::put('/staff/{user}', [StaffController::class, 'update']);
            Route::patch('/staff/{user}/status', [StaffController::class, 'updateStatus']);
            Route::patch('/staff/{user}/availability', [StaffController::class, 'updateAvailability']);
            Route::delete('/staff/{user}', [StaffController::class, 'destroy']);

            // Client Registry (Full CRUD)
            Route::get('/clients', [ClientController::class, 'index']);
            Route::post('/clients', [ClientController::class, 'store']);
            Route::get('/clients/{client}', [ClientController::class, 'show']);
            Route::put('/clients/{client}', [ClientController::class, 'update']);
            Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

            // Sync Engine for Mobile Offline Persistence
            Route::get('/sync/pull', [SyncController::class, 'pull']);
            Route::post('/sync/push', [SyncController::class, 'push']);

            // Patient Registry (all patients, nested under clients for listing, flat for show/update)
            Route::get('/patients', [PatientController::class, 'all']);
            Route::get('/clients/{client}/patients', [PatientController::class, 'index']);
            Route::post('/patients', [PatientController::class, 'store']);
            Route::get('/patients/{patient}', [PatientController::class, 'show']);
            Route::put('/patients/{patient}', [PatientController::class, 'update']);

            // Clinical Cases
            Route::get('/patients/{patient}/cases', [ClinicalCaseController::class, 'index']);
            Route::post('/cases', [ClinicalCaseController::class, 'store']);
            Route::get('/cases/{case}', [ClinicalCaseController::class, 'show']);
            Route::put('/cases/{case}', [ClinicalCaseController::class, 'update']);

            // Diagnoses (nested under cases)
            Route::get('/cases/{case}/diagnoses', [DiagnosisController::class, 'index']);
            Route::post('/cases/{case}/diagnoses', [DiagnosisController::class, 'store']);

            // Clinical Visits (nested under cases for list/create, flat for show/update)
            Route::get('/cases/{case}/visits', [VisitController::class, 'index']);
            Route::post('/cases/{case}/visits', [VisitController::class, 'store']);
            Route::get('/visits/{visit}', [VisitController::class, 'show']);
            Route::put('/visits/{visit}', [VisitController::class, 'update']);

            // Clinical Progress Notes (nested under cases)
            Route::get('/cases/{case}/progress-notes', [ClinicalProgressNoteController::class, 'index']);
            Route::post('/cases/{case}/progress-notes', [ClinicalProgressNoteController::class, 'store']);
            Route::get('/cases/{case}/progress-notes/{progressNote}', [ClinicalProgressNoteController::class, 'show']);
            Route::post('/cases/{case}/progress-notes/{progressNote}/sign', [ClinicalProgressNoteController::class, 'sign']);

            // Clinical Dose Administrations (eMAR)
            Route::get('/cases/{case}/dose-administrations', [CaseDoseAdministrationController::class, 'index']);
            Route::post('/cases/{case}/dose-administrations', [CaseDoseAdministrationController::class, 'store']);

            // Diagnostic Media & X-Rays (MinIO S3)
            Route::get('/cases/{case}/images', [MediaController::class, 'indexForCase']);
            Route::post('/cases/{case}/images', [MediaController::class, 'uploadForCase']);
            Route::post('/cases/{case}/media/presigned-url', [MediaController::class, 'requestPresignedUrl']);
            Route::post('/cases/{case}/media/confirm', [MediaController::class, 'confirmUpload']);
            Route::get('/visits/{visit}/images', [MediaController::class, 'indexForVisit']);
            Route::post('/visits/{visit}/images', [MediaController::class, 'uploadForVisit']);
            Route::delete('/images/{image}', [MediaController::class, 'destroy']);

            // High-Precision Fractional Inventory Engine
            Route::get('/inventory/products', [ProductController::class, 'index']);
            Route::post('/inventory/products', [ProductController::class, 'store']);
            Route::get('/inventory/products/{product}', [ProductController::class, 'show']);
            Route::put('/inventory/products/{product}', [ProductController::class, 'update']);
            Route::delete('/inventory/products/{product}', [ProductController::class, 'destroy']);
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
