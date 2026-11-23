<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\ClientController;
use App\Http\Controllers\Portal\ClinicalCaseController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\InventoryController;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Controllers\Portal\StaffController;
use App\Http\Controllers\Portal\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Clinic & Doctor Web Application Routes
|--------------------------------------------------------------------------
|
| Electronic medical records (EMR), patient files, clinical cases,
| SOAP visits, pharmacy stock, and staff management for veterinary clinics.
| Loaded with prefix: /portal
|
*/

// Public Ingress & Two-Step Authentication
Route::group([], function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('portal.login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::post('/login/discover', [AuthController::class, 'discover'])->name('portal.login.discover');
    Route::post('/login/select-clinic', [AuthController::class, 'clinicSelected'])->name('portal.login.clinic-selected');
    Route::post('/login/authenticate', [AuthController::class, 'authenticate'])->name('portal.login.authenticate');
});

// Protected Clinic Operations (Scoped to Active Tenant)
Route::middleware(['auth:web', 'tenant'])->group(function (): void {
    // Session Controls
    Route::post('/logout', [AuthController::class, 'destroy'])->name('portal.logout');
    Route::post('/switch-clinic/{tenant?}', [AuthController::class, 'switchClinic'])->name('portal.switch-clinic');

    // Clinical Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('portal.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Level 1: Patients Registry & Medical Profiles (Cases ONLY, no visits/media)
    Route::get('/patients', [PatientController::class, 'index'])->name('portal.patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('portal.patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('portal.patients.store');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('portal.patients.show');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('portal.patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('portal.patients.update');

    // Level 2: Clinical Cases & Problem Umbrellas (Compact Visit Tiles ONLY, no media)
    Route::get('/cases/create', [ClinicalCaseController::class, 'create'])->name('portal.cases.create');
    Route::post('/cases', [ClinicalCaseController::class, 'store'])->name('portal.cases.store');
    Route::get('/cases/{case}', [ClinicalCaseController::class, 'show'])->name('portal.cases.show');
    Route::get('/cases/{case}/edit', [ClinicalCaseController::class, 'edit'])->name('portal.cases.edit');
    Route::put('/cases/{case}', [ClinicalCaseController::class, 'update'])->name('portal.cases.update');

    // Level 3: Clinical Visits (Exclusive Workspace for SOAP, eMAR Dosing & MinIO S3 Media)
    Route::get('/visits/create', [VisitController::class, 'create'])->name('portal.visits.create');
    Route::post('/visits', [VisitController::class, 'store'])->name('portal.visits.store');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('portal.visits.show');
    Route::post('/visits/{visit}/soap', [VisitController::class, 'storeSoap'])->name('portal.visits.soap');
    Route::post('/visits/{visit}/dose', [VisitController::class, 'administerDose'])->name('portal.visits.dose');
    Route::post('/visits/{visit}/media', [VisitController::class, 'uploadMedia'])->name('portal.visits.media');
    Route::post('/visits/{visit}/sign', [VisitController::class, 'sign'])->name('portal.visits.sign');
    Route::get('/visits/{visit}/edit', [VisitController::class, 'edit'])->name('portal.visits.edit');
    Route::put('/visits/{visit}', [VisitController::class, 'update'])->name('portal.visits.update');

    // Client Directory
    Route::get('/clients', [ClientController::class, 'index'])->name('portal.clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('portal.clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('portal.clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('portal.clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('portal.clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('portal.clients.update');

    // Pharmacy & Inventory Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->name('portal.inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('portal.inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('portal.inventory.store');
    Route::get('/inventory/receive', [InventoryController::class, 'receive'])->name('portal.inventory.receive');
    Route::post('/inventory/receive', [InventoryController::class, 'storeReceive'])->name('portal.inventory.store-receive');
    Route::get('/inventory/waste', [InventoryController::class, 'waste'])->name('portal.inventory.waste');
    Route::post('/inventory/waste', [InventoryController::class, 'storeWaste'])->name('portal.inventory.store-waste');

    // Staff Management
    Route::get('/staff', [StaffController::class, 'index'])->name('portal.staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('portal.staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('portal.staff.store');
    Route::post('/staff/{user}/toggle-availability', [StaffController::class, 'toggleAvailability'])->name('portal.staff.toggle-availability');
    Route::post('/staff/{user}/toggle-duty', [StaffController::class, 'toggleAvailability'])->name('portal.staff.toggle-duty');
});
