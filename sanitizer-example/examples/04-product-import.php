<?php

declare(strict_types=1);

/**
 * Example 04 — E-commerce Product Import Sanitization
 *
 * Real-world scenario: processing a product CSV/API import with mixed data
 * quality. Sanitizes prices, stock, titles and descriptions in bulk.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  trim          : no params
 *  capitalize    : no params — capitalizes first letter of each word
 *  html_purify   : no params — removes dangerous tags, keeps safe HTML
 *  normalize_whitespace: no params — collapses whitespace sequences
 *  lower_case    : no params
 *  slug          : no params — spaces→dash, special chars removed
 *  to_float      : no params — converts string like "29.9" or "  29.9  " to float
 *                  ⚠️ Always run AFTER trim so "  29.9  " is clean before conversion
 *  to_int        : no params — converts string or float to int (truncates decimal)
 *  round         : 'precision' (int, default 0) — rounds to N decimal places
 *                  ⚠️ Run AFTER to_float so value is already numeric
 *  clamp         : 'min' (float|int), 'max' (float|int)
 *                  — clamps value to [min, max] range
 *                  — negative price → 0.01, price > 99999.99 → 99999.99
 *                  — negative stock → 0,  stock > 99999 → 99999
 *  truncate      : 'maxLength' (int)
 *
 * Run: php examples/04-product-import.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

// ── DTO ──────────────────────────────────────────────────────────────────────

final class ProductDto
{
    /** trim → capitalize → truncate (maxLength: 80) */
    #[Sanitize('trim', 'capitalize', ['truncate', ['maxLength' => 80]])]
    public string $title = '';

    /**
     * html_purify → strips <script>, <iframe>, on* attrs (XSS protection)
     * normalize_whitespace → collapses remaining whitespace
     * truncate → maxLength: 500 chars for description
     */
    #[Sanitize('html_purify', 'normalize_whitespace', ['truncate', ['maxLength' => 500]])]
    public string $description = '';

    /**
     * Price pipeline (ORDER MATTERS):
     *   1. trim    → strip whitespace ("  29.9  " → "29.9")
     *   2. to_float → parse as float (29.9)
     *   3. round   → precision: 2 → round to 2 decimals (29.90)
     *   4. clamp   → min: 0.01, max: 99999.99 → enforce bounds
     *      "-5" → 0.01   "199999.99" → 99999.99
     */
    #[Sanitize('trim', 'to_float', ['round', ['precision' => 2]], ['clamp', ['min' => 0.01, 'max' => 99999.99]])]
    public mixed $price = 0.0;

    /**
     * Stock pipeline:
     *   1. to_int  → "150.0" → 150, "-3" → -3
     *   2. clamp   → min: 0, max: 99999 → negative stock → 0
     */
    #[Sanitize('to_int', ['clamp', ['min' => 0, 'max' => 99999]])]
    public mixed $stock = 0;

    /** trim → lower_case → slug → "Clothing & Apparel" → "clothing-apparel" */
    #[Sanitize('trim', 'lower_case', 'slug')]
    public string $category = '';

    public function __construct(
        string $title,
        string $description,
        mixed $price,
        mixed $stock,
        string $category,
    ) {
        $this->title       = $title;
        $this->description = $description;
        $this->price       = $price;
        $this->stock       = $stock;
        $this->category    = $category;
    }
}

// ── Simulate dirty import rows ────────────────────────────────────────────────

$sanitizer = (new SanitizerServiceProvider())->createAttributeSanitizer();

$products = [
    new ProductDto(
        title:       '  kariricode T-SHIRT  ',
        description: '<p>100% cotton <strong>premium</strong> shirt.</p><script>track()</script>',
        price:       '  29.9  ',         // whitespace → to_float → round → 29.90
        stock:       '150.0',             // float string → to_int → 150
        category:    '  Clothing & Apparel  ',
    ),
    new ProductDto(
        title:       'LAPTOP STAND Pro 2024',
        description: '<div>Ergonomic stand for <em>laptops</em> up to 17".</div>',
        price:       '-5',               // negative → clamp min 0.01
        stock:       '-3',               // negative → clamp min 0
        category:    'Accessories/Hardware',
    ),
    new ProductDto(
        title:       'wireless keyboard   ',
        description: '<script>evil()</script>Bluetooth. 2.4GHz. 12-month battery.',
        price:       '199999.99',        // over max → clamp to 99999.99
        stock:       '0',
        category:    '  KEYBOARDS  ',
    ),
];

foreach ($products as $p) {
    $sanitizer->sanitize($p);
}

// ── Print result ─────────────────────────────────────────────────────────────

echo "\n═══ E-commerce Product Import ═══════════════════════════════════\n";
foreach ($products as $i => $p) {
    printf("\n  Product %d:\n", $i + 1);
    printf("    title       : %s\n",   $p->title);
    printf("    description : %s\n",   $p->description);
    printf("    price       : %.2f\n", (float) $p->price);
    printf("    stock       : %d\n",   (int)   $p->stock);
    printf("    category    : %s\n",   $p->category);
}
echo "\n═════════════════════════════════════════════════════════════════\n";

assert($products[0]->title    === 'Kariricode T-shirt',    "title should be capitalized");
assert(!str_contains((string) $products[0]->description, '<script>'), "description should be purified");
assert((float) $products[0]->price  === 29.90,             "price should be 29.90");
assert((int) $products[0]->stock    === 150,               "stock should be int 150");
assert($products[0]->category === 'clothing-apparel',      "category should be slug");

assert((float) $products[1]->price >= 0.01,                "negative price clamped to 0.01");
assert((int) $products[1]->stock   === 0,                  "negative stock clamped to 0");

assert((float) $products[2]->price === 99999.99,           "price clamped to max");
assert($products[2]->category === 'keyboards',             "category should be slug");

echo "\n✅ All assertions passed!\n\n";
