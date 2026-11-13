<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\ClinicalCase;
use App\Models\Image;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Upload an image attached to any imageable model (Case, Visit, Patient, etc.) directly to MinIO S3.
     */
    public function uploadForModel(
        Model $model,
        UploadedFile $file,
        ?string $title = null,
        ?string $tenantId = null,
        ?string $typeAlias = null
    ): Image {
        $effectiveTenantId = $tenantId
            ?? $this->tenantManager->current()?->id
            ?? 'default';

        $folder = match (true) {
            $model instanceof ClinicalCase => 'cases',
            $model instanceof Visit => 'visits',
            default => strtolower(class_basename($model)).'s',
        };

        $imageableType = $typeAlias ?? $folder;

        $ext = $file->getClientOriginalExtension() ?: 'webp';
        $filename = (string) Str::uuid().'.'.$ext;
        $path = "media/{$effectiveTenantId}/{$folder}/{$model->getKey()}/{$filename}";

        // Strictly stream directly to MinIO S3
        Storage::disk('s3')->putFileAs(
            dirname($path),
            $file,
            $filename
        );

        $url = Storage::disk('s3')->url($path);

        return Image::query()->create([
            'id' => (string) Str::uuid(),
            'imageable_type' => $imageableType,
            'imageable_id' => (string) $model->getKey(),
            'url' => $url,
            'title' => $title ?? $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
        ]);
    }

    /**
     * Upload an image attached to a Clinical Case.
     */
    public function uploadForCase(
        ClinicalCase $case,
        UploadedFile $file,
        ?string $title = null,
        ?string $tenantId = null
    ): Image {
        return $this->uploadForModel($case, $file, $title, $tenantId, 'cases');
    }

    /**
     * Upload an image attached to a Visit encounter.
     */
    public function uploadForVisit(
        Visit $visit,
        UploadedFile $file,
        ?string $title = null,
        ?string $tenantId = null
    ): Image {
        return $this->uploadForModel($visit, $file, $title, $tenantId, 'visits');
    }

    /**
     * List all images attached to a model (matches both polymorphic alias and FQCN).
     *
     * @return Collection<int, Image>
     */
    public function getImagesForModel(Model $model, ?string $typeAlias = null): Collection
    {
        $alias = $typeAlias ?? match (true) {
            $model instanceof ClinicalCase => 'cases',
            $model instanceof Visit => 'visits',
            default => strtolower(class_basename($model)).'s',
        };

        return Image::query()
            ->where(function ($q) use ($model, $alias) {
                $q->where('imageable_type', $alias)
                    ->orWhere('imageable_type', get_class($model));
            })
            ->where('imageable_id', (string) $model->getKey())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Generate a Presigned Upload URL for direct MinIO S3 upload authorized by parent Case.
     * Allows uploading media for an encounter (visit) even if the visit was created offline
     * and has not yet synced to MySQL.
     *
     * @return array{upload_url: string, file_url: string, headers: array<string, mixed>, key: string, filename: string}
     */
    public function generatePresignedUploadUrl(
        ClinicalCase $case,
        ?string $visitId,
        string $filename,
        ?string $contentType = 'image/webp',
        ?string $title = null,
        ?string $tenantId = null
    ): array {
        $effectiveTenantId = $tenantId
            ?? $this->tenantManager->current()?->id
            ?? 'default';

        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'webp';
        $uuid = (string) Str::uuid();
        $storedFilename = "{$uuid}.{$ext}";

        $subfolder = $visitId ? "visits/{$visitId}" : 'general';
        $path = "media/{$effectiveTenantId}/cases/{$case->getKey()}/{$subfolder}/{$storedFilename}";

        // Strictly generate S3 temporary upload URL via Flysystem AWS S3 adapter pointing to MinIO
        $presigned = Storage::disk('s3')->temporaryUploadUrl(
            $path,
            now()->addMinutes(15)
        );

        $uploadUrl = $presigned['url'];
        $headers = [];
        if (! empty($presigned['headers'])) {
            foreach ($presigned['headers'] as $headerKey => $headerVal) {
                $headers[$headerKey] = is_array($headerVal) ? implode(', ', $headerVal) : (string) $headerVal;
            }
        }

        // If public base URL is configured (e.g. http://10.0.3.2:9000/vet-media for emulator or LAN IP),
        // replace internal 127.0.0.1 host with public host in upload_url
        $publicBase = env('AWS_URL');
        if ($publicBase) {
            $parsedPublic = parse_url($publicBase);
            $parsedUpload = parse_url($uploadUrl);
            if (! empty($parsedPublic['host']) && ! empty($parsedUpload['host']) && $parsedUpload['host'] !== $parsedPublic['host']) {
                $publicHostPort = $parsedPublic['host'].(! empty($parsedPublic['port']) ? ':'.$parsedPublic['port'] : '');
                $uploadHostPort = $parsedUpload['host'].(! empty($parsedUpload['port']) ? ':'.$parsedUpload['port'] : '');
                $uploadUrl = str_replace($uploadHostPort, $publicHostPort, $uploadUrl);
            }
        }

        $fileUrl = Storage::disk('s3')->url($path);

        return [
            'upload_url' => $uploadUrl,
            'file_url' => $fileUrl,
            'headers' => $headers,
            'key' => $path,
            'filename' => $storedFilename,
            'case_id' => (string) $case->getKey(),
            'visit_id' => $visitId,
        ];
    }

    /**
     * Confirm a completed MinIO S3 direct upload and record the Image metadata in MySQL.
     */
    public function confirmUpload(
        ClinicalCase $case,
        string $fileUrl,
        ?string $visitId = null,
        ?string $title = null,
        ?int $fileSizeBytes = null,
        ?string $mimeType = 'image/webp',
        ?string $imageId = null
    ): Image {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        if ($path) {
            $key = ltrim($path, '/');
            $bucket = (string) config('filesystems.disks.s3.bucket', 'vet-media');
            if (str_starts_with($key, $bucket.'/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
            if (! Storage::disk('s3')->exists($key)) {
                throw ValidationException::withMessages([
                    'file_url' => ['The uploaded media file does not exist in storage.'],
                ]);
            }
        }

        $imageableType = $visitId ? 'visits' : 'cases';
        $imageableId = $visitId ?? (string) $case->getKey();

        return Image::query()->updateOrCreate(
            ['url' => $fileUrl],
            [
                'id' => $imageId ?? (string) Str::uuid(),
                'imageable_type' => $imageableType,
                'imageable_id' => $imageableId,
                'title' => $title ?? 'Diagnostic Image',
                'mime_type' => $mimeType ?? 'image/webp',
                'file_size_bytes' => $fileSizeBytes ?? 0,
            ]
        );
    }

    /**
     * Permanently delete an image from MinIO S3 and database.
     */
    public function deleteImage(Image $image): void
    {
        $path = parse_url($image->url, PHP_URL_PATH);
        if ($path) {
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'vet-media/')) {
                $path = substr($path, strlen('vet-media/'));
            }
            Storage::disk('s3')->delete($path);
        }

        $image->delete();
    }
}
