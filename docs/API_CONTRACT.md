# VetManager: Complete RESTful API Specification (v1)

| Document Metadata | Specification Detail |
| :--- | :--- |
| **System** | VetManager (Multi-Tenant B2B SaaS for Veterinary Clinics & Hospitals) |
| **Status** | Production-Grade API Contract Specification (v1.0.0) |
| **Protocol** | RESTful HTTPS, JSON API, Bearer Token Authentication |
| **Base URL** | `https://api.vetmanager.io/api/v1` (or `https://{tenant-slug}.vetmanager.io/api/v1`) |
| **Document Path** | `/docs/API_CONTRACT.md` |

---

## 1. Global API Invariants & Conventions

### 1.1 Standard HTTP Headers
Every incoming API request must adhere to standard HTTP header specifications:

| Header Name | Type | Requirement | Description |
| :--- | :--- | :--- | :--- |
| `Content-Type` | `string` | Required on `POST`/`PUT`/`PATCH` | Must be `application/json` |
| `Accept` | `string` | Required | Must be `application/json` |
| `Authorization` | `string` | Required (except public auth) | `Bearer <sanctum_personal_access_token>` |
| `X-Tenant-ID` | `string` | Required (if not using subdomain) | Clinic UUID or slug (e.g. `alpha-pet-hospital`) |
| `Idempotency-Key` | `string` | Required on financial/stock/dose mutations | Client-generated UUID v4/v7 to prevent replay execution |
| `X-Client-Version`| `string` | Optional / Recommended | Flutter app semver (e.g. `1.4.2+102`) |
| `X-Device-ID` | `string` | Optional | Unique hardware identifier for audit logs |

### 1.2 Idempotency Replay Mechanics
When `Idempotency-Key` is provided:
1. The server checks Redis cache key `idempotency:{tenant_id}:{key}`.
2. If currently executing, returns `409 Conflict` with `error_code: "MUTATION_IN_PROGRESS"`.
3. If previously executed within 24 hours, returns the cached HTTP status code and response payload with header:
   ```http
   Idempotent-Replay: true
   ```
4. If new, executes the database transaction, persists the `idempotency_key` in the database, caches the response in Redis for 86,400 seconds (24 hours), and returns the new resource.

### 1.3 Standard Response Formats

#### Success Envelope (Single Resource / Mutation)
```json
{
  "success": true,
  "data": {
    "id": "018f4a1c-9210-7212-a19e-e8c105658b10",
    "type": "dose_administration",
    "attributes": { ... }
  },
  "meta": {
    "timestamp": "2026-10-13T20:45:00.000000Z"
  }
}
```

#### Success Envelope (Paginated List)
```json
{
  "success": true,
  "data": [
    { ... },
    { ... }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 25,
    "total": 112,
    "timestamp": "2026-10-13T20:45:00.000000Z"
  },
  "links": {
    "first": "https://api.vetmanager.io/api/v1/cases?page=1",
    "last": "https://api.vetmanager.io/api/v1/cases?page=5",
    "prev": null,
    "next": "https://api.vetmanager.io/api/v1/cases?page=2"
  }
}
```

#### Standard Error Envelope
```json
{
  "success": false,
  "error_code": "INSUFFICIENT_STOCK",
  "message": "Available stock (1.2000 ml) is insufficient for required dose (2.5000 ml).",
  "errors": {
    "dose_amount_base_units": [
      "The requested quantity exceeds available physical stock in opened and sealed batches."
    ]
  },
  "timestamp": "2026-10-13T20:45:00.000000Z",
  "trace_id": "req_018f4a21-99af-7182-b7e1-aa11b819f4d1"
}
```

---

## 2. Authentication & Tenant Identity

### 2.1 Resolve Tenant
Resolves clinic identity, branding, and status from clinic slug or custom domain before presenting the login screen.

