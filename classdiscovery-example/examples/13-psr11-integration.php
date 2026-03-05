<?php

declare(strict_types=1);

/**
 * Example 13: PSR11Integration + ConfiguratorBridge.
 *
 * Demonstrates integration helpers designed for framework/container use:
 *   - PSR11Integration::createDefaultScanner()  — ready-to-use Scanner factory
 *   - PSR11Integration::createAttributeScanner() — AttributeScanner factory
 *   - PSR11Integration::createReflectionScanner() — ReflectionScanner factory
 *   - PSR11Integration::createCircularDetector()  — CircularDetector factory
 *   - ConfiguratorBridge::createScanner()         — build scanner from config array
 *   - ConfiguratorBridge::createCacheStrategy()   — build ChainCacheStrategy from config
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  PSR11Integration (static factory methods):
 *    ::createDefaultScanner(config: array): FileScanner     — with optional cache
 *    ::createAttributeScanner(config: array): AttributeScanner  — production-ready
 *    ::createReflectionScanner(config: array): ReflectionScanner — full metadata
 *    ::createCircularDetector(config: array): CircularDetector
 *
 *  ConfiguratorBridge (static factory from config array):
 *    ::createScanner(config['scanner']): Scanner
 *      config keys: type (file|reflection|attribute), dirs, exclude, maxDepth
 *    ::createCacheStrategy(config['cache']): CacheStrategy
 *      config keys: driver (file|memory|chain), path, ttl, version
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Cache\ChainCacheStrategy;
use KaririCode\ClassDiscovery\Contract\Scanner;
use KaririCode\ClassDiscovery\Integration\ConfiguratorBridge;
use KaririCode\ClassDiscovery\Integration\PSR11Integration;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 13: PSR11Integration + ConfiguratorBridge       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$src = [__DIR__ . '/../src'];

// ── PSR11Integration::createDefaultScanner ──────────────────────
echo "── PSR11Integration::createDefaultScanner() ───────────────\n";
$scanner = PSR11Integration::createDefaultScanner();
echo "  Scanner class: " . $scanner::class . "\n";
$result  = $scanner->scan($src);
echo "  Discovered: " . count($result) . " class(es)\n";

// ── PSR11Integration::createAttributeScanner ────────────────────
echo "\n── PSR11Integration::createAttributeScanner() ─────────────\n";
$attrScanner = PSR11Integration::createAttributeScanner();
echo "  Scanner class: " . $attrScanner::class . "\n";
$attrResult = $attrScanner->scan($src);
echo "  Discovered: " . count($attrResult) . " class(es)\n";

// ── PSR11Integration::createReflectionScanner ───────────────────
echo "\n── PSR11Integration::createReflectionScanner() ────────────\n";
$refScanner = PSR11Integration::createReflectionScanner();
echo "  Scanner class: " . $refScanner::class . "\n";
$refResult  = $refScanner->scan($src);
echo "  Discovered: " . count($refResult) . " class(es) (with full reflection metadata)\n";

// ── PSR11Integration::createCircularDetector ────────────────────
echo "\n── PSR11Integration::createCircularDetector() ─────────────\n";
$detector = PSR11Integration::createCircularDetector(throwOnDetection: false);
$cycles   = $detector->check($refResult);
echo "  Circular dependencies detected: " . count($cycles) . "\n";

// ── ConfiguratorBridge::createScanner (array config) ────────────
echo "\n── ConfiguratorBridge::createScanner(config array) ────────\n";
$cacheDir = sys_get_temp_dir() . '/kariricode-configurator-bridge-cache';
$config = [
    'composer_json' => __DIR__ . '/../composer.json',
    'include_dev'   => false,
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'max_files'     => 5000,
    'cache'         => [
        'enabled' => true,
        'dir'     => $cacheDir,
    ],
];

$configuredScanner = ConfiguratorBridge::createScanner($config);
echo "  Scanner class: " . $configuredScanner::class . "\n";
$configuredResult  = $configuredScanner->scan($src);
echo "  Discovered: " . count($configuredResult) . " class(es)\n";

// ── ConfiguratorBridge::createCacheStrategy ─────────────────────
echo "\n── ConfiguratorBridge::createCacheStrategy(config) ────────\n";
$cacheStrategy = ConfiguratorBridge::createCacheStrategy(['dir' => $cacheDir]);
echo "  Strategy class: " . $cacheStrategy::class . "\n";
$isChain = $cacheStrategy instanceof ChainCacheStrategy;
echo "  Is ChainCacheStrategy (Memory+File): " . ($isChain ? 'YES' : 'NO') . "\n";

// ── ConfiguratorBridge with cache disabled ───────────────────────
echo "\n── ConfiguratorBridge::createScanner(cache disabled) ──────\n";
$configNocache = ['composer_json' => __DIR__ . '/../composer.json', 'cache' => ['enabled' => false]];
$noCacheScanner = ConfiguratorBridge::createScanner($configNocache);
$noCacheResult  = $noCacheScanner->scan($src);
echo "  Scanner class: " . $noCacheScanner::class . "\n";
echo "  Discovered: " . count($noCacheResult) . " class(es) (no cache)\n";

// Cleanup
if (is_dir($cacheDir)) {
    array_map('unlink', glob("{$cacheDir}/*.php") ?: []);
    @rmdir($cacheDir);
}

echo "\n  ✅ PSR11Integration + ConfiguratorBridge: OK\n\n";
