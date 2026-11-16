<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Validation\ValidationException;
use Stringable;

final class FractionalQuantity implements Stringable
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Parse and validate a quantity string with strict scale-4 BCMath.
     *
     * @throws ValidationException
     */
    public static function fromString(string $quantity, string $fieldName = 'quantity'): self
    {
        $trimmed = trim($quantity);

        // Reject scientific notation, non-numeric strings, or malformed decimals (max 8 integer digits + 4 decimals)
        if (strlen($trimmed) > 13 || ! preg_match('/^\d{1,8}(\.\d{1,4})?$/', $trimmed)) {
            throw ValidationException::withMessages([
                $fieldName => ["Quantity must be a valid positive numeric value (max 8 integer digits, up to 4 decimal places)."],
            ]);
        }

        $normalized = bcadd($trimmed, '0.0000', 4);

        if (bccomp($normalized, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages([
                $fieldName => ["Quantity must be strictly greater than zero."],
            ]);
        }

        return new self($normalized);
    }

    /**
     * Create from an already-validated or internal scale-4 string (can be zero).
     */
    public static function fromRaw(string $value): self
    {
        return new self(bcadd(trim($value), '0.0000', 4));
    }

    public static function zero(): self
    {
        return new self('0.0000');
    }

    public function add(self|string $other): self
    {
        $otherVal = $other instanceof self ? $other->value : bcadd(trim((string) $other), '0.0000', 4);
        return new self(bcadd($this->value, $otherVal, 4));
    }

    public function sub(self|string $other): self
    {
        $otherVal = $other instanceof self ? $other->value : bcadd(trim((string) $other), '0.0000', 4);
        return new self(bcsub($this->value, $otherVal, 4));
    }

    public function comp(self|string $other): int
    {
        $otherVal = $other instanceof self ? $other->value : bcadd(trim((string) $other), '0.0000', 4);
        return bccomp($this->value, $otherVal, 4);
    }

    public function isZero(): bool
    {
        return bccomp($this->value, '0.0000', 4) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->value, '0.0000', 4) > 0;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