- **Method**: `POST`
- **Path**: `/api/v1/auth/resolve-tenant`
- **Auth**: Public (No token required)
- **Request Body**:
```json
{
  "identifier": "alpha-pet-hospital"
}
```
- **Validation**:
  * `identifier`: `required|string|max:191`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "tenant_id": "018f4a00-1111-7000-8000-000000000001",
    "name": "Alpha Pet Hospital & Equine Center",
    "slug": "alpha-pet-hospital",
    "custom_domain": "clinic.alphavet.com",
    "status": "active",
    "currency": "USD",
    "timezone": "America/New_York",
    "branding": {
      "logo_url": "https://cdn.vetmanager.io/assets/alpha-logo.png",
      "primary_color": "#1E3A8A"
    }
  }
}
```
- **Error `404 Not Found`**:
```json
{
  "success": false,
  "error_code": "TENANT_NOT_FOUND",
  "message": "No active veterinary practice matched the provided identifier."
}
```

---

### 2.2 Staff Login
Authenticates a clinic employee against the resolved tenant database and issues a scoped Sanctum personal access token.

- **Method**: `POST`
- **Path**: `/api/v1/auth/login`
- **Headers**: `X-Tenant-ID: {tenant_uuid_or_slug}`
- **Request Body**:
```json
{
  "email": "dr.smith@alphavet.com",
  "password": "CorrectHorseBatteryStaple123!",
  "device_name": "Field-iPad-Pro-A485"
}
```
- **Validation**:
  * `email`: `required|email`
  * `password`: `required|string|min:8`
  * `device_name`: `required|string|max:100`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "token": "1|8f9a2b4c6d8e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a",
    "token_type": "Bearer",
    "expires_at": "2026-11-12T20:45:00.000000Z",
    "user": {
      "id": "018f4a02-2222-7000-8000-000000000002",
      "first_name": "Sarah",
      "last_name": "Smith",
      "email": "dr.smith@alphavet.com",
      "license_number": "VET-NY-90214",
      "role": "field_doctor",
      "permissions": [
        "cases.view_assigned",
        "cases.create",
        "progress_logs.create",
        "medications.prescribe",
        "medications.administer",
        "inventory.consume",
        "media.upload",
        "sync.offline_push"
      ]
    },
    "tenant": {
      "id": "018f4a00-1111-7000-8000-000000000001",
      "name": "Alpha Pet Hospital & Equine Center",
      "slug": "alpha-pet-hospital"
    }
  }
}
```
- **Error `401 Unauthorized`**:
```json
{
  "success": false,
  "error_code": "INVALID_CREDENTIALS",
  "message": "The provided email or password does not match clinic records."
}
```

---

### 2.3 Get Authenticated Profile (`/me`)
Returns the authenticated clinician's role, permissions, and active storage quota snapshot.

- **Method**: `GET`
- **Path**: `/api/v1/auth/me`
- **Headers**:
  * `Authorization: Bearer {token}`
  * `X-Tenant-ID: {tenant_id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "018f4a02-2222-7000-8000-000000000002",
      "first_name": "Sarah",
      "last_name": "Smith",
      "role": "field_doctor",
      "email": "dr.smith@alphavet.com"
    },
    "clinic_storage": {
      "total_allocated_bytes": 10737418240,
      "current_usage_bytes": 4124981024,
      "usage_percentage": 38.41,
      "warning_active": false
    }
  }
}
```

---

## 3. Clients & Patients Lifecycle

