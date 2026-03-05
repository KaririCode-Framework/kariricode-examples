<?php

declare(strict_types=1);

/**
 * Example 04: Schema-Based Validation
 *
 * Loads and validates environment variables against a declarative
 * .env.schema file — no PHP code needed to define validation rules.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  $dotenv->loadWithSchema(schemaPath: string):
 *    - Loads .env AND validates against .env.schema in one call.
 *    - Throws ValidationException on any schema violation.
 *
 *  .env.schema directives (one per line: VARIABLE_NAME: constraint=value):
 *    required   — variable must be present in .env
 *    notEmpty   — value must not be empty
 *    type       — enforces PHP type: int | float | bool | string
 *    min=N      — numeric minimum value
 *    max=N      — numeric maximum value
 *    allowed=a,b,c — comma-separated allowed values
 *    regex=/pattern/ — PCRE regex the raw value must match
 *
 *  Example schema line:
 *    DB_PORT: required, type=int, min=1, max=65535
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  04 — Schema Validation\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$schemaPath = __DIR__ . '/../.env.schema';

// ── PASS: current .env satisfies the schema ────────────────────────────────
echo "── Schema PASS (valid .env) ──────────────────────────────\n";

$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv(__DIR__ . '/..', $config);

try {
    $dotenv->loadWithSchema($schemaPath);
    echo "  ✓ Schema validation passed — all variables conform.\n";

    $checks = [
        'APP_ENV'      => $dotenv->get('APP_ENV'),
        'APP_DEBUG'    => $dotenv->get('APP_DEBUG') ? 'true' : 'false',
        'APP_PORT'     => $dotenv->get('APP_PORT'),
        'DB_PORT'      => $dotenv->get('DB_PORT'),
        'DB_POOL_SIZE' => $dotenv->get('DB_POOL_SIZE'),
        'API_TIMEOUT'  => $dotenv->get('API_TIMEOUT'),
    ];

    echo "\n  Validated values:\n";
    foreach ($checks as $key => $value) {
        printf("    %-16s = %s\n", $key, $value);
    }
} catch (ValidationException $e) {
    echo "  ✗ Schema validation failed:\n";
    foreach ($e->errors() as $err) {
        echo "    – {$err}\n";
    }
}

// ── FAIL: violate the schema intentionally ─────────────────────────────────
echo "\n── Schema FAIL (invalid values) ─────────────────────────\n";

$badTmpDir = sys_get_temp_dir() . '/kc_schema_fail_' . getmypid();
@mkdir($badTmpDir, 0o755, true);

file_put_contents($badTmpDir . '/.env', implode("\n", [
    'APP_ENV=unknown_env',
    'APP_DEBUG=maybe',
    'APP_PORT=99999',
    'DB_HOST=localhost',
    'DB_PORT=999999',
    'DB_POOL_SIZE=200',
    'API_TIMEOUT=500',
    'API_RATE_LIMIT=50000',
]));

$badConfig = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$badDotenv = new Dotenv($badTmpDir, $badConfig);

try {
    $badDotenv->loadWithSchema($schemaPath);
    echo "  ✓ Passed (unexpected)\n";
} catch (ValidationException $e) {
    echo "  ✗ Caught " . count($e->errors()) . " schema violation(s):\n";
    foreach ($e->errors() as $err) {
        echo "    – {$err}\n";
    }
} finally {
    @unlink($badTmpDir . '/.env');
    @rmdir($badTmpDir);
}

echo "\n✓ Example 04 completed.\n\n";
