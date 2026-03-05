<?php

declare(strict_types=1);

/**
 * Example 01: Basic Loading
 *
 * Demonstrates the simplest possible usage of kariricode/dotenv:
 * load a .env file and read values via $dotenv->get().
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  DotenvConfiguration (ValueObject):
 *    - loadMode   LoadMode enum: Overwrite | Skip | Immutable (default Overwrite)
 *                 Overwrite → $_ENV and $_SERVER are overwritten for each key
 *                 Skip      → only sets keys that are NOT already set in environment
 *                 Immutable → never changes any existing environment variable
 *    - typeCasting bool (default true) — auto-cast "42"→int, "true"→bool, "null"→null
 *    - autoload   bool (default false) — load .env on Dotenv::__construct()
 *
 *  Dotenv(rootDir, config):
 *    - rootDir    string — directory containing .env file
 *    - config     DotenvConfiguration — full configuration object
 *
 *  $dotenv->get(key, default = null):
 *    - key        string — variable name (case-sensitive)
 *    - default    mixed  — returned when key is not present
 *    - Returns typed value after TypeSystem pipeline
 *
 *  $dotenv->variables():
 *    - Returns all loaded key→value pairs as flat array
 *
 *  $dotenv->isLoaded():
 *    - Returns bool; true after successful load()
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  01 — Basic Loading\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// ── 1. Instantiate and load ────────────────────────────────────────────────
$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv(__DIR__ . '/..', $config);
$dotenv->load();

// ── 2. Read via env() helper (registered in src/env.php) ──────────────────
echo "APP_NAME    : " . $dotenv->get('APP_NAME') . "\n";
echo "APP_VERSION : " . $dotenv->get('APP_VERSION') . "\n";
echo "APP_ENV     : " . $dotenv->get('APP_ENV') . "\n";

// ── 3. Read via $dotenv->get() with a default ──────────────────────────────
echo "DB_HOST     : " . $dotenv->get('DB_HOST', 'not-set') . "\n";
echo "DB_PORT     : " . $dotenv->get('DB_PORT', 5432) . "\n";
echo "MISSING_KEY : " . ($dotenv->get('MISSING_KEY') ?? '(null — default applied)') . "\n";

// ── 4. Variable interpolation (resolved at parse time) ────────────────────
echo "\n── Variable interpolation ──────────────────────────────────\n";
echo "BASE_URL          : " . $dotenv->get('BASE_URL') . "\n";
echo "HEALTH_ENDPOINT   : " . $dotenv->get('HEALTH_ENDPOINT') . "\n";
echo "AUTH_ENDPOINT     : " . $dotenv->get('AUTH_ENDPOINT') . "\n";
echo "FALLBACK_URL      : " . $dotenv->get('FALLBACK_URL') . "\n";

// ── 5. Loaded state ────────────────────────────────────────────────────────
echo "\n── State ─────────────────────────────────────────────────\n";
echo "isLoaded(): " . ($dotenv->isLoaded() ? 'true' : 'false') . "\n";
echo "Total variables loaded: " . count($dotenv->variables()) . "\n";

echo "\n✓ Example 01 completed.\n\n";