### 3.1 List Clients
- **Method**: `GET`
- **Path**: `/api/v1/clients`
- **Query Parameters**:
  * `client_type`: `individual`, `farm_manager`, `livestock_enterprise`
  * `search`: string (matches name, phone, email, tax ID)
  * `page`: integer (default 1)
  * `per_page`: integer (default 25)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": "018f4a05-3333-7000-8000-000000000005",
      "client_type": "farm_manager",
      "name": "Oakridge Equine & Cattle Farm",
      "contact_person": "Robert Jenkins",
      "phone_primary": "+1-555-019-2831",
      "phone_secondary": "+1-555-019-2832",
      "email": "rjenkins@oakridgefarms.com",
      "tax_vat_number": "AGRI-992182-US",
      "physical_address": "8421 Old Pasture Rd, Lexington, KY",
      "gps_latitude": 38.0405837,
      "gps_longitude": -84.5037164,
      "active_patients_count": 48,
      "created_at": "2026-01-15T10:00:00.000000Z"
    }
  ],
  "meta": { "total": 1 }
}
```

---

### 3.2 Create Client
- **Method**: `POST`
- **Path**: `/api/v1/clients`
- **Request Body**:
```json
{
  "client_type": "farm_manager",
  "name": "Bluegrass Breeding Stables",
  "contact_person": "Marcus Vance",
  "phone_primary": "+1-555-022-8811",
  "email": "marcus@bluegrassbreeding.com",
  "tax_vat_number": "VAT-US-881920",
  "physical_address": "1200 Derby Lane, Louisville, KY",
  "gps_latitude": 38.252677,
  "gps_longitude": -85.758456,
  "notes": "Biosecurity gate code: #4092. Clean truck tires at wash bay."
}
```
- **Validation**:
  * `client_type`: `required|in:individual,farm_manager,livestock_enterprise`
  * `name`: `required|string|max:191`
  * `phone_primary`: `required|string|max:32`
  * `email`: `nullable|email|max:191`
  * `gps_latitude`: `nullable|numeric|between:-90,90`
  * `gps_longitude`: `nullable|numeric|between:-180,180`
- **Response `201 Created`**: Returns created client object.

---

### 3.3 List Patients
- **Method**: `GET`
- **Path**: `/api/v1/patients`
- **Query Parameters**:
  * `client_id`: UUID
  * `species`: string (e.g. `Canine`, `Equine`, `Bovine`)
  * `search`: string (matches name, microchip, ear tag, passport)
  * `status`: `active`, `deceased`, `transferred`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": "018f4a08-4444-7000-8000-000000000008",
      "client_id": "018f4a05-3333-7000-8000-000000000005",
      "name_or_tag": "Thunderbolt Flight",
      "species": "Equine",
      "breed": "Thoroughbred",
      "gender": "intact_male",
      "date_of_birth": "2021-04-12",
      "is_approximate_age": false,
      "microchip_number": "985141002938192",
      "ear_tag_id": null,
      "passport_number": "FEI-USA-90182",
      "coat_color_markings": "Bay with white blaze on forehead",
      "current_weight_kg": 512.450,
      "weight_recorded_at": "2026-10-10T09:30:00.000000Z",
      "status": "active"
    }
  ]
}
```

---

### 3.4 Create Patient
- **Method**: `POST`
- **Path**: `/api/v1/patients`
- **Request Body**:
```json
{
  "client_id": "018f4a05-3333-7000-8000-000000000005",
  "name_or_tag": "Barn Tag #B-402",
  "species": "Bovine",
  "breed": "Angus Cross",
  "gender": "intact_female",
  "date_of_birth": "2023-02-18",
  "is_approximate_age": false,
  "ear_tag_id": "US-KY-402-991",
  "microchip_number": null,
  "current_weight_kg": 645.200,
  "coat_color_markings": "Solid black"
}
```
- **Validation**:
  * `client_id`: `required|uuid|exists:tenant.clients,id`
  * `name_or_tag`: `required|string|max:100`
  * `species`: `required|string|max:64`
  * `gender`: `required|in:intact_male,neutered_male,intact_female,spayed_female,unknown`
  * `microchip_number`: `nullable|string|max:32|unique:tenant.patients,microchip_number`
  * `current_weight_kg`: `nullable|numeric|between:0.001,5000.000`
- **Response `201 Created`**: Returns created patient object.

---

## 4. Clinical Cases & Immutable Timeline Logs

