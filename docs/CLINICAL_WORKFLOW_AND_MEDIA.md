# VetManager Backend — Clinical Hierarchy & MinIO S3 Media Integration

> **Notice for Developers & AI Agents**:
> This document specifies the backend architectural contract for Clinical Cases, Visits, and MinIO S3 Media Storage in **VetManager Laravel**. Any AI assistant or developer working on the API or web admin MUST follow these specifications.

---

## 1. Relational Model & Hierarchy

The backend implements the strict 3-tier clinical record model:

```
Tenant
  └── Patient (Animal Profile)
        └── ClinicalCase (Medical Problem Umbrella: e.g. "Leg Fracture")
              ├── Visit 1 (Initial Consultation Encounter)
              │     ├── ClinicalProgressNote (type: 'soap')
              │     ├── DoseAdministration (High-precision pharmacy inventory usage)
              │     └── Images (Polymorphic: imageable_type = 'visits', imageable_id = visit_id)
              │
              ├── Visit 2 (Follow-up Checkup Encounter)
              │     └── Images (Post-treatment X-Rays/Photos)
              │
              └── Visit N (Discharge)
```

---

## 2. MinIO S3 Architecture & Presigned URLs

### 2.1 Storage Configuration
All media files (radiographs, ultrasound, cytology, wound photos) are strictly stored on **MinIO (S3-compatible)** object storage. Local disk storage (`public` disk / `storage:link`) is **strictly prohibited**.

- **MinIO Bucket**: `vet-media`
- **Driver**: `Storage::disk('s3')`
- **S3 Object Path Convention**:
  ```
  media/{tenant_id}/cases/{case_id}/visits/{visit_id}/{uuid}.{ext}
  ```

### 2.2 Presigned Direct Upload Pipeline
To eliminate server CPU/memory bottlenecks from binary photo streaming, clients upload directly to MinIO S3 via AWS Signature v4 Presigned URLs:

#### Endpoint 1: Request Presigned URL
`POST /api/v1/cases/{caseId}/media/presigned-url`

- **Headers**:
  - `Authorization: Bearer <token>`
  - `X-Tenant-ID: <tenant_id>`
- **Request Body**:
  ```json
  {
    "filename": "xray_foreleg.webp",
    "content_type": "image/webp",
    "visit_id": "019313ea-7e23-7000-8000-000000000001",
    "title": "Lateral Forelimb Radiograph"
  }
  ```
- **Response**:
  ```json
  {
    "message": "Presigned MinIO S3 upload URL generated successfully.",
    "data": {
      "upload_url": "http://10.0.3.2:9000/vet-media/media/...?...X-Amz-Signature=...",
      "file_url": "http://10.0.3.2:9000/vet-media/media/...",
      "headers": {
        "Host": "127.0.0.1:9000"
      },
      "key": "media/...",
      "filename": "uuid.webp"
    }
  }
  ```
- **Offline-First Tolerance**:
  - The route authorizes via the parent `{caseId}`.
  - If `visit_id` was created offline on mobile and does not yet exist in MySQL, the endpoint still issues the presigned S3 URL without returning a 404!

#### Endpoint 2: Direct Binary PUT Stream (Client to MinIO)
The mobile app streams binary bytes directly to `upload_url`:
- **HTTP Method**: `PUT`
- **Headers**: `Content-Type: image/webp`, `Content-Length: <bytes>`

#### Endpoint 3: Confirm Upload
`POST /api/v1/cases/{caseId}/media/confirm`

- **Request Body**:
  ```json
  {
    "file_url": "http://10.0.3.2:9000/vet-media/media/...",
    "visit_id": "019313ea-7e23-7000-8000-000000000001",
    "image_id": "019313ea-...",
    "title": "Lateral Forelimb Radiograph",
    "file_size_bytes": 1048576,
    "mime_type": "image/webp"
  }
  ```
- **Action**: Persists the polymorphic `Image` row in MySQL (`imageable_type = 'visits'`, `imageable_id = visit_id`).

---

## 3. Database Schema Reference

### `cases` Table
- `id` (UUID v7, primary key)
- `tenant_id` (UUID foreign key)
- `case_number` (string, e.g. `CAS-2026-0042`)
- `patient_id` (UUID foreign key)
- `chief_complaint` (text)
- `priority` (enum: `routine`, `urgent`, `critical`)
- `status` (enum: `open`, `in_progress`, `closed`)
- `admitted_at`, `discharged_at` (timestamps)

