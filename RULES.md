# Strict Coding Standards (PHP 8.3 & Laravel 11)

1. Strict Types:
   - Every single PHP file must start immediately with:
     <?php
     declare(strict_types=1);

2. Domain Precision:
   - All medication doses, weights, and stock balances must use Decimal casting.
   - Never use standard float arithmetic for stock movements. Use BCMath for calculations.

3. Type Safety & Enums:
   - Forbid raw status strings ('open', 'critical', 'discharged').
   - Use backed string Enums for all entity states and categories.

4. Casts:
   - Never use `$casts = [...]`. Use Laravel 11's `protected function casts(): array`.

5. Input Flow:
   - FormRequest -> Validated Array -> Readonly DTO -> Action/Service -> Transformer Response.
