<?php

declare(strict_types=1);

/**
 * Example 03 — Product Import: Numeric, Data & Encoding Rules
 *
 * Real-world scenario: import pipeline receives raw product data from suppliers.
 *
 * !! IMPORTANT PARAMETER NOTES (confirmed from implementations):
 *
 *  currency_format params:
 *    - 'prefix'     string: prepended to the number (e.g. 'R$ ', '$')
 *    - 'dec_point'  string: decimal separator (default '.', use ',' for pt-BR)
 *    - 'thousands'  string: thousand separator (default ',', use '.' for pt-BR)
 *    - 'decimals'   int:    decimal places (default 2)
 *    NO 'locale' or 'currency' params — those do not exist.
 *
 *  hash params:
 *    - 'algo'  string: hash algorithm (default 'sha256') — NOT 'algorithm'
 *
 *  csv_to_array params:
 *    - 'separator'  string: CSV separator (default ',')
 *    - 'enclosure'  string: CSV enclosure (default '"')
 *    - 'header'     bool:   if true (default), first CSV row is used as keys
 *                           if false, returns indexed rows
 *
 *  array_to_key_value params:
 *    - 'key'    string: name of the field to use as map key (default 'id')
 *    - 'value'  string: name of the field to use as map value (default 'name')
 *    Input MUST be a list of associative arrays (e.g. [['id'=>1,'name'=>'Alice'],…])
 *
 * Run: php examples/03-product-import.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Configuration\TransformerConfiguration;
use KaririCode\Transformer\Provider\TransformerServiceProvider;

$provider = new TransformerServiceProvider();
$engine = $provider->createEngine();

// ── Numeric rules ─────────────────────────────────────────────────────────────

$numericResult = $engine->transform(
    [
        'price_brl' => 1990.50,
        'price_usd' => 1500.00,
        'discount'  => 0.15,
        'rank'      => 3,
        'stock'     => 42,
    ],
    [
        // BRL: prefix 'R$ ', comma as decimal, dot as thousands
        'price_brl' => [['currency_format', ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']]],
        // USD: prefix '$', US formatting (defaults)
        'price_usd' => [['currency_format', ['prefix' => '$']]],
        // percentage: 0.15 → "15%"  (decimals defaults to 1)
        'discount'  => [['percentage', ['decimals' => 0]]],
        'rank'      => ['ordinal'],           // 3 → "3rd"
        'stock'     => ['number_to_words'],   // 42 → "forty-two"
    ],
);

echo "\n═══ Numeric Rules ════════════════════════════════════════════════\n";
printf("  price_brl  : %s\n", $numericResult->get('price_brl'));  // R$ 1.990,50
printf("  price_usd  : %s\n", $numericResult->get('price_usd'));  // $1,500.00
printf("  discount   : %s\n", $numericResult->get('discount'));   // 15%
printf("  rank       : %s\n", $numericResult->get('rank'));       // 3rd
printf("  stock      : %s\n", $numericResult->get('stock'));      // forty-two

// ── Data rules ────────────────────────────────────────────────────────────────

// csv_to_array with header=true (default): maps first row as keys
$csvWithHeader = "name,price,qty\nT-shirt,29.90,100\nJeans,149.90,50";

// csv_to_array with header=false: returns indexed rows
$csvNoHeader = "php,kariricode,transformer";

// array_to_key_value: expects list of {key_field, value_field} objects
$productList = [
    ['sku' => 'KC-001', 'title' => 'KaririCode Transformer'],
    ['sku' => 'KC-002', 'title' => 'KaririCode Sanitizer'],
    ['sku' => 'KC-003', 'title' => 'KaririCode Devkit'],
];

$dataResult = $engine->transform(
    [
        'csv_h'  => $csvWithHeader,
        'csv_n'  => $csvNoHeader,
        'meta'   => ['sku' => 'KC-001', 'qty' => 42, 'wh' => 'SP-01'],
        'attrs'  => $productList,
        'colors' => ['Midnight Blue', 'Charcoal Gray', 'Ivory White'],
    ],
    [
        'csv_h'  => ['csv_to_array'],                                                              // header: true (default)
        'csv_n'  => [['csv_to_array', ['header' => false]]],                                      // indexed rows
        'meta'   => ['json_encode'],                                                               // array → JSON
        'attrs'  => [['array_to_key_value', ['key' => 'sku', 'value' => 'title']]],               // list → map
        'colors' => [['implode', ['separator' => ' | ']]],                                         // array → string
    ],
);

echo "\n═══ Data Rules ══════════════════════════════════════════════════\n";
$csvH = (array) $dataResult->get('csv_h');
printf("  csv_to_array (header=true)   : [0]['name']=%s [0]['price']=%s\n",
    $csvH[0]['name'] ?? '?', $csvH[0]['price'] ?? '?');
$csvN = $dataResult->get('csv_n');
printf("  csv_to_array (header=false)  : %s\n", json_encode($csvN));
printf("  json_encode                  : %s\n", $dataResult->get('meta'));
printf("  array_to_key_value           : %s\n", json_encode($dataResult->get('attrs'), JSON_UNESCAPED_UNICODE));
printf("  implode                      : %s\n", $dataResult->get('colors'));

// json_decode round-trip
$encoded = $dataResult->get('meta');
$decoded = $engine->transform(['v' => $encoded], ['v' => ['json_decode']])->get('v');
printf("  json_decode round-trip       : sku=%s qty=%s\n", $decoded['sku'] ?? '?', $decoded['qty'] ?? '?');

// ── Encoding rules ────────────────────────────────────────────────────────────

// hash param key is 'algo' (NOT 'algorithm')
$encResult = $engine->transform(
    [
        'key'    => 'sk_live_kariricode_api_token',
        'sha256' => 'kariricode-transformer-v2.0.0',
        'md5'    => 'kariricode-transformer-v2.0.0',
        'sha1'   => 'kariricode-transformer-v2.0.0',
    ],
    [
        'key'    => ['base64_encode'],
        'sha256' => [['hash', ['algo' => 'sha256']]],   // algo: sha256
        'md5'    => [['hash', ['algo' => 'md5']]],      // algo: md5 (32 chars)
        'sha1'   => [['hash', ['algo' => 'sha1']]],     // algo: sha1 (40 chars)
    ],
);

// base64 round-trip
$b64Decoded = $engine->transform(
    ['v' => $encResult->get('key')],
    ['v' => ['base64_decode']],
)->get('v');

echo "\n═══ Encoding Rules ══════════════════════════════════════════════\n";
printf("  base64_encode  : %s\n", $encResult->get('key'));
printf("  base64_decode  : %s\n", $b64Decoded);
printf("  hash sha256    : %s  (64 chars)\n", $encResult->get('sha256'));
printf("  hash md5       : %s  (32 chars)\n", $encResult->get('md5'));
printf("  hash sha1      : %s  (40 chars)\n", $encResult->get('sha1'));

// ── TransformerConfiguration: disable tracking for high-throughput paths ──────

$fastEngine = $provider->createEngine(new TransformerConfiguration(trackTransformations: false));
$bulkResult = $fastEngine->transform(
    ['v1' => 1500.0, 'v2' => 299.9, 'v3' => 0.085],
    [
        'v1' => [['currency_format', ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']]],
        'v2' => [['currency_format', ['prefix' => '$']]],
        'v3' => [['percentage', ['decimals' => 1]]],
    ],
);
echo "\n═══ TransformerConfiguration(trackTransformations: false) — no log cost\n";
printf("  v1=%s  v2=%s  v3=%s  log_count=%d\n",
    $bulkResult->get('v1'), $bulkResult->get('v2'), $bulkResult->get('v3'),
    $bulkResult->transformationCount(), // 0 — tracking disabled
);
echo "═════════════════════════════════════════════════════════════════\n\n";