### 4.1 List Cases
- **Method**: `GET`
- **Path**: `/api/v1/cases`
- **Query Parameters**:
  * `status`: `open`, `under_observation`, `critical_care`, `discharged`, `closed`
  * `case_type`: `inpatient`, `outpatient`, `field_farm_visit`
  * `assigned_to_me`: `boolean` (filters by authenticated doctor)
  * `patient_id`: UUID
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": "018f4a10-5555-7000-8000-000000000010",
      "case_number": "CAS-2026-00042",
      "case_type": "field_farm_visit",
      "status": "under_observation",
      "priority": "urgent",
      "chief_complaint": "Acute severe colic, pawing ground, elevated pulse",
      "admitted_at": "2026-10-13T14:20:00.000000Z",
      "patient": {
        "id": "018f4a08-4444-7000-8000-000000000008",
        "name_or_tag": "Thunderbolt Flight",
        "species": "Equine",
        "current_weight_kg": 512.450
      },
      "client": {
        "id": "018f4a05-3333-7000-8000-000000000005",
        "name": "Oakridge Equine & Cattle Farm",
        "phone_primary": "+1-555-019-2831"
      },
      "attending_veterinarian": {
        "id": "018f4a02-2222-7000-8000-000000000002",
        "name": "Dr. Sarah Smith"
      }
    }
  ]
}
```

---

### 4.2 Create Clinical Case
- **Method**: `POST`
- **Path**: `/api/v1/cases`
- **Headers**: `Idempotency-Key: {uuid}`
- **Request Body**:
```json
{
  "patient_id": "018f4a08-4444-7000-8000-000000000008",
  "case_type": "field_farm_visit",
  "priority": "urgent",
  "chief_complaint": "Acute colic with abdominal distention",
  "provisional_diagnosis": "Spasmodic / impaction colic"
}
```
- **Validation**:
  * `patient_id`: `required|uuid|exists:tenant.patients,id`
  * `case_type`: `required|in:inpatient,outpatient,field_farm_visit`
  * `priority`: `required|in:routine,urgent,emergency_critical`
  * `chief_complaint`: `required|string|max:255`
- **Response `201 Created`**: Returns created case object with generated `case_number`.

---

### 4.3 Get Case Chronological Timeline
Returns the immutable chronological feed merging progress notes, vital sign readings, administered dosages, diagnostic media, and surgical addenda.

- **Method**: `GET`
- **Path**: `/api/v1/cases/{id}/timeline`
- **Query Parameters**:
  * `order`: `asc` or `desc` (default `desc`)
  * `type`: optional filter (`soap_note`, `vital_signs`, `dose_administration`, `media_attachment`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": "018f4a15-6666-7000-8000-000000000015",
      "entry_type": "clinical_progress_log",
      "log_type": "vital_signs",
      "logged_at": "2026-10-13T15:00:00.000000Z",
      "author": { "id": "018f4a02-2222-7000-8000-000000000002", "name": "Dr. Sarah Smith" },
      "vitals": {
        "temperature_celsius": 38.60,
        "heart_rate_bpm": 64,
        "respiratory_rate_bpm": 24,
        "mucosal_color": "pale",
        "capillary_refill_time_sec": 2.5,
        "systolic_bp_mmhg": 110,
        "diastolic_bp_mmhg": 70,
        "pain_score": 7,
        "recorded_weight_kg": 512.450
      },
      "soap": {
        "subjective": "Horse remains restless, persistent flank watching, slight sweating behind ears.",
        "objective": "Decreased borborygmi in all 4 quadrants. Nasogastric reflux yielded 1.5L net fluid.",
        "assessment": "Moderate pain refractory to initial sedation, probable large colon pelvic flexure impaction.",
        "plan": "Administer 1.1 mg/kg IV Flunixin meglumine. Maintain IV crystalloid infusion."
      },
      "is_immutable": true,
      "addenda": []
    },
    {
      "id": "018f4a16-7777-7000-8000-000000000016",
      "entry_type": "dose_administration",
      "administered_at": "2026-10-13T15:10:00.000000Z",
      "product_name": "Flunixin Meglumine 50 mg/ml",
      "dose_amount_base_units": 11.2500,
      "unit_of_measure": "ml",
      "route": "IV",
      "administered_by": "Dr. Sarah Smith",
      "is_controlled": false,
      "witnessed_by": null
    }
  ]
}
```

---

### 4.4 Append Daily Progress Log (SOAP / Vitals)
Creates an immutable progress log. The entry is legally locked at inception.

