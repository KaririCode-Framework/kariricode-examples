<?php

declare(strict_types=1);

/**
 * Example 07: bootEnv() — Environment-Aware Cascade Loading
 *
 * bootEnv() loads files in order (Symfony-inspired cascade):
 *   1. .env               — base defaults (committed to VCS)
 *   2. .env.local         — local overrides (gitignored)
 *   3. .env.{env}         — environment-specific defaults
 *   4. .env.{env}.local   — environment-specific local (skipped for 'test')
 *
 * The environment name is resolved from:
 *   1. $environmentName argument (if passed)
 *   2. DotenvConfiguration::environmentName
 *   3. APP_ENV variable (already loaded by step 1)
 *   4. Defaults to 'dev'
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  $dotenv->bootEnv(environmentName: ?string = null):
 *    - environmentName: override env name (null → auto-resolve from APP_ENV)
 *    - ⚠️ env = 'test' skips .env.test.local for reproducibility in CI
 *
 *  DotenvConfiguration(environmentName: string):
 *    - Sets the default environment name used by bootEnv() when no arg passed.
 *
 *  $dotenv->debug(): array
 *    - Returns list of [key, value, source, loadedAt] for all loaded variables.
 *    - Useful to trace which file provided which variable in a cascade.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  07 — bootEnv() Cascade Loading\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$dir = __DIR__ . '/..';

// ── Prepare cascade files ──────────────────────────────────────────────────
// .env is already created. We also have .env.local.
// Let's also create a .env.staging for demonstration:
$stagingEnv = $dir . '/.env.staging';
file_put_contents($stagingEnv, implode("\n", [
    '# Staging overrides',
    'APP_ENV=staging',
    'APP_DEBUG=false',
    'DB_HOST=staging-db.internal',
    'DB_POOL_SIZE=50',
]));

// ── 1. Default cascade (env resolved from APP_ENV in .env = 'local') ───────
echo "── Default cascade (APP_ENV=local from .env) ─────────────\n";

$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv($dir, $config);
$dotenv->bootEnv();

echo "  APP_ENV     : " . $dotenv->get('APP_ENV') . "\n";
echo "  APP_DEBUG   : " . ($dotenv->get('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "  DB_HOST     : " . $dotenv->get('DB_HOST') . "\n";
echo "  DB_POOL_SIZE: " . $dotenv->get('DB_POOL_SIZE') . "\n";

// ── 2. Explicit environment override ──────────────────────────────────────
echo "\n── Explicit staging environment ──────────────────────────\n";

$config2 = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv2 = new Dotenv($dir, $config2);
$dotenv2->bootEnv('staging');

echo "  APP_ENV     : " . $dotenv2->get('APP_ENV') . "\n";
echo "  APP_DEBUG   : " . ($dotenv2->get('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "  DB_HOST     : " . $dotenv2->get('DB_HOST') . "\n";
echo "  DB_POOL_SIZE: " . $dotenv2->get('DB_POOL_SIZE') . "\n";

// ── 3. Test environment (skips .env.test.local for reproducibility) ────────
echo "\n── Test environment (env.test.local skipped) ─────────────\n";

$testEnv = $dir . '/.env.test';
file_put_contents($testEnv, implode("\n", [
    'APP_ENV=test',
    'APP_DEBUG=false',
    'DB_NAME=kariricode_test',
]));

$config3 = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
$dotenv3 = new Dotenv($dir, $config3);
$dotenv3->bootEnv('test');

echo "  APP_ENV  : " . $dotenv3->get('APP_ENV') . "\n";
echo "  DB_NAME  : " . $dotenv3->get('DB_NAME') . "\n";
echo "  (note: .env.test.local would be skipped for 'test' env)\n";

// ── 4. Debug: which files were loaded ─────────────────────────────────────
echo "\n── debug() source tracking ───────────────────────────────\n";
$report = $dotenv2->debug();
$sources = array_unique(array_column($report, 'source'));
echo "  Sources loaded in staging cascade:\n";
foreach ($sources as $source) {
    echo "    • {$source}\n";
}

// Cleanup temp files
@unlink($stagingEnv);
@unlink($testEnv);

echo "\n✓ Example 07 completed.\n\n";
