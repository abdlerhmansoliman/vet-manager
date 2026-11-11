<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Models\Image;
use App\Models\Visit;
use App\Services\Media\MediaService;
use App\Transformers\ImageTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * Upload an image for a specific Clinical Case directly to MinIO S3.
     */
    public function uploadForCase(Request $request, ClinicalCase $case): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,png,webp,jpg', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $image = $this->mediaService->uploadForCase(
            case: $case,
            file: $request->file('file'),
            title: $request->input('title')
        );

        return response()->json([
            'message' => 'Image uploaded to MinIO successfully.',
            'data' => ImageTransformer::item($image),
        ], 201);
    }

    /**
     * List all images attached to a Clinical Case.
     */
    public function indexForCase(ClinicalCase $case): JsonResponse
    {
        $images = $this->mediaService->getImagesForModel($case);

        return response()->json([
            'data' => ImageTransformer::items($images),
        ]);
    }

    /**
     * Upload an image for a specific Visit encounter directly to MinIO S3.
     */
    public function uploadForVisit(Request $request, Visit $visit): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,png,webp,jpg', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $image = $this->mediaService->uploadForVisit(
            visit: $visit,
            file: $request->file('file'),
            title: $request->input('title')
        );

        return response()->json([
            'message' => 'Image uploaded to MinIO successfully.',
            'data' => ImageTransformer::item($image),
        ], 201);
    }

    /**
     * List all images attached to a Visit encounter.
     */
    public function indexForVisit(Visit $visit): JsonResponse
    {
        $images = $this->mediaService->getImagesForModel($visit);

        return response()->json([
            'data' => ImageTransformer::items($images),
        ]);
    }

    /**
     * Request a MinIO S3 Presigned Upload URL authorized by the parent Case.
     * Allows uploading media for a visit even if the visit was created offline and has not yet synced.
     */
    public function requestPresignedUrl(Request $request, ClinicalCase $case): JsonResponse
    {
        $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['nullable', 'string', 'max:100'],
            'visit_id' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = $this->mediaService->generatePresignedUploadUrl(
            case: $case,
            visitId: $request->input('visit_id'),
            filename: $request->input('filename'),
            contentType: $request->input('content_type', 'image/webp'),
            title: $request->input('title')
        );

        return response()->json([
            'message' => 'Presigned MinIO S3 upload URL generated successfully.',
            'data' => $payload,
        ]);
    }

    /**
     * Confirm a completed MinIO S3 direct upload and record the Image metadata in MySQL.
     */
    public function confirmUpload(Request $request, ClinicalCase $case): JsonResponse
    {
        $request->validate([
            'file_url' => ['required', 'string'],
            'visit_id' => ['nullable', 'string', 'max:50'],
            'image_id' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'file_size_bytes' => ['nullable', 'integer'],
            'mime_type' => ['nullable', 'string', 'max:100'],
        ]);

        $image = $this->mediaService->confirmUpload(
            case: $case,
            fileUrl: $request->input('file_url'),
            visitId: $request->input('visit_id'),
            title: $request->input('title'),
            fileSizeBytes: $request->input('file_size_bytes'),
            mimeType: $request->input('mime_type', 'image/webp'),
            imageId: $request->input('image_id')
        );

        return response()->json([
            'message' => 'Image upload confirmed and recorded successfully.',
            'data' => ImageTransformer::item($image),
        ], 201);
    }

    /**
     * Delete an image from MinIO S3 and database.
     */
    public function destroy(Image $image): JsonResponse
    {
        $this->mediaService->deleteImage($image);

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
}