- **Method**: `POST`
- **Path**: `/api/v1/cases/{id}/timeline/logs`
- **Headers**: `Idempotency-Key: {uuid}`
- **Request Body**:
```json
{
  "log_type": "soap_note",
  "logged_at": "2026-10-13T16:30:00Z",
  "soap_subjective": "Horse is resting comfortably, borborygmi audible in right quadrants.",
  "soap_objective": "Heart rate decreased to 42 bpm. Gums moist and pink.",
  "soap_assessment": "Resolving spasmodic impaction.",
  "soap_plan": "Recheck in 4 hours. Offer small portion of soaked mash.",
  "temperature_celsius": 38.10,
  "heart_rate_bpm": 42,
  "respiratory_rate_bpm": 14,
  "mucosal_color": "pink",
  "capillary_refill_time_sec": 1.5,
  "pain_score": 1,
  "body_condition_score": 5
}
```
- **Validation**:
  * `log_type`: `required|in:soap_note,vital_signs,examination,triage_entry,surgical_note,nursing_care,discharge_summary`
  * `temperature_celsius`: `nullable|numeric|between:30.00,45.00`
  * `heart_rate_bpm`: `nullable|integer|between:10,350`
  * `mucosal_color`: `nullable|in:pink,pale,cyanotic,icteric,hyperemic,injected,muddy`
  * `capillary_refill_time_sec`: `nullable|numeric|between:0.5,10.0`
- **Response `201 Created`**: Returns created log with `is_immutable: true` and `signed_at` timestamp.

---

### 4.5 Append Addendum to Progress Log
Allows legal corrective addenda without modifying original clinical notes.

- **Method**: `POST`
- **Path**: `/api/v1/cases/{id}/timeline/logs/{log_id}/addenda`
- **Headers**: `Idempotency-Key: {uuid}`
- **Request Body**:
```json
{
  "soap_plan": "ADDENDUM Note: Rectal palpation re-performed at 16:45 confirms pelvic flexure evacuated.",
  "reason_for_addendum": "Supplemental physical examination finding omitted from initial SOAP submission."
}
```
- **Response `201 Created`**: Returns newly created child log with `addendum_to_log_id: {log_id}`.

---

## 5. Active Medications & High-Precision Dose Administration

### 5.1 Prescribe Active Medication
- **Method**: `POST`
- **Path**: `/api/v1/cases/{id}/medications`
- **Request Body**:
```json
{
  "product_id": "018f4a20-8888-7000-8000-000000000020",
  "prescribed_dose_amount": 2.5000,
  "frequency": "q12h",
  "route": "IV",
  "start_date": "2026-10-13",
  "end_date": "2026-10-16",
  "special_instructions": "Dilute slowly in 20 ml sterile saline over 3 minutes."
}
```
- **Response `201 Created`**: Returns created active medication record.

---

### 5.2 Administer Dose (Atomic Deduction + Idempotency + Narcotic Check)
Deducts precise fractional units from physical stock via pessimistic locking (`lockForUpdate()`), enforces open containers first, and requires witness verification if the product has `is_controlled = true`.

- **Method**: `POST`
- **Path**: `/api/v1/cases/{id}/dose-administrations`
- **Headers**:
  * `Idempotency-Key: {uuid}` (Mandatory)
- **Request Body**:
```json
{
  "active_medication_id": "018f4a22-9999-7000-8000-000000000022",
  "product_id": "018f4a20-8888-7000-8000-000000000020",
  "dose_amount_base_units": 2.5000,
  "route_administered": "IV",
  "patient_weight_at_dose_kg": 512.450,
  "witness_staff_id": "018f4a03-3333-7000-8000-000000000003",
  "witness_pin": "9821",
  "clinical_notes": "Administered slowly via left jugular catheter. No adverse reaction."
}
```
- **Validation**:
  * `product_id`: `required|uuid|exists:tenant.inventory_products,id`
  * `dose_amount_base_units`: `required|numeric|gt:0`
  * `route_administered`: `required|string|max:32`
  * `witness_staff_id`: `required_if:product.is_controlled,true|nullable|uuid`
  * `witness_pin`: `required_if:product.is_controlled,true|nullable|string|size:4`
- **Execution Lifecycle**:
  1. Validates witness credentials if `is_controlled == true`.
  2. Begins DB Transaction (`FOR UPDATE`).
  3. Checks for active open container (`inventory_open_containers`).
  4. If open container exists, deducts `2.5000 ml` from it; if volume hits `0.0000`, marks status `depleted`.
  5. If no open container, executes FEFO query on batches (`inventory_batches ORDER BY expiration_date ASC`), opens container, and deducts stock.
  6. Inserts immutable record into `stock_movement_ledger`.
  7. Inserts into `dose_administrations`.
  8. Commits transaction and returns response.