### `visits` Table
- `id` (UUID v7, primary key)
- `tenant_id` (UUID foreign key)
- `case_id` (UUID foreign key to `cases`)
- `patient_id` (UUID foreign key to `patients`)
- `title` (string, e.g. "Initial Consultation", "Follow-up Visit")
- `visit_type` (enum: `consultation`, `follow_up`, `emergency`, `surgery`)
- `status` (enum: `open`, `completed`, `cancelled`)
- `is_signed` (boolean)
- `signed_at` (timestamp)
- `signed_by` (string)
- `visit_date` (timestamp)

### `images` Table (Polymorphic)
- `id` (UUID primary key)
- `imageable_type` (string: `'visits'` or `'cases'`)
- `imageable_id` (string: `visit_id` or `case_id`)
- `url` (string: MinIO canonical S3 URL)
- `title` (string)
- `file_size_bytes` (integer)
- `mime_type` (string)

---

## 4. Visit CRUD API Specifications

### 4.1 List Visits for Case
`GET /api/v1/cases/{caseId}/visits`
- **Response**: List of visits belonging to this clinical case ordered by `visit_date DESC`.

### 4.2 Create Visit
`POST /api/v1/cases/{caseId}/visits`
- **Headers**:
  - `Authorization: Bearer <token>`
  - `X-Tenant-ID: <tenant_id>`
  - `Idempotency-Key: <uuid_v7>`
- **Request Body**:
  ```json
  {
    "title": "Initial Consultation",
    "visit_type": "consultation",
    "visit_date": "2026-11-10T14:30:00Z",
    "reason_for_visit": "Persistent limping on right foreleg"
  }
  ```
- **Validation**:
  - `title`: `required|string|max:255`
  - `visit_type`: `required|string|in:consultation,follow_up,emergency,surgery`
  - `visit_date`: `nullable|date`
  - `reason_for_visit`: `nullable|string`
- **Response `201 Created`**: Returns created `VisitTransformer` payload.

### 4.3 Get Visit Details
`GET /api/v1/visits/{visitId}`
- **Response `200 OK`**: Returns visit details, SOAP progress notes, doses administered, and attached MinIO images.

### 4.4 Update Visit Details
`PUT /api/v1/visits/{visitId}`
- **Headers**:
  - `Authorization: Bearer <token>`
  - `X-Tenant-ID: <tenant_id>`
  - `Idempotency-Key: <uuid_v7>`
- **Request Body**:
  ```json
  {
    "title": "Follow-up Checkup & Suture Removal",
    "visit_type": "follow_up",
    "reason_for_visit": "Splint removal and wound assessment"
  }
  ```
- **Constraint**: Cannot modify a visit once it has been signed and locked (`is_signed = true`).
- **Response `200 OK`**: Returns updated visit object.

---

## 5. Universal 3-Level Edit Capabilities

| Level | Entity | Modifiable Attributes | Endpoint | Lock / Immutability Rule |
| :--- | :--- | :--- | :--- | :--- |
| **Level 1** | **Patient Profile** | Name, species, breed, DOB, weight, microchip, allergies JSON, client owner | `PUT /api/v1/patients/{id}` | Editable at any time |
| **Level 2** | **Clinical Case** | Chief complaint, provisional/final diagnoses, triage priority, encounter status | `PUT /api/v1/cases/{id}` | Editable at any time; preserves medical umbrella history |
| **Level 3** | **Visit Encounter** | Title, visit type, reason for visit | `PUT /api/v1/visits/{id}` | Editable only BEFORE record signing; locked upon signing |

---

## 6. Bidirectional Sync Engine Parity

- **Delta Pull (`GET /api/v1/sync/pull`)**:
  - `SyncController.php` queries `visits` modified since `since` timestamp:
    ```php
    'visits' => Visit::where('tenant_id', $tenantId)
        ->where('updated_at', '>', $since)
        ->get()
    ```
- **Mutation Ingestion (`POST /api/v1/sync/push`)**:
  - `ProcessSyncMutationsJob.php` processes `visit` / `visits` mutation items.
  - **Self-Healing Fallback**: If an offline client pushes a visit referencing a `case_id` not yet created in the cloud, the job automatically creates an umbrella `ClinicalCase` to guarantee zero data loss.

