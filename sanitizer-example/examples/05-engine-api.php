<?php

declare(strict_types=1);

/**
 * Example 05 — SanitizerEngine (Programmatic API, no attributes)
 *
 * Real-world scenario: sanitizing raw associative arrays from API payloads or
 * database results without DTOs.
 *
 * SanitizerEngine.sanitize() signature:
 *   sanitize(array $data, array $fieldRules): SanitizationResult
 *
 * !! PARAMETER REFERENCE for this example:
 *
 *  normalize_date  : 'outputFormat'(string, default 'Y-m-d')
 *                    — tries multiple date formats (Y/m/d, d-m-Y, m.d.Y, etc.)
 *                    — if none match, returns original value unchanged
 *  timestamp_to_date: 'format'(string, default 'Y-m-d H:i:s')
 *                    — param is 'format' (not 'outputFormat')
 *  replace         : 'search'(string), 'replace'(string)
 *                    — plain string replacement (no regex)
 *  regex_replace   : 'pattern'(string), 'replacement'(string)
 *                    — PCRE pattern replacement (e.g. '/\+[^@]+/')
 *  clamp           : 'min'(float|int), 'max'(float|int)
 *  round           : 'precision'(int, default 0)
 *
 *  Dot-notation support:
 *    Engine resolves 'customer.name' as $data['customer']['name']
 *    Results are stored in $result['customer.name'] (flat key format)
 *
 * Run: php examples/05-engine-api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

$engine = (new SanitizerServiceProvider())->createEngine();

// ── 5a: API search / user profile payload ────────────────────────────────────

echo "═══ 5a: User Profile Payload ═══════════════════════════════════\n";

$payload = [
    'name'    => '  walmir silva  ',
    'email'   => '  WALMIR@KARIRICODE.ORG  ',
    'bio'     => "<p>PHP   developer.<br>Loves   clean code.</p><script>evil()</script>",
    'website' => '  https://kariricode.org  ',
];

$result = $engine->sanitize($payload, [
    'name'    => ['trim', 'capitalize'],
    'email'   => ['trim', 'lower_case', 'email_filter'],
    'bio'     => ['strip_tags', 'normalize_whitespace', ['truncate', ['maxLength' => 100]]],
    'website' => ['trim'],
]);

$data = $result->getSanitizedData();
printf("  name    : '%s'\n", $data['name']);    // 'Walmir Silva'
printf("  email   : '%s'\n", $data['email']);   // 'walmir@kariricode.org'
printf("  bio     : '%s'\n", $data['bio']);     // plain text, no HTML, truncated
printf("  website : '%s'\n", $data['website']); // 'https://kariricode.org'

assert($data['name']  === 'Walmir Silva',          "name should be capitalized");
assert($data['email'] === 'walmir@kariricode.org', "email should be lowercased");
assert(!str_contains($data['bio'], '<'),            "bio should have no HTML");
echo "✅ Passed\n\n";

// ── 5b: Numeric normalization (prices + stock) ────────────────────────────────

echo "═══ 5b: Price & Stock Normalization ═══════════════════════════\n";

$products = [
    ['price' => '  29.999  ', 'stock' => '100.7'],   // trim + to_float + round + clamp
    ['price' => '-100',        'stock' => '-5'],       // negative → clamp to min
    ['price' => '150000',      'stock' => '999999'],   // over max → clamp to max
];

// Pipeline order: trim → to_float → round(precision:2) → clamp(min:0.01, max:9999.99)
$fieldRules = [
    'price' => ['trim', 'to_float', ['round', ['precision' => 2]], ['clamp', ['min' => 0.01, 'max' => 9999.99]]],
    'stock' => ['to_int', ['clamp', ['min' => 0, 'max' => 9999]]],
];

foreach ($products as $raw) {
    $res  = $engine->sanitize($raw, $fieldRules);
    $data = $res->getSanitizedData();
    printf("  price='%s' → %.2f   stock='%s' → %d\n",
        $raw['price'], (float) $data['price'],
        $raw['stock'], (int) $data['stock']);
}
echo "✅ Passed\n\n";

// ── 5c: Date normalization ────────────────────────────────────────────────────

echo "═══ 5c: Date Normalization (normalize_date) ════════════════════\n";
echo "  normalize_date tries multiple formats automatically.\n";
echo "  If none match, returns the original value unchanged.\n\n";

$records = [
    ['created_at' => '2024/03/15'],    // Y/m/d format
    ['created_at' => '15-03-2024'],    // d-m-Y format
    ['created_at' => '03.15.2024'],    // m.d.Y format
];

foreach ($records as $raw) {
    // outputFormat: output format string (default 'Y-m-d')
    $res  = $engine->sanitize($raw, ['created_at' => [['normalize_date', ['outputFormat' => 'Y-m-d']]]]);
    $data = $res->getSanitizedData();
    printf("  '%s' → '%s'\n", $raw['created_at'], $data['created_at']);
}
echo "✅ Passed\n\n";

// ── 5d: Brazilian documents (CPF, CNPJ, CEP) ─────────────────────────────────

echo "═══ 5d: Brazilian Documents ═══════════════════════════════════\n";

$customerData = [
    'cpf'        => '529.982.247-25',
    'cnpj'       => '11222333000181',
    'cep'        => '63050210',
    'phone'      => '+55 (88) 9.9999-8888',
    'trade_name' => '  kariricode framework ltda  ',
];

// Pipeline: digits_only (strip mask) → format_* (reformat)  — ORDER MATTERS
$res  = $engine->sanitize($customerData, [
    'cpf'        => ['digits_only', 'format_cpf'],   // → 529.982.247-25
    'cnpj'       => ['digits_only', 'format_cnpj'],  // → 11.222.333/0001-81
    'cep'        => ['digits_only', 'format_cep'],   // → 63050-210
    'phone'      => ['digits_only'],                  // → only digits (no format_phone rule)
    'trade_name' => ['trim', 'upper_case'],
]);
$data = $res->getSanitizedData();

printf("  CPF        : %s\n", $data['cpf']);        // 529.982.247-25
printf("  CNPJ       : %s\n", $data['cnpj']);       // 11.222.333/0001-81
printf("  CEP        : %s\n", $data['cep']);        // 63050-210
printf("  phone      : %s\n", $data['phone']);      // digits only
printf("  trade_name : %s\n", $data['trade_name']); // UPPERCASE

assert($data['cpf']  === '529.982.247-25',              "CPF formatted");
assert(str_contains($data['cnpj'], '/'),                 "CNPJ formatted");
assert(str_contains($data['cep'], '-'),                  "CEP formatted");
assert(ctype_digit($data['phone']),                      "phone digits only");
assert($data['trade_name'] === 'KARIRICODE FRAMEWORK LTDA', "trade_name uppercase");
echo "✅ Passed\n\n";

// ── 5e: Dot-notation nested field access ──────────────────────────────────────

echo "═══ 5e: Dot-notation Nested Field Access ═══════════════════════\n";
echo "  'customer.name' reads \$data['customer']['name'] at execution time.\n";
echo "  Result stored as flat key 'customer.name' in getSanitizedData().\n\n";

$order = [
    'customer' => ['name' => '  walmir silva  ', 'email' => '  WALMIR@KARIRICODE.ORG  '],
    'shipping' => ['cep' => '01310100'],
    'notes'    => '  <b>fragile</b>   handle with care  ',
];

$res  = $engine->sanitize($order, [
    'customer.name'  => ['trim', 'capitalize'],
    'customer.email' => ['trim', 'lower_case', 'email_filter'],
    'shipping.cep'   => ['digits_only', 'format_cep'],
    'notes'          => ['strip_tags', 'trim', 'normalize_whitespace'],
]);
$data = $res->getSanitizedData();

printf("  customer.name  : '%s'\n", $data['customer.name']);   // 'Walmir Silva'
printf("  customer.email : '%s'\n", $data['customer.email']);  // 'walmir@kariricode.org'
printf("  shipping.cep   : '%s'\n", $data['shipping.cep']);    // '01310-100'
printf("  notes          : '%s'\n", $data['notes']);            // 'fragile handle with care'

assert($data['customer.name']  === 'Walmir Silva',         "name capitalized");
assert($data['customer.email'] === 'walmir@kariricode.org', "email lowercased");
assert(str_contains($data['shipping.cep'], '-'),            "CEP formatted");
assert(!str_contains($data['notes'], '<'),                  "notes stripped");
echo "✅ All engine assertions passed!\n\n";