- **Response `201 Created`**:
```json
{
  "success": true,
  "data": {
    "dose_administration_id": "018f4a25-aaaa-7000-8000-000000000025",
    "case_id": "018f4a10-5555-7000-8000-000000000010",
    "product_name": "Diazepam 5 mg/ml Injection",
    "dose_amount_administered": 2.5000,
    "unit": "ml",
    "is_controlled": true,
    "witnessed_by": "Dr. Marcus Reed (Lic: VET-NY-8812)",
    "container_used": {
      "id": "018f4a24-bbbb-7000-8000-000000000024",
      "tag": "VIAL-DIAZ-014",
      "remaining_volume_base_units": 7.5000
    },
    "batch_details": {
      "batch_number": "LOT-DZ-2025-08",
      "expiration_date": "2027-04-30"
    },
    "ledger_movement_id": "018f4a26-cccc-7000-8000-000000000026",
    "administered_at": "2026-10-13T16:45:00.000000Z"
  }
}
```
- **Error `422 Unprocessable Content` (Insufficient Stock)**:
```json
{
  "success": false,
  "error_code": "INSUFFICIENT_STOCK",
  "message": "Only 1.2000 ml remaining across open containers and sealed batches. Cannot dispense 2.5000 ml."
}
```
- **Error `403 Forbidden` (Narcotic Witness Invalid)**:
```json
{
  "success": false,
  "error_code": "NARCOTIC_WITNESS_VERIFICATION_FAILED",
  "message": "The witness PIN provided does not match the credentials of Dr. Marcus Reed."
}
```

---

### 5.3 Manual Stock Movement & Waste Discard
Used for receiving stock, discarded syringe waste, or physical audit reconciliation.

- **Method**: `POST`
- **Path**: `/api/v1/inventory/movements`
- **Headers**: `Idempotency-Key: {uuid}`
- **Request Body**:
```json
{
  "product_id": "018f4a20-8888-7000-8000-000000000020",
  "batch_id": "018f4a23-bbbb-7000-8000-000000000023",
  "open_container_id": "018f4a24-bbbb-7000-8000-000000000024",
  "movement_type": "wastage_discard",
  "quantity_base_units": -0.5000,
  "reason_notes": "Syringe needle bent during draw; 0.5 ml discarded into biohazard narcotic disposal."
}
```
- **Response `201 Created`**: Returns ledger snapshot and resulting balance.

---

### 5.4 Controlled Substance Regulatory Audit Report
Generates digital logbook entries for state narcotics licensing and DEA inspections.

