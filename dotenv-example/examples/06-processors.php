<?php

declare(strict_types=1);

/**
 * Example 06: Variable Processors
 *
 * Processors transform typed values after loading. They are applied by
 * variable name or glob pattern (e.g., "*_URL").
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  $dotenv->addProcessor(pattern: string, processor: VariableProcessor):
 *    - pattern: exact name ("DB_HOST") or glob ("*_URL", "ENCODED_*")
 *    - processor: any class implementing VariableProcessor interface:
 *        process(rawValue: string, typedValue: mixed): string
 *    ⚠️  Must be called BEFORE $dotenv->load() to take effect.
 *    ⚠️  Glob patterns are matched with fnmatch() — case-sensitive on Linux.
 *
 *  Built-in processors:
 *    TrimProcessor         : strips leading/trailing whitespace from raw value
 *    Base64DecodeProcessor : base64_decode() the raw value string
 *    CsvToArrayProcessor   : splits raw CSV string → PHP array (separator: ',')
 *    UrlNormalizerProcessor: rtrim(url, '/') — removes trailing slash(es)
 *
 *  Custom processors: implement VariableProcessor; return processed string.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Contract\VariableProcessor;
use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Processor\Base64DecodeProcessor;
use KaririCode\Dotenv\Processor\CsvToArrayProcessor;
use KaririCode\Dotenv\Processor\TrimProcessor;
use KaririCode\Dotenv\Processor\UrlNormalizerProcessor;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  06 — Variable Processors\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config1 = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv  = new Dotenv(__DIR__ . '/..', $config1);

// ── Register processors before load ───────────────────────────────────────
// TrimProcessor: trim whitespace on any *_NAME or *_USER variable
$dotenv->addProcessor('*_NAME', new TrimProcessor());
$dotenv->addProcessor('*_USER', new TrimProcessor());

// Base64DecodeProcessor: automatically decode ENCODED_* variables
$dotenv->addProcessor('ENCODED_*', new Base64DecodeProcessor());

// CsvToArrayProcessor: split ALLOWED_IPS into an array
$dotenv->addProcessor('ALLOWED_IPS', new CsvToArrayProcessor());

// UrlNormalizerProcessor: remove trailing slash from all *_URL variables
$dotenv->addProcessor('*_URL', new UrlNormalizerProcessor());
$dotenv->addProcessor('*_ENDPOINT', new UrlNormalizerProcessor());

$dotenv->load();

// ── TrimProcessor ─────────────────────────────────────────────────────────
echo "── TrimProcessor (*_NAME, *_USER) ────────────────────────\n";
echo "  APP_NAME (trimmed): '" . $dotenv->get('APP_NAME') . "'\n";

// ── Base64DecodeProcessor ─────────────────────────────────────────────────
echo "\n── Base64DecodeProcessor (ENCODED_*) ─────────────────────\n";
$encoded  = base64_decode('SGVsbG8gZnJvbSBLYXJpcmlDb2RlIQ==');
$decoded  = $dotenv->get('ENCODED_SECRET');
echo "  ENCODED_SECRET raw   : SGVsbG8gZnJvbSBLYXJpcmlDb2RlIQ==\n";
echo "  ENCODED_SECRET decoded: " . $decoded . "\n";
echo "  Type: " . get_debug_type($decoded) . "\n";

// ── CsvToArrayProcessor ───────────────────────────────────────────────────
echo "\n── CsvToArrayProcessor (ALLOWED_IPS) ─────────────────────\n";
$ips = $dotenv->get('ALLOWED_IPS');
echo "  ALLOWED_IPS type  : " . get_debug_type($ips) . "\n";
if (is_array($ips)) {
    echo "  ALLOWED_IPS count : " . count($ips) . "\n";
    foreach ($ips as $i => $ip) {
        echo "    [{$i}] {$ip}\n";
    }
}

// ── UrlNormalizerProcessor ────────────────────────────────────────────────
echo "\n── UrlNormalizerProcessor (*_URL, *_ENDPOINT) ─────────────\n";

// APP_WEBHOOK_URL has a trailing space+slash in .env — both get cleaned
echo "  APP_WEBHOOK_URL    : " . $dotenv->get('APP_WEBHOOK_URL') . "\n";
echo "  HEALTH_ENDPOINT    : " . $dotenv->get('HEALTH_ENDPOINT') . "\n";
echo "  AUTH_ENDPOINT      : " . $dotenv->get('AUTH_ENDPOINT') . "\n";

// ── Custom processor via closure ───────────────────────────────────────────
echo "\n── Custom processor (uppercase APP_ENV) ───────────────────\n";



$upperCaseProcessor = new class implements VariableProcessor {
    #[\Override]
    public function process(string $rawValue, mixed $typedValue): string
    {
        return strtoupper($rawValue);
    }
};

$config2 = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv2 = new Dotenv(__DIR__ . '/..', $config2);
$dotenv2->addProcessor('APP_ENV', $upperCaseProcessor);
$dotenv2->load();

echo "  APP_ENV (processed) : " . $dotenv2->get('APP_ENV') . "\n";

echo "\n✓ Example 06 completed.\n\n";
