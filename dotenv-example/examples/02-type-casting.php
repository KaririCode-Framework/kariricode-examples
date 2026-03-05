<?php

declare(strict_types=1);

/**
 * Example 02: Auto Type Casting
 *
 * kariricode/dotenv automatically detects and casts values to the correct
 * PHP type — no manual casting needed.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  DotenvConfiguration(typeCasting: true):
 *    - typeCasting bool (default true) — enables the TypeSystem pipeline:
 *        "42"       → int
 *        "3.14"     → float
 *        "true"/"false"/"yes"/"no"/"on"/"off" → bool
 *        "null"/"~"/"" → null
 *        '{"k":"v"}' → array (JSON decoded, assoc=true)
 *        anything else → string
 *    ⚠️  Type detection is order-sensitive: null checked before bool,
 *        bool before numeric, numeric before JSON, JSON before string.
 *
 *  Supported types: string, int, float, bool, null, array (JSON), object (JSON)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  02 — Auto Type Casting\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config = new DotenvConfiguration(typeCasting: true, loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv(__DIR__ . '/..', $config);
$dotenv->load();

// ── Helper ─────────────────────────────────────────────────────────────────
function dumpVar(string $name, mixed $value): void
{
    $type = get_debug_type($value);
    $display = match (true) {
        is_array($value) => json_encode($value),
        is_bool($value)  => $value ? 'true' : 'false',
        is_null($value)  => 'null',
        default          => (string) $value,
    };
    printf("  %-22s  %-10s  %s\n", $name, "($type)", $display);
}

// ── Integer ────────────────────────────────────────────────────────────────
echo "── integer values ────────────────────────────────────────\n";
dumpVar('DB_PORT',        $dotenv->get('DB_PORT'));
dumpVar('APP_PORT',       $dotenv->get('APP_PORT'));
dumpVar('DB_POOL_SIZE',   $dotenv->get('DB_POOL_SIZE'));
dumpVar('CACHE_TTL',      $dotenv->get('CACHE_TTL'));
dumpVar('API_RATE_LIMIT', $dotenv->get('API_RATE_LIMIT'));

// ── Float ──────────────────────────────────────────────────────────────────
echo "\n── float values ──────────────────────────────────────────\n";
dumpVar('PI_VALUE', $dotenv->get('PI_VALUE'));
dumpVar('SCORE',    $dotenv->get('SCORE'));

// ── Boolean ────────────────────────────────────────────────────────────────
echo "\n── boolean values ────────────────────────────────────────\n";
dumpVar('APP_DEBUG',        $dotenv->get('APP_DEBUG'));
dumpVar('CACHE_ENABLED',    $dotenv->get('CACHE_ENABLED'));
dumpVar('FEATURE_FLAG',     $dotenv->get('FEATURE_FLAG'));
dumpVar('MAINTENANCE_MODE', $dotenv->get('MAINTENANCE_MODE'));

// ── Null ───────────────────────────────────────────────────────────────────
echo "\n── null values ───────────────────────────────────────────\n";
dumpVar('NULLABLE_VAR', $dotenv->get('NULLABLE_VAR'));

// ── Array (JSON) ───────────────────────────────────────────────────────────
echo "\n── json values ───────────────────────────────────────────\n";
dumpVar('JSON_CONFIG', $dotenv->get('JSON_CONFIG'));

$json = $dotenv->get('JSON_CONFIG');
if (is_array($json)) {
    echo "  JSON_CONFIG[theme]  : " . ($json['theme'] ?? '') . "\n";
    echo "  JSON_CONFIG[lang]   : " . ($json['lang'] ?? '') . "\n";
    echo "  JSON_CONFIG[items]  : " . ($json['items'] ?? '') . " (" . get_debug_type($json['items'] ?? null) . ")\n";
}

// ── String (no cast needed) ────────────────────────────────────────────────
echo "\n── string values (no cast) ───────────────────────────────\n";
dumpVar('APP_NAME', $dotenv->get('APP_NAME'));
dumpVar('DB_USER',  $dotenv->get('DB_USER'));

echo "\n✓ Example 02 completed.\n\n";
