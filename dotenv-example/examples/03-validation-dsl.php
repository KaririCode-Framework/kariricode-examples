<?php

declare(strict_types=1);

/**
 * Example 03: Fluent Validation DSL
 *
 * Demonstrates the built-in validation DSL: required, notEmpty, isInteger,
 * isBoolean, isNumeric, between, allowedValues, matchesRegex, url, email,
 * ifPresent (conditional), and custom callbacks.
 *
 * collect-all semantics: ALL validation errors are reported at once via assert().
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  $dotenv->validate() → ValidationBuilder (fluent API):
 *    ->required(..keys)               — variables must exist in loaded .env
 *    ->notEmpty(..keys)               — values must not be empty string
 *    ->isInteger(key)                 — value must be castable to int
 *    ->isBoolean(key)                 — value must be truthy/falsy string
 *    ->isNumeric(key)                 — value must be numeric string
 *    ->between(min, max)              — ⚠️ must chain AFTER isInteger()/isNumeric()
 *    ->allowedValues(key, array)      — value must be in the listed strings
 *    ->matchesRegex(key, pattern)     — value must match the PCRE pattern
 *    ->url(key)                       — value must be a valid URL (filter_var)
 *    ->email(key)                     — value must be a valid email (filter_var)
 *    ->ifPresent(key)                 — next constraint ONLY applies if key exists
 *    ->custom(key, callable, msg)     — fn(string $v): bool; throws msg on false
 *    ->assert()                       — collects ALL errors, throws ValidationException
 *
 *  ValidationException:
 *    ->errors(): string[]  — list of all collected error messages
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  03 — Validation DSL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv(__DIR__ . '/..', $config);
$dotenv->load();

// ── Pass: valid constraints ────────────────────────────────────────────────
echo "── Validation PASS scenario ──────────────────────────────\n";

try {
    $dotenv->validate()
        ->required('APP_NAME', 'APP_ENV', 'DB_HOST', 'DB_PORT')
        ->notEmpty('APP_NAME', 'DB_HOST')
        ->isInteger('DB_PORT')->between(1, 65535)
        ->isInteger('APP_PORT')->between(1024, 65535)
        ->isBoolean('APP_DEBUG', 'CACHE_ENABLED')
        ->isNumeric('SCORE')
        ->allowedValues('APP_ENV', ['local', 'staging', 'production', 'test'])
        ->matchesRegex('APP_VERSION', '/^\d+\.\d+\.\d+$/')
        ->url('BASE_URL')
        ->email('DB_USER')  // this will fail — DB_USER is 'admin', not email
        ->assert();

    echo "  ✓ All assertions passed.\n";
} catch (ValidationException $e) {
    // DB_USER is not an email — expected failure in this demo
    echo "  ✗ Validation caught (expected for DB_USER email check):\n";
    foreach ($e->errors() as $err) {
        echo "    – {$err}\n";
    }
}

// ── Collect-all: multiple failures reported at once ────────────────────────
echo "\n── Validation FAIL — collect-all (fake bad values) ───────\n";

// Create a dotenv from a string-based fixture to demonstrate failures
$failTmpDir = sys_get_temp_dir() . '/kc_validation_fail_' . getmypid();
@mkdir($failTmpDir, 0o755, true);

file_put_contents($failTmpDir . '/.env', implode("\n", [
    'APP_ENV=invalid_env',
    'DB_PORT=99999',
    'APP_DEBUG=maybe',
    'API_TIMEOUT=0',
    'APP_NAME=',
]));

$tmpConfig = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$tmpDotenv = new Dotenv($failTmpDir, $tmpConfig);
$tmpDotenv->load();

try {
    $tmpDotenv->validate()
        ->required('APP_ENV', 'DB_PORT', 'APP_DEBUG', 'API_TIMEOUT', 'APP_NAME')
        ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
        ->isInteger('DB_PORT')->between(1, 65535)
        ->isBoolean('APP_DEBUG')
        ->isInteger('API_TIMEOUT')->between(1, 300)
        ->notEmpty('APP_NAME')
        ->assert();

    echo "  ✓ Passed (unexpected)\n";
} catch (ValidationException $e) {
    echo "  ✗ Caught " . count($e->errors()) . " error(s):\n";
    foreach ($e->errors() as $err) {
        echo "    – {$err}\n";
    }
} finally {
    @unlink($failTmpDir . '/.env');
    @rmdir($failTmpDir);
}

// ── Conditional: ifPresent (optional variable) ─────────────────────────────
echo "\n── ifPresent — conditional validation ────────────────────\n";

try {
    $dotenv->validate()
        ->ifPresent('REDIS_HOST')->notEmpty()
        ->ifPresent('REDIS_PORT')->isInteger()->between(1, 65535)
        ->assert();

    // matchesRegex requires (name, pattern) — use directly, not chained from ifPresent
    $dotenv->validate()
        ->matchesRegex('API_KEY', '/^[a-zA-Z0-9]{6,}$/')
        ->assert();

    echo "  ✓ Conditional checks passed (REDIS_* absent — skipped gracefully).\n";
    echo "  ✓ API_KEY present and matched pattern.\n";
} catch (ValidationException $e) {
    foreach ($e->errors() as $err) {
        echo "  ✗ {$err}\n";
    }
}

// ── Custom callback ────────────────────────────────────────────────────────
echo "\n── custom() callback validation ──────────────────────────\n";

try {
    $dotenv->validate()
        ->custom(
            'MAX_UPLOAD_MB',
            fn (string $v): bool => (int) $v <= 100,
            'MAX_UPLOAD_MB must be ≤ 100 MB.'
        )
        ->assert();

    echo "  ✓ Custom rule passed (MAX_UPLOAD_MB=" . $dotenv->get('MAX_UPLOAD_MB') . ").\n";
} catch (ValidationException $e) {
    foreach ($e->errors() as $err) {
        echo "  ✗ {$err}\n";
    }
}

echo "\n✓ Example 03 completed.\n\n";
