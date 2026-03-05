<?php

declare(strict_types=1);

/**
 * Example 10: DirectoryScanner — depth control, glob pattern, symlink policy.
 *
 * Demonstrates:
 *   - DirectoryScanner::setMaxDepth() — limit recursion depth
 *   - DirectoryScanner::setPattern()  — custom file glob matching
 *   - DirectoryScanner::setFollowSymlinks() — symlink containment
 *   - Comparing shallow (depth=1) vs deep (depth=10) scans
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  DirectoryScanner:
 *    $scanner->setMaxDepth(depth: int)       — 0 = no limit, 1 = top dir only
 *    $scanner->setPattern(glob: string)       — e.g. '*.php', 'Abstract*.php'
 *    $scanner->setFollowSymlinks(bool)        — default false (prevents loops)
 *    $scanner->setExcludedPaths(paths: str[]) — skip specific directories
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\DirectoryScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 10: DirectoryScanner — Depth, Pattern, Symlinks ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$src = [__DIR__ . '/../src'];

// ── Default behavior: maxDepth=10, pattern=*.php, no symlinks ───
echo "── Default scan (maxDepth=10, *.php) ──────────────────────\n";
$scanner = new DirectoryScanner($resolver);
$result  = $scanner->scan($src);
echo "  📦 Discovered: " . count($result) . " class(es)\n";
foreach ($result as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── setMaxDepth(1): only top-level src/*.php files ───────────────
echo "\n── setMaxDepth(1): only top-level ─────────────────────────\n";
$shallowScanner = new DirectoryScanner($resolver);
$shallowScanner->setMaxDepth(1);
$shallowResult = $shallowScanner->scan($src);
echo "  📦 Discovered (depth=1): " . count($shallowResult) . " class(es) (top-level only)\n";
foreach ($shallowResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── setPattern: only files matching *Service.php ────────────────
echo "\n── setPattern('*Service.php') ─────────────────────────────\n";
$patternScanner = new DirectoryScanner($resolver);
$patternScanner->setPattern('*Service.php');
$patternResult = $patternScanner->scan($src);
echo "  📦 Matching *Service.php: " . count($patternResult) . " class(es)\n";
foreach ($patternResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── setPattern: filter to *Attribute.php (attribute definitions) ─
echo "\n── setPattern('*.php') + maxDepth(2) ──────────────────────\n";
$mixedScanner = new DirectoryScanner($resolver);
$mixedScanner->setMaxDepth(2)->setPattern('*.php');
$mixedResult = $mixedScanner->scan($src);
echo "  📦 Discovered (depth=2, *.php): " . count($mixedResult) . " class(es)\n";

// ── setFollowSymlinks: disabled (default, safe) ──────────────────
echo "\n── setFollowSymlinks(false) — default, symlinks skipped ───\n";
$symScanner = new DirectoryScanner($resolver);
$symScanner->setFollowSymlinks(false);
$symResult = $symScanner->scan($src);
echo "  🔒 Symlinks ignored. Discovered: " . count($symResult) . " class(es)\n";

echo "\n  ✅ DirectoryScanner (maxDepth, setPattern, setFollowSymlinks): OK\n\n";