- **Method**: `GET`
- **Path**: `/api/v1/inventory/controlled-substances/audit-report`
- **Query Parameters**:
  * `product_id`: UUID (optional filter)
  * `start_date`: `YYYY-MM-DD`
  * `end_date`: `YYYY-MM-DD`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "report_title": "Controlled Substance Dispensing & Wastage Ledger",
    "generated_at": "2026-10-13T20:45:00.000000Z",
    "entries": [
      {
        "timestamp": "2026-10-13T16:45:00.000000Z",
        "product_name": "Diazepam 5 mg/ml",
        "schedule": "Schedule IV",
        "patient_identifier": "Thunderbolt Flight (Equine #985141002938192)",
        "case_number": "CAS-2026-00042",
        "amount_dispensed": "2.5000 ml",
        "amount_wasted": "0.0000 ml",
        "balance_remaining": "7.5000 ml",
        "administered_by": "Dr. Sarah Smith",
        "witnessed_by": "Dr. Marcus Reed"
      }
    ]
  }
}
```

---

## 6. Clinical Media & Cloudflare R2 Upload Pipeline

### 6.1 Request Presigned Upload URL (Quota Guard Check)
Validates tenant storage quotas before issuing an Amazon S3 / Cloudflare R2 presigned PUT URL.

- **Method**: `POST`
- **Path**: `/api/v1/media/presign`
- **Request Body**:
```json
{
  "case_id": "018f4a10-5555-7000-8000-000000000010",
  "file_name": "thoracic_radiograph_lateral.webp",
  "mime_type": "image/webp",
  "file_size_bytes": 4829104,
  "media_type": "radiograph_xray"
}
```
- **Validation**:
  * `case_id`: `required|uuid|exists:tenant.cases,id`
  * `file_name`: `required|string|max:255`
  * `mime_type`: `required|string|in:image/webp,image/jpeg,image/png,video/mp4,application/pdf,application/dicom`
  * `file_size_bytes`: `required|integer|min:1|max:104857600` (Max 100 MB per single file)
  * `media_type`: `required|in:radiograph_xray,ultrasound,surgical_photo,clinical_wound,lab_panel_report,ecg_strip,other`
- **Quota Verification Logic**:
  * Checks `tenant_storage_usage.current_usage_bytes + file_size_bytes <= total_allocated_bytes`.
  * If usage >= 90%, response includes header `X-Storage-Quota-Warning: 90%`.
  * If usage > 100%, request is rejected with `402 Payment Required`.
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "upload_url": "https://r2.vetmanager.io/tenants/018f4a00-1111-7000/cases/018f4a10-5555/018f4a30-dddd.webp?X-Amz-Signature=...",
    "storage_object_key": "tenants/018f4a00-1111-7000/cases/018f4a10-5555/018f4a30-dddd.webp",
    "http_method": "PUT",
    "expires_at": "2026-10-13T21:00:00.000000Z",
    "required_headers": {
      "Content-Type": "image/webp"
    }
  }
}
```
- **Error `402 Payment Required` (Quota Exceeded)**:
```json
{
  "success": false,
  "error_code": "STORAGE_QUOTA_EXCEEDED",
  "message": "Storage quota limit (10.00 GB) exceeded. Purchase a +50GB storage add-on package to continue uploading media.",
  "errors": {
    "current_usage_bytes": 10737418240,
    "total_allocated_bytes": 10737418240
  }
}
```

---

### 6.2 Confirm Uploaded Media
Executed by Flutter once the direct PUT upload to Cloudflare R2 succeeds. Verifies the file directly with Cloudflare R2 via an S3 `HEAD` request, atomically increments the tenant storage counter, and records the attachment.

- **Method**: `POST`
- **Path**: `/api/v1/media/confirm`
- **Request Body**:
```json
{
  "case_id": "018f4a10-5555-7000-8000-000000000010",
  "storage_object_key": "tenants/018f4a00-1111-7000/cases/018f4a10-5555/018f4a30-dddd.webp",
  "file_name": "thoracic_radiograph_lateral.webp",
  "mime_type": "image/webp",
  "media_type": "radiograph_xray",
  "clinical_progress_log_id": "018f4a15-6666-7000-8000-000000000015",
  "metadata": {
    "width": 2048,
    "height": 1536,
    "client_compressed": true
  }
}
```
- **Response `201 Created`**:
```json
{
  "success": true,
  "data": {
    "id": "018f4a31-eeee-7000-8000-000000000031",
    "case_id": "018f4a10-5555-7000-8000-000000000010",
    "file_name": "thoracic_radiograph_lateral.webp",
    "file_size_bytes": 4829104,
    "media_type": "radiograph_xray",
    "status": "verified_active",
    "view_url": "https://media.vetmanager.io/tenants/018f4a00-1111-7000/cases/018f4a10-5555/018f4a30-dddd.webp?token=...",
    "created_at": "2026-10-13T16:55:00.000000Z"
  }
}
```

---

## 7. Case Study Export Engine (Anonymized Clinical PDF)

### 7.1 Trigger De-Identified Case Study Export
Enqueues an asynchronous report generation job that strips owner identity and compiles clinical timeline, laboratory panels, vitals charts, and radiographs into a medical report.

- **Method**: `POST`
- **Path**: `/api/v1/cases/{id}/export-case-study`
- **Request Body**:
```json
{
  "title": "Medical Management of Pelvic Flexure Impaction Colic in an Adult Thoroughbred",
  "included_sections": [
    "signalment",
    "vitals_charts",
    "soap_timeline",
    "pharmacotherapy",
    "selected_media"
  ],
  "author_notes": "Case presentation prepared for the American Association of Equine Practitioners (AAEP) annual forum."
}
```
- **Response `202 Accepted`**:
```json
{
  "success": true,
  "data": {
    "export_id": "018f4a35-ffff-7000-8000-000000000035",
    "case_id": "018f4a10-5555-7000-8000-000000000010",
    "anonymized_subject_code": "EQUINE-SUBJECT-#EQ-904",
    "status": "queued",
    "estimated_seconds": 15,
    "check_status_url": "https://api.vetmanager.io/api/v1/case-study-exports/018f4a35-ffff-7000-8000-000000000035"
  }
}
```

