# VetManager: Official Clinical Visit & Case Report PDF Implementation Plan

> **Feature**: Official Clinical Visit & Case Medical Report PDF Generator  
> **Status**: In Execution  
> **Target Scope**: Backend Laravel 11 (`/api/v1/visits/{visit}/pdf`) + Blade PDF Template + Multi-Tenant Isolation  
> **Architectural Standard**: Clean 3-Tier Clinical Encapsulation, Pure Server-Side Authoritative Generation  

---

## 1. Goal & Context

In veterinary medicine, the **Clinical Visit & Case Report** is the definitive legal medical summary provided to animal owners, referring hospitals, and insurance providers. 

This implementation provides an official server-side PDF generator that aggregates the complete Level 1 (Patient/Owner), Level 2 (Clinical Case), and Level 3 (Visit Encounter, Vitals, SOAP, Medications, and Media) data into an authoritative, beautifully formatted, multi-page PDF document.

---

## 2. Technical Stack & Dependencies

* **Package**: `barryvdh/laravel-dompdf` (v3.1.2)  
  * Why: Pure PHP, zero external headless-browser daemon overhead, minimal memory footprint, rock-solid in CI and multi-tenant Docker/Cloud environments, full UTF-8 support.
* **Rendering Engine**: Laravel Blade template styled with clean, print-optimized CSS adhering to the *Stitch Warm Clinical* palette.
* **Delivery Mechanism**: Streamed directly via HTTP with `Content-Type: application/pdf` or downloaded as an attachment, with optional caching in object storage (MinIO/S3).

---

## 3. Data Structure to Render in the PDF

| Tier / Section | Data Points Rendered | Source Models |
| :--- | :--- | :--- |
| **Clinic Branding (Header)** | Clinic Name, Slug/Code, Phone, Email, Address, System ID | `TenantManager::getTenant()` |
| **Level 1: Patient Signalment** | Name / Tag, Species, Breed, Sex, Age/DOB, Current Weight, Microchip Number / Ear Tag ID, High-risk Allergies | `Patient` |
| **Level 1: Client / Owner** | Client Name, Phone, Email, Address, Emergency Contact | `Client` |
| **Level 2: Medical Case** | Case Number, Case Type, Priority, Chief Complaint, Provisional & Final Diagnoses | `ClinicalCase`, `Diagnosis` |
| **Level 3: Visit Details** | Visit Date, Title, Encounter Type, Reason for Visit, Attending Clinician, Sign Status | `Visit`, `User` |
| **Level 3: Vitals & Biomarkers** | Temperature (°C), Heart Rate (bpm), Respiratory Rate (rpm), Blood Pressure (Systolic/Diastolic), Mucous Membranes, Weight | `ClinicalProgressNote` |
| **Level 3: SOAP Examination** | Subjective Notes, Objective Notes, Clinical Assessment, Treatment & Discharge Plan | `ClinicalProgressNote` |
| **Level 3: Administered Doses** | Medication Name, Formulation, Batch No, Dose Amount, Dose Unit, Route (PO/IM/IV), Time Administered | `DoseAdministration`, `InventoryProduct` |
| **Level 3: Media References** | Attached Radiographs, Ultrasounds, and Clinical Photos (Captions, Upload Dates) | `Image` |
| **Integrity & Verification** | Digital Lock Badge, Signing Timestamp, Authorizing Clinician Name & License | `Visit` (`is_signed`, `signed_at`, `signed_by`) |

---

## 4. Proposed Changes & Implementation Steps

### Step 1: Install Dependency
* Add `barryvdh/laravel-dompdf` via Composer:
  ```bash
  composer require barryvdh/laravel-dompdf
  ```

### Step 2: Create Blade Template
* **File**: `resources/views/pdf/clinical-visit-report.blade.php`
* Structure:
  * Print-safe CSS (A4 page format, `@page` margin, page numbering `Page X of Y`).
  * Header with clinic details and medical report title.
  * 2-column patient and client signalment cards.
  * Case summary ribbon (Case #, Chief Complaint, Diagnoses).
  * Visit encounter info & Vitals ribbon.
  * SOAP Notes sections (S, O, A, P).
  * Medication and dose administration table.
  * Diagnostic imaging registry.
  * Legal verification footer with signature stamp and lock status.

### Step 3: Implement Controller & Service
* **File**: `app/Http/Controllers/Api/V1/Visits/VisitPdfController.php`
* Method: `__invoke(Visit $visit, Request $request): Response`
* Eager load all necessary relationships (`case.patient.client`, `case.diagnoses`, `progressNotes.veterinarian`, `doseAdministrations.product`, `images`).
* Support query parameter `?stream=1` (for in-browser preview) or `?download=1` (for file download).

### Step 4: Register API Route
* **File**: `routes/api.php`
  ```php
  Route::get('/visits/{visit}/pdf', VisitPdfController::class);
  ```
* Tenant-scoped and Sanctum-protected.

### Step 5: Automated Feature Tests
* **File**: `tests/Feature/VisitPdfTest.php`
  * Test successful PDF generation for signed and unsigned visits (200 OK, `application/pdf`).
  * Test tenant isolation (cannot access visit belonging to another tenant).
  * Test 401 unauthenticated requests.
  * Test 404 for non-existent visit IDs.

### Step 6: Code Style & Review
* Run `vendor/bin/pint --format agent` to enforce code standards.
* Run full PHPUnit suite: `php artisan test --filter=VisitPdfTest`.
