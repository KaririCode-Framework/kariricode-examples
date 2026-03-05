<?php

declare(strict_types=1);

/**
 * Example 02 — E-Commerce Product Catalog
 *
 * Real-world scenario: validating a product before inserting it into a catalog,
 * covering numeric constraints, date windows, type checking, and collection rules.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  positive             : no params — value > 0
 *  integer              : no params — value is an integer
 *  range                : 'min'(numeric), 'max'(numeric) — value within bounds
 *  decimal              : 'places'(int) — at most N decimal places
 *  greater_than         : 'threshold'(numeric) — value > threshold
 *  less_than            : 'threshold'(numeric) — value < threshold
 *  between              : 'min'(numeric), 'max'(numeric) — inclusive range
 *  date_format          : 'format'(string, default 'Y-m-d') — date string must match format
 *  date_after           : 'after'(string date), 'format'(string) — date is after boundary
 *  date_before          : 'before'(string date), 'format'(string) — date is before boundary
 *  date_between         : 'after'(string), 'before'(string), 'format'(string)
 *  type                 : 'type'(string) — gettype() must equal the given type string
 *  count                : 'min'(int), 'max'(int) — array element count
 *  unique               : no params — array must have no duplicate values
 *  key_exists           : 'key'(string|int) — key must exist in the array
 *  slug                 : no params — only lowercase a-z, 0-9 and hyphens
 *
 * Run: php examples/02-ecommerce-product.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Provider\ValidatorServiceProvider;

// ── DTO ───────────────────────────────────────────────────────────────────────

final class ProductInput
{
    /**
     * slug → URL-safe identifier: only a-z, 0-9 and hyphens
     * length → 3 .. 80 chars
     */
    #[Validate('required', 'slug', ['length', ['min' => 3, 'max' => 80]])]
    public string $sku = '';

    /**
     * required + not_empty → must have a name
     * length → 5 .. 200 chars
     */
    #[Validate('required', 'not_empty', ['length', ['min' => 5, 'max' => 200]])]
    public string $name = '';

    /**
     * positive → price must be > 0
     * decimal → at most 2 decimal places (cents)
     */
    #[Validate('required', 'positive', ['decimal', ['places' => 2]])]
    public float|int $price = 0;

    /**
     * integer → stock count must be a whole number
     * between → 0 .. 99 999
     */
    #[Validate('integer', ['between', ['min' => 0, 'max' => 99999]])]
    public int $stock = 0;

    /**
     * date_format → must be Y-m-d
     * date_after  → launch must be after 2020-01-01
     */
    #[Validate('required', ['date_format', ['format' => 'Y-m-d']], ['date_after', ['after' => '2020-01-01']])]
    public string $launchDate = '';

    /**
     * date_between → campaign must fall inside 2024 .. 2026
     */
    #[Validate(['date_format', ['format' => 'Y-m-d']], ['date_between', ['after' => '2024-01-01', 'before' => '2026-12-31']])]
    public string $campaignExpiry = '';

    /**
     * type → must be an actual PHP array (gettype() === 'array')
     * count → 1 .. 10 tags
     * unique → no duplicated tags
     */
    #[Validate(['type', ['type' => 'array']], ['count', ['min' => 1, 'max' => 10]], 'unique')]
    public array $tags = [];

    public function __construct(
        string $sku,
        string $name,
        float|int $price,
        int $stock,
        string $launchDate,
        string $campaignExpiry,
        array $tags,
    ) {
        $this->sku            = $sku;
        $this->name           = $name;
        $this->price          = $price;
        $this->stock          = $stock;
        $this->launchDate     = $launchDate;
        $this->campaignExpiry = $campaignExpiry;
        $this->tags           = $tags;
    }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

$provider  = new ValidatorServiceProvider();
$validator = $provider->createAttributeValidator();

// ── Helper ────────────────────────────────────────────────────────────────────

function printResult02(string $label, object $result): void
{
    $valid = $result->isValid();
    echo "\n─── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo '  valid  : ' . ($valid ? '✅ true' : '❌ false') . PHP_EOL;
    if (! $valid) {
        foreach ($result->getErrors() as $err) {
            printf("  ✗ %-20s %s\n", $err->field . ':', $err->message);
        }
    }
    echo PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO A — valid product                                              │
// └──────────────────────────────────────────────────────────────────────────┘

$product = new ProductInput(
    sku:            'iphone-16-pro-256gb',
    name:           'Apple iPhone 16 Pro 256 GB Titanium',
    price:          8999.99,
    stock:          150,
    launchDate:     '2025-01-15',
    campaignExpiry: '2025-06-30',
    tags:           ['smartphone', 'apple', 'iphone', 'pro'],
);

$result = $validator->validate($product);
printResult02('Scenario A — valid product', $result);
assert($result->isValid(), 'A: valid product should pass');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO B — invalid price, out-of-range stock, duplicate tags          │
// └──────────────────────────────────────────────────────────────────────────┘

$bad = new ProductInput(
    sku:            'bad sku with spaces', // slug fails (spaces not allowed)
    name:           'X',                   // too short (< 5 chars)
    price:          -9.99,                 // negative — positive fails
    stock:          100000,                // exceeds max 99 999
    launchDate:     '1999-12-31',          // before 2020-01-01
    campaignExpiry: '2030-01-01',          // outside 2024–2026 window
    tags:           ['phone', 'phone'],    // duplicate → unique fails; count ok
);

$result = $validator->validate($bad);
printResult02('Scenario B — invalid product', $result);
assert(! $result->isValid(), 'B: invalid product must fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO C — Engine API: validate raw arrays (no DTO/attributes)        │
// └──────────────────────────────────────────────────────────────────────────┘

echo "─── Scenario C — Engine API (raw data array) ─────────────────────────────\n";

$engine = $provider->createEngine();

$data = [
    'discount'    => 0.15,    // 15 % — between 0 and 1
    'rating'      => 4.5,     // 0 .. 5
    'reviewCount' => 120,
];

$result = $engine->validate($data, [
    'discount'    => [['decimal', ['places' => 2]], ['between', ['min' => 0, 'max' => 1]]],
    'rating'      => [['between', ['min' => 0, 'max' => 5]]],
    'reviewCount' => ['integer', 'positive'],
]);

echo '  discount    valid : ' . ($result->isValid() ? '✅' : '❌') . PHP_EOL;
assert($result->isValid(), 'C: discount/rating/reviewCount should pass');

$badData = ['discount' => 1.555, 'rating' => 6.0, 'reviewCount' => -1];
$result2 = $engine->validate($badData, [
    'discount'    => [['between', ['min' => 0, 'max' => 1]]],
    'rating'      => [['between', ['min' => 0, 'max' => 5]]],
    'reviewCount' => ['positive'],
]);
echo '  bad metrics valid : ' . (! $result2->isValid() ? '✅ (correctly invalid)' : '❌') . PHP_EOL;
assert(! $result2->isValid(), 'C: bad metrics should fail');

echo PHP_EOL;
echo "═══════════════════════════════════════════════════════\n";
echo "✅ Example 02 — E-Commerce Product validation complete!\n";
echo "═══════════════════════════════════════════════════════\n\n";
