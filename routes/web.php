<?php

declare(strict_types=1);

use App\Http\Controllers\Landlord\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Central / Landlord Scope)
|--------------------------------------------------------------------------
|
| These routes handle the platform-wide Landlord Administration interface,
| managing tenant lifecycle, clinic provisioning, subscription tiers,
| and central infrastructure metrics.
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('landlord.dashboard');
Route::post('/tenants', [DashboardController::class, 'store'])->name('landlord.tenants.store');
Route::get('/tenants/{tenant}', [DashboardController::class, 'show'])->name('landlord.tenants.show');
Route::put('/tenants/{tenant}', [DashboardController::class, 'update'])->name('landlord.tenants.update');
Route::patch('/tenants/{tenant}/status', [DashboardController::class, 'toggleStatus'])->name('landlord.tenants.toggle-status');
Route::post('/tenants/{tenant}/migrate', [DashboardController::class, 'migrate'])->name('landlord.tenants.migrate');
Route::delete('/tenants/{tenant}', [DashboardController::class, 'destroy'])->name('landlord.tenants.destroy');
