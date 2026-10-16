<?php

declare(strict_types=1);

namespace App\Transformers;

abstract class Transformer
{
    /**
     * Transform a single entity/model into a standardized array payload.
     *
     * @return array<string, mixed>
     */
    abstract public function transform(mixed $item): array;

    /**
     * Transform an iterable collection of entities.
     *
     * @param  iterable<mixed>  $items
     * @return list<array<string, mixed>>
     */
    public function collection(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->transform($item);
        }

        return $result;
    }

    /**
     * Static helper for single item transformation.
     *
     * @return array<string, mixed>|null
     */
    public static function item(mixed $item): ?array
    {
        if ($item === null) {
            return null;
        }

        return (new static)->transform($item);
    }

    /**
     * Static helper for collection transformation.
     *
     * @param  iterable<mixed>  $items
     * @return list<array<string, mixed>>
     */
    public static function items(iterable $items): array
    {
        return (new static)->collection($items);
    }
}
