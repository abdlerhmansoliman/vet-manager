<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private ClinicalCase $case;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        // Authenticate as Dr. John Miller on AlphaPet
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $this->token = $loginResponse->json('token');
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $vet = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();

        $client = Client::factory()->create();
        $patient = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Bella',
        ]);

        $this->case = ClinicalCase::query()->create([
            'id' => (string) Str::uuid(),
            'case_number' => 'CAS-TEST-'.strtoupper(Str::random(8)),
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $vet->id,
            'case_type' => 'outpatient',
            'status' => 'open',
            'priority' => 'routine',
            'chief_complaint' => 'Abdominal swelling',
            'admitted_at' => now(),
        ]);

        $this->visit = Visit::query()->create([
            'id' => (string) Str::uuid(),
            'case_id' => $this->case->id,
            'title' => 'Initial Ultrasound Visit',
            'visit_type' => 'outpatient',
            'status' => 'open',
            'visit_date' => now(),
        ]);

        $tenantManager->reset();
    }

    public function test_can_upload_image_for_clinical_case_to_s3_minio(): void
    {
        $file = UploadedFile::fake()->image('radiograph.jpg', 800, 600);

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/cases/{$this->case->id}/images", [
                'file' => $file,
                'title' => 'Abdominal Radiograph Lateral',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Image uploaded to MinIO successfully.');
        $response->assertJsonPath('data.title', 'Abdominal Radiograph Lateral');
        $response->assertJsonPath('data.imageable_type', 'cases');
        $response->assertJsonPath('data.imageable_id', $this->case->id);

        $imageId = $response->json('data.id');
        $this->assertNotEmpty($imageId);

        // Verify retrieval endpoint
        $listResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/cases/{$this->case->id}/images");

        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'data');
    }

    public function test_can_upload_image_for_visit_encounter_to_s3_minio(): void
    {
        $file = UploadedFile::fake()->image('ultrasound.png', 640, 480);

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/visits/{$this->visit->id}/images", [
                'file' => $file,
                'title' => 'Bladder Scan Longitudinal',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Image uploaded to MinIO successfully.');
        $response->assertJsonPath('data.title', 'Bladder Scan Longitudinal');
        $response->assertJsonPath('data.imageable_type', 'visits');
        $response->assertJsonPath('data.imageable_id', $this->visit->id);

        // Verify retrieval endpoint
        $listResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/visits/{$this->visit->id}/images");

        $listResponse->assertOk();
        $listResponse->assertJsonCount(1, 'data');
    }

    public function test_can_delete_image(): void
    {
        $file = UploadedFile::fake()->image('wound.jpg');

        $uploadResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/cases/{$this->case->id}/images", [
                'file' => $file,
                'title' => 'Wound photo',
            ]);

        $imageId = $uploadResponse->json('data.id');

        $deleteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/v1/images/{$imageId}");

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('message', 'Image deleted successfully.');
    }

    public function test_can_request_presigned_upload_url_for_offline_generated_visit(): void
    {
        $offlineVisitId = (string) Str::uuid(); // Not saved in MySQL

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/cases/{$this->case->id}/media/presigned-url", [
                'filename' => 'xray_offline.webp',
                'visit_id' => $offlineVisitId,
                'content_type' => 'image/webp',
                'title' => 'Offline Encounter X-Ray',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'upload_url',
                'file_url',
                'headers',
                'key',
                'filename',
                'case_id',
                'visit_id',
            ],
        ]);
        $response->assertJsonPath('data.case_id', $this->case->id);
        $response->assertJsonPath('data.visit_id', $offlineVisitId);
        $this->assertNotEmpty($response->json('data.upload_url'));
        $this->assertNotEmpty($response->json('data.file_url'));
    }

    public function test_can_confirm_direct_upload_and_persist_image_metadata(): void
    {
        $offlineVisitId = (string) Str::uuid();
        $fileUrl = 'http://10.0.3.2:9000/vet-media/media/alphavet/cases/'.$this->case->id.'/visits/'.$offlineVisitId.'/xray.webp';

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/cases/{$this->case->id}/media/confirm", [
                'file_url' => $fileUrl,
                'visit_id' => $offlineVisitId,
                'title' => 'Confirmed Radiograph',
                'file_size_bytes' => 102400,
                'mime_type' => 'image/webp',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Image upload confirmed and recorded successfully.');
        $response->assertJsonPath('data.imageable_type', 'visits');
        $response->assertJsonPath('data.imageable_id', $offlineVisitId);
        $response->assertJsonPath('data.title', 'Confirmed Radiograph');
        $response->assertJsonPath('data.url', $fileUrl);

        // Verify in tenant DB
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $this->assertDatabaseHas('images', [
            'imageable_type' => 'visits',
            'imageable_id' => $offlineVisitId,
            'url' => $fileUrl,
            'title' => 'Confirmed Radiograph',
        ]);

        $tenantManager->reset();
    }
}
