<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\Image;

class ImageTransformer extends Transformer
{
    /**
     * @param  Image  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'imageable_type' => $item->imageable_type,
            'imageable_id' => $item->imageable_id,
            'url' => $item->url,
            'title' => $item->title,
            'mime_type' => $item->mime_type,
            'file_size_bytes' => $item->file_size_bytes,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
