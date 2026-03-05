<?php

declare(strict_types=1);

/**
 * Example 08: env() Helper Function
 *
 * The env() helper is available in the KaririCode\Dotenv namespace.
 * Import it with `use function KaririCode\Dotenv\env;` to use it
 * globally throughout your application — just like Laravel's env().
 *
 * Features:
 *   - Auto type casting (same TypeSystem as $dotenv->get())
 *   - Resolution order: $_ENV → $_SERVER → getenv() → $default
 *   - Works after any load() call (safe to call from anywhere in your app)
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  env(key: string, default: mixed = null): mixed
 *    - key     : variable name (case-sensitive)
 *    - default : returned when key is not found in $_ENV/$_SERVER/getenv()
 *    - Uses the same TypeSystem pipeline as $dotenv->get() — returns typed value
 *    - ⚠️ Requires at least one $dotenv->load() call before usage.
 *    - ⚠️ Import with: use function KaririCode\Dotenv\env;
 *
 *  env() vs $dotenv->get():
 *    - Identical results — both use the same TypeSystem
 *    - env() reads from superglobals (no Dotenv instance needed)
 *    - $dotenv->get() reads from the internal loaded state
 */

require_once __DIR__ . '/../vendor/autoload.php';

use function KaririCode\Dotenv\env;

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  08 — env() Helper Function\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// ── Load once at bootstrap ─────────────────────────────────────────────────
$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite, typeCasting: true);
$dotenv = new Dotenv(__DIR__ . '/..', $config);
$dotenv->load();

// ── After load(), env() reads from $_ENV / $_SERVER / getenv() ────────────
echo "── Basic usage — env(key) ────────────────────────────────\n";
echo "  APP_NAME    : " . env('APP_NAME') . "\n";
echo "  APP_ENV     : " . env('APP_ENV') . "\n";
echo "  APP_VERSION : " . env('APP_VERSION') . "\n";

// ── Default value when key is absent ──────────────────────────────────────
echo "\n── Default fallback — env(key, default) ──────────────────\n";
echo "  REDIS_HOST  : " . (env('REDIS_HOST', 'localhost')) . " (default)\n";
echo "  REDIS_PORT  : " . (env('REDIS_PORT', 6379)) . " (default)\n";
echo "  LOG_LEVEL   : " . (env('LOG_LEVEL', 'info')) . " (default)\n";

// ── Auto type casting (same engine as $dotenv->get()) ─────────────────────
echo "\n── Auto type casting ─────────────────────────────────────\n";

$values = [
    'DB_PORT'         => env('DB_PORT'),           // int
    'APP_DEBUG'       => env('APP_DEBUG'),          // bool
    'CACHE_ENABLED'   => env('CACHE_ENABLED'),      // bool
    'PI_VALUE'        => env('PI_VALUE'),           // float
    'NULLABLE_VAR'    => env('NULLABLE_VAR'),       // null
    'JSON_CONFIG'     => env('JSON_CONFIG'),        // array
];

foreach ($values as $key => $value) {
    $type = get_debug_type($value);
    $display = match (true) {
        is_array($value)    => json_encode($value),
        is_bool($value)     => $value ? 'true' : 'false',
        is_null($value)     => 'null',
        default             => (string) $value,
    };
    printf("  %-18s (%s): %s\n", $key, $type, $display);
}

// ── JSON sub-key access ────────────────────────────────────────────────────
echo "\n── JSON sub-key access ───────────────────────────────────\n";
$config = env('JSON_CONFIG');
if (is_array($config)) {
    echo "  JSON_CONFIG['theme'] : " . $config['theme'] . "\n";
    echo "  JSON_CONFIG['lang']  : " . $config['lang'] . "\n";
    echo "  JSON_CONFIG['items'] : " . $config['items'] . " (" . get_debug_type($config['items']) . ")\n";
}

// ── Typical application bootstrap pattern ─────────────────────────────────
echo "\n── Typical bootstrap pattern ─────────────────────────────\n";
echo "  (after one Dotenv::load() in bootstrap, env() is available everywhere)\n\n";

// Simulate a config class using env() — no Dotenv instance needed
final class DatabaseConfig
{
    public readonly string  $host;
    public readonly int     $port;
    public readonly string  $name;
    public readonly string  $user;
    public readonly string  $password;
    public readonly int     $poolSize;

    public function __construct()
    {
        $this->host     = (string) env('DB_HOST', 'localhost');
        $this->port     = (int)    env('DB_PORT', 5432);
        $this->name     = (string) env('DB_NAME', 'app');
        $this->user     = (string) env('DB_USER', 'root');
        $this->password = (string) env('DB_PASSWORD', '');
        $this->poolSize = (int)    env('DB_POOL_SIZE', 5);
    }
}

$db = new DatabaseConfig();
printf("  DB: %s@%s:%d/%s (pool=%d)\n",
    $db->user, $db->host, $db->port, $db->name, $db->poolSize);

// ── Comparison: env() vs $dotenv->get() ───────────────────────────────────
echo "\n── env() vs \$dotenv->get() ───────────────────────────────\n";
echo "  env('APP_PORT')            = " . env('APP_PORT') . " (" . get_debug_type(env('APP_PORT')) . ")\n";
echo "  \$dotenv->get('APP_PORT')  = " . $dotenv->get('APP_PORT') . " (" . get_debug_type($dotenv->get('APP_PORT')) . ")\n";
echo "  (identical — both use the same TypeSystem pipeline)\n";

echo "\n✓ Example 08 completed.\n\n";