---

### 7.2 Check Export Status & Retrieve Download URL
- **Method**: `GET`
- **Path**: `/api/v1/case-study-exports/{export_id}`
- **Response `200 OK` (When Completed)**:
```json
{
  "success": true,
  "data": {
    "export_id": "018f4a35-ffff-7000-8000-000000000035",
    "status": "completed",
    "anonymized_subject_code": "EQUINE-SUBJECT-#EQ-904",
    "pdf_file_size_bytes": 6291456,
    "download_url": "https://r2.vetmanager.io/exports/018f4a35-ffff.pdf?X-Amz-Expires=900&X-Amz-Signature=...",
    "download_url_expires_at": "2026-10-13T21:15:00.000000Z",
    "completed_at": "2026-10-13T21:00:12.000000Z"
  }
}
```

---

## 8. Offline-First Synchronization (Ambulatory Field Farm Visits)

Designed for veterinarians performing herd visits or equine evaluations without cellular reception.

### 8.1 Sync Push (Batch Offline Ingestion)
Transmits queued mutations created offline in local SQLite/Isar. Evaluates mutations sequentially inside a single atomic database transaction per mutation.

- **Method**: `POST`
- **Path**: `/api/v1/sync/push`
- **Request Body**:
```json
{
  "client_device_id": "iPad-Field-Vet-01",
  "batch_timestamp": "2026-10-13T20:30:00Z",
  "mutations": [
    {
      "mutation_id": "018f4a40-1111-7000-8000-000000000040",
      "idempotency_key": "idemp_field_dose_018f4a40",
      "entity_type": "dose_administration",
      "action": "create",
      "client_created_at": "2026-10-13T17:15:22Z",
      "payload": {
        "case_id": "018f4a10-5555-7000-8000-000000000010",
        "product_id": "018f4a20-8888-7000-8000-000000000020",
        "dose_amount_base_units": 2.5000,
        "route_administered": "IV",
        "witness_staff_id": "018f4a03-3333-7000-8000-000000000003",
        "witness_pin": "9821"
      }
    },
    {
      "mutation_id": "018f4a41-2222-7000-8000-000000000041",
      "idempotency_key": "idemp_field_soap_018f4a41",
      "entity_type": "clinical_progress_log",
      "action": "create",
      "client_created_at": "2026-10-13T17:20:00Z",
      "payload": {
        "case_id": "018f4a10-5555-7000-8000-000000000010",
        "log_type": "vital_signs",
        "temperature_celsius": 38.30,
        "heart_rate_bpm": 40,
        "respiratory_rate_bpm": 12,
        "pain_score": 0
      }
    }
  ]
}
```
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "processed_count": 2,
    "successful_mutations": [
      {
        "mutation_id": "018f4a40-1111-7000-8000-000000000040",
        "status": "applied",
        "server_id": "018f4a25-aaaa-7000-8000-000000000025"
      },
      {
        "mutation_id": "018f4a41-2222-7000-8000-000000000041",
        "status": "applied",
        "server_id": "018f4a50-3333-7000-8000-000000000050"
      }
    ],
    "failed_mutations": []
  }
}
```

---

### 8.2 Sync Pull (Incremental Delta Sync)
Downloads all entities updated on the clinic server since the doctor's last checkpoint.

- **Method**: `GET`
- **Path**: `/api/v1/sync/pull`
- **Query Parameters**:
  * `since`: ISO 8601 UTC timestamp (e.g. `2026-10-13T12:00:00Z`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "sync_checkpoint": "2026-10-13T20:50:00.000000Z",
    "clients": { "created_or_updated": [], "deleted_ids": [] },
    "patients": { "created_or_updated": [], "deleted_ids": [] },
    "cases": { "created_or_updated": [], "deleted_ids": [] },
    "inventory_products": { "created_or_updated": [], "deleted_ids": [] }
  }
}
```
