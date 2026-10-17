<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\AuthController;
use App\Http\Controllers\Landlord\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central / Landlord Routes
|--------------------------------------------------------------------------
|
| Dedicated routes for platform superadministrators managing tenancy,
| infrastructure, subscriptions, and clinic provisioning.
| Loaded with prefix: /central
|
*/

// Guest Landlord Authentication Routes
Route::middleware('guest:landlord')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('landlord.login');
    Route::post('/login', [AuthController::class, 'store'])->name('landlord.login.store');
});

// Protected Landlord Superadmin Routes
Route::middleware('auth:landlord')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('landlord.logout');

    Route::get('/', [DashboardController::class, 'index'])->name('landlord.dashboard');
    Route::post('/tenants', [DashboardController::class, 'store'])->name('landlord.tenants.store');
    Route::get('/tenants/{tenant}', [DashboardController::class, 'show'])->name('landlord.tenants.show');
    Route::put('/tenants/{tenant}', [DashboardController::class, 'update'])->name('landlord.tenants.update');
    Route::patch('/tenants/{tenant}/status', [DashboardController::class, 'toggleStatus'])->name('landlord.tenants.toggle-status');
    Route::post('/tenants/{tenant}/migrate', [DashboardController::class, 'migrate'])->name('landlord.tenants.migrate');
    Route::delete('/tenants/{tenant}', [DashboardController::class, 'destroy'])->name('landlord.tenants.destroy');
});
