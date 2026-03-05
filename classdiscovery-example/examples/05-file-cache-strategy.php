<?php

declare(strict_types=1);

/**
 * Example 05: FileCacheStrategy — cache scan results to avoid re-scanning.
 *
 * Demonstrates: FileCacheStrategy, performance comparison cold vs warm cache.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  FileCacheStrategy(cachePath: string, ttl: int, version: ?string):
 *    - cachePath : directory for .php cache files (created automatically)
 *    - ttl       : cache time-to-live in seconds (default 3600)
 *    - version   : if provided, old cache files with different version are invalidated
 *    ⚠️  Cache is invalidated automatically when scanned file mtimes change.
 *
 *  $scanner->scan(dirs, filters, cache: CacheStrategy): DiscoveryResult
 *    - cache: pass a CacheStrategy to enable scan result caching
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Cache\FileCacheStrategy;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 05: File-based Cache Strategy                  ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$cacheDir = sys_get_temp_dir() . '/kariricode-classdiscovery-example-cache';
$cache    = new FileCacheStrategy($cacheDir);

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

// ── Cold scan (no cache) ────────────────────────────────────────
$cache->clear();
$scanner = new FileScanner($resolver);
$scanner->setCacheStrategy($cache);

$coldScanStart = hrtime(true);
$result = $scanner->scan([__DIR__ . '/../src']);
$coldMs = (hrtime(true) - $coldScanStart) / 1_000_000;

echo "❄  Cold scan  : " . count($result) . " class(es) in " . round($coldMs, 3) . "ms\n";

// ── Warm scan (from cache) ──────────────────────────────────────
$warmScanner = new FileScanner($resolver);
$warmScanner->setCacheStrategy($cache);

$warmScanStart = hrtime(true);
$warmResult = $warmScanner->scan([__DIR__ . '/../src']);
$warmMs = (hrtime(true) - $warmScanStart) / 1_000_000;

echo "🔥  Warm scan  : " . count($warmResult) . " class(es) in " . round($warmMs, 3) . "ms\n";

$speedup = $coldMs > 0 ? round($coldMs / max($warmMs, 0.001), 1) : '∞';
echo "⚡  Speedup    : {$speedup}×\n";

// ── Cleanup ─────────────────────────────────────────────────────
$cache->clear();
echo "\n  ✅ FileCacheStrategy: OK\n\n";
