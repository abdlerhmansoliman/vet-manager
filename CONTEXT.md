# Repository Architectural Context

## 1. Tech Stack & Versions
- **Backend Framework:** PHP 8.3, Laravel framework 13 (v13.37.0)
- **Database:** MySQL 8 (database-per-tenant isolation + landlord central database)
- **Queue & Cache:** Redis (queue connection `redis`, cache store `redis` with `vetmanager_cache_` prefix)
- **Storage:** MinIO / AWS S3 compatible (`league/flysystem-aws-s3-v3 ^3.35`, bucket: `vet-media`)
- **Frontend Stack:** Vue 3 (v3.5.45), Inertia.js (v3.7 / `@inertiajs/vue3` v3.11), Tailwind CSS 4, Vite 8
- **Mobile Companion:** Flutter + Drift SQLite (offline-first companion app with bidirectional sync engine)

## 2. Directory Map
- `app/Actions/`: Single-responsibility mutation/command classes across domains (Visits, Cases, Patient, Inventory, Auth).
- `app/DTOs/`: Strongly-typed input transfer objects mapping incoming requests to action boundaries.
- `app/Enums/`: Domain enums for visit types, case priorities, inventory transaction types, and account statuses.
- `app/Http/Controllers/Api/V1/`: Versioned REST API endpoints for mobile companion and sync operations.
- `app/Http/Controllers/Portal/`: Inertia/Vue controllers powering clinic web administration.
- `app/Http/Controllers/Landlord/`: Central administrative endpoints for tenant lifecycle management.
- `app/Http/Middleware/`: Tenant resolution (`IdentifyTenant`), token scope checks (`EnsureValidTenantToken`), idempotency.
- `app/Http/Requests/`: Strict validation request rules mapped per action.
- `app/Jobs/`: Async background jobs (`ProcessSyncMutationsJob` for sync mutation ingestion, `ProvisionTenantJob`).
- `app/Models/`: Eloquent models spanning landlord entities (`Tenant`, `LandlordUser`) and tenant clinical/inventory records.
- `app/Services/Tenancy/`: Tenant resolution, dynamic database switching (`TenantManager`), user providers.
- `app/Services/Media/`: S3/MinIO media handling, upload verification, and storage quota enforcement.
- `app/Services/Inventory/`: Stock depletion, FIFO calculations, container management, and audit verification.
- `app/Transformers/`: Standardized JSON API representation layer extending `App\Transformers\Transformer`.
- `resources/js/`: Vue 3 Inertia pages (Pages/Portal, Pages/Auth), layouts, and components.
- `routes/`: Partitioned route definitions (`api.php`, `portal.php`, `central.php`, `web.php`).

## 3. Architecture & Isolation Rules
- **Multi-Tenancy Strategy:** Database-per-tenant. Central `landlord` DB tracks tenants and global credentials; each clinic operates in an isolated MySQL database switched dynamically at runtime via `TenantManager::switch()`.
- **Tenant Resolution:** Sequential resolution via `X-Tenant-ID` header (API), session `active_tenant_id` (Portal), or host subdomain. Inactive or suspended tenants are rejected.
- **Authentication:**
  - *Mobile API:* Laravel Sanctum tokens strictly scoped with `tenant:{tenant_id}` ability validated by `EnsureValidTenantToken`.
  - *Web Portal:* Session-based auth via `TenantUserProvider` with Argon2id passwords and tenant selection.
- **Timezone Conventions:** Application timezone is globally `UTC`. All persisted dates and API timestamps must be ISO 8601 strings (`toIso8601String()`).
- **API Response Pattern:** Standard `{"data": ...}` envelope. Raw model serialization is forbidden; all endpoints must transform output using dedicated `Transformer` classes.

## 4. Core Domain Entities
- `Tenant`: Central landlord entity managing tenant database credentials, subscription status, and storage quotas.
- `LandlordUser`: Central administrative account with system-level control across all clinics.
- `User`: Clinic veterinarian or staff account in tenant DB; links to `VeterinarianProfile` and clinical activity.
- `Client`: Pet owner entity; belongs to tenant DB, has many `Patient` records.
- `Species`: Reference entity for animal classification and breed structures.
- `Patient`: Demographic animal profile; belongs to `Client` and `Species`, has many `ClinicalCase` records.
- `ClinicalCase`: Medical episode umbrella; belongs to `Patient`, has many `Visit` and `Diagnosis` records.
- `Diagnosis`: Disease/condition classification; belongs to `ClinicalCase` with provisional/confirmed state.
- `Visit`: Clinical encounter container; belongs to `ClinicalCase`, has many `ClinicalProgressNote`, `DoseAdministration`, and `Image` records.
- `ClinicalProgressNote`: SOAP exam record (Subjective, Objective, Assessment, Plan); belongs to `Visit`, authored by `User`.
- `DoseAdministration`: Administered treatment record; belongs to `Visit`, links to `InventoryBatch` and `InventoryProduct`.
- `Image`: Diagnostic media asset; belongs exclusively to `Visit`, stored in MinIO S3 with path/metadata.
- `InventoryProduct`: Medication or medical supply catalog definition; has many `InventoryBatch` records.
- `InventoryBatch`: Stock batch with lot number, expiry date, and unit cost; belongs to `InventoryProduct` and `InventoryContainer`.
- `InventoryContainer`: Physical clinic storage location (room, shelf, fridge); contains `InventoryBatch` items.
- `InventoryMovementLedger`: Immutable audit log recording additions, consumptions, and waste events.

## 5. Conventions & Constraints
- **Strict 3-Tier Clinical Hierarchy:** Level 1 (Patient = Demographics/Cases) -> Level 2 (Case = Episode/Visits) -> Level 3 (Visit = SOAP, Doses, Diagnostic Media). Zero visits/media directly on Patient; zero media directly on Case.
- **Full-Stack Parity & Bidirectional Sync:** Eloquent schemas must mirror Flutter Drift SQLite 1:1. Delta pull via `SyncController`; offline mutation push ingested asynchronously via `ProcessSyncMutationsJob` preserving client UUIDs.
- **Service/Action Pattern:** Controllers must remain thin, delegating write logic to `App\Actions\*` classes taking `App\DTOs\*`.
- **Background Job Tenancy:** Tenant-bound jobs must explicitly set tenant connection via `TenantManager::switch()` before querying/mutating tenant data.
- **Medical Record Immutability:** Once signed (`is_signed = true`), clinical visits and notes cannot be mutated; inventory movement ledgers are strictly append-only.
- **Storage Quota Enforcement:** Media uploads must validate tenant `storage_used_bytes` against `storage_limit_bytes` before persisting to MinIO.
