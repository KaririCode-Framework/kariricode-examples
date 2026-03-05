<?php

declare(strict_types=1);

/**
 * Example 09: ChainCacheStrategy — Memory (L1) + File (L2) tiered caching.
 *
 * Demonstrates:
 *   - MemoryCacheStrategy  — in-process, zero I/O, ideal for dev/tests
 *   - ChainCacheStrategy   — multi-tier with automatic L1 promotion on L2 hit
 *   - Performance comparison: cold → L2 warm → L1 warm
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  MemoryCacheStrategy():
 *    - In-process array cache, no persistence between requests
 *    - Zero I/O overhead; ideal for tests and dev with multiple scans
 *
 *  ChainCacheStrategy(strategies: CacheStrategy[]):
 *    - Checks L1 first, falls back to L2, promotes result back to L1 on hit
 *    - strategies: ordered from fastest to slowest (e.g. [Memory, File])
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Cache\ChainCacheStrategy;
use KaririCode\ClassDiscovery\Cache\FileCacheStrategy;
use KaririCode\ClassDiscovery\Cache\MemoryCacheStrategy;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 09: ChainCacheStrategy — Memory + File Tiers   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$cacheDir = sys_get_temp_dir() . '/kariricode-classdiscovery-chain-cache';
$src      = [__DIR__ . '/../src'];

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

// ── MemoryCacheStrategy (standalone) ───────────────────────────
echo "── MemoryCacheStrategy (in-process, no I/O) ───────────────\n";
$memory  = new MemoryCacheStrategy();
$scanner = new FileScanner($resolver);
$scanner->setCacheStrategy($memory);

$t0       = hrtime(true);
$result   = $scanner->scan($src);
$coldMs   = (hrtime(true) - $t0) / 1_000_000;
echo "  ❄  Cold scan  : " . count($result) . " class(es) in " . round($coldMs, 3) . "ms\n";

$t0       = hrtime(true);
$result   = $scanner->scan($src);
$warmMs   = (hrtime(true) - $t0) / 1_000_000;
echo "  🔥 Warm scan  : " . count($result) . " class(es) in " . round($warmMs, 3) . "ms\n";
$speedup  = $coldMs > 0 ? round($coldMs / max($warmMs, 0.001), 1) : '∞';
echo "  ⚡ Speedup    : {$speedup}×\n\n";

// ── ChainCacheStrategy: L1 memory + L2 file ─────────────────────
echo "── ChainCacheStrategy: Memory (L1) → File (L2) ────────────\n";
$fileCache = new FileCacheStrategy($cacheDir);
$fileCache->clear(); // start cold

$chain           = new ChainCacheStrategy(new MemoryCacheStrategy(), $fileCache);
$chainedScanner  = new FileScanner($resolver);
$chainedScanner->setCacheStrategy($chain);

// Cold scan — writes to both L1 and L2
$t0       = hrtime(true);
$result   = $chainedScanner->scan($src);
$coldMs   = (hrtime(true) - $t0) / 1_000_000;
echo "  ❄  Cold scan  : " . count($result) . " class(es) in " . round($coldMs, 3) . "ms (L1 + L2 miss)\n";

// L1 hit (memory, same process)
$t0       = hrtime(true);
$result   = $chainedScanner->scan($src);
$l1Ms     = (hrtime(true) - $t0) / 1_000_000;
echo "  🧠 L1 hit     : " . count($result) . " class(es) in " . round($l1Ms, 3) . "ms (memory)\n";

// Simulate a new process: L1 miss → L2 hit + auto-promote to new L1
$newMemory = new MemoryCacheStrategy();
$newChain  = new ChainCacheStrategy($newMemory, $fileCache); // same file cache
$newScanner = new FileScanner($resolver);
$newScanner->setCacheStrategy($newChain);

$t0    = hrtime(true);
$result = $newScanner->scan($src);
$l2Ms  = (hrtime(true) - $t0) / 1_000_000;
echo "  💾 L2 hit     : " . count($result) . " class(es) in " . round($l2Ms, 3) . "ms (file, promoted to L1)\n";

// Second call to newScanner: now L1 promoted
$t0    = hrtime(true);
$result = $newScanner->scan($src);
$l1PromotedMs = (hrtime(true) - $t0) / 1_000_000;
echo "  🧠 L1 promoted: " . count($result) . " class(es) in " . round($l1PromotedMs, 3) . "ms (after L2→L1 promotion)\n";

// Cleanup
$fileCache->clear();

echo "\n  ✅ MemoryCacheStrategy + ChainCacheStrategy: OK\n\n";
