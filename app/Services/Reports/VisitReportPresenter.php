<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use DateTimeInterface;

final class VisitReportPresenter
{
    public function __construct(private readonly string $timezone = 'UTC') {}

    public function enum(mixed $value, string $default = '—'): string
    {
        if ($value === null) {
            return $default;
        }

        $resolved = $value instanceof \BackedEnum
            ? (string) $value->value
            : ($value instanceof \UnitEnum ? $value->name : (string) $value);

        return filled($resolved) ? ucfirst(str_replace('_', ' ', $resolved)) : $default;
    }

    public function date(?DateTimeInterface $date, string $format = 'Y-m-d H:i', string $default = '—'): string
    {
        if (! $date) {
            return $default;
        }

        return Carbon::instance($date)->timezone($this->timezone)->format($format);
    }

    public function text(mixed $value, string $default = '—'): string
    {
        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            $filtered = array_filter(array_map('strval', $value));

            return ! empty($filtered) ? implode(', ', $filtered) : $default;
        }

        $str = trim((string) $value);

        return filled($str) ? $str : $default;
    }

    public function list(mixed $value, string $default = 'None'): string
    {
        if (empty($value)) {
            return $default;
        }

        $items = is_iterable($value) ? $value : [$value];

        $rendered = collect($items)
            ->map(function (mixed $item): string {
                if (is_array($item)) {
                    return (string) ($item['name'] ?? json_encode($item));
                }

                return (string) $item;
            })
            ->filter()
            ->implode(', ');

        return filled($rendered) ? $rendered : $default;
    }

    public function dose(mixed $amount, mixed $unit = null): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $formattedAmount = rtrim(rtrim((string) $amount, '0'), '.');

        $resolvedUnit = $unit instanceof \BackedEnum
            ? (string) $unit->value
            : ($unit instanceof \UnitEnum ? $unit->name : (string) ($unit ?? ''));

        $unitStr = filled($resolvedUnit) ? ' '.$resolvedUnit : '';

        return $formattedAmount.$unitStr;
    }
}
