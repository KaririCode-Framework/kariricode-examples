<?php

declare(strict_types=1);

/**
 * Example 11: DependencyAnalyzer + CircularDetector.
 *
 * Demonstrates:
 *   - DependencyAnalyzer::buildGraph() — builds dependency graph from scan results
 *   - DependencyAnalyzer::detectCircularDependencies() — Tarjan-based cycle detection
 *   - CircularDetector::check() — high-level wrapper with optional exception throwing
 *
 * Requires ReflectionScanner (constructors and type hints need reflection).
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  DependencyAnalyzer::buildGraph(result: DiscoveryResult): DependencyGraph
 *    - Builds directed graph: FQCN → list of direct dependencies
 *    - Dependencies resolved from constructor param type hints (ReflectionScanner)
 *
 *  DependencyAnalyzer::detectCircularDependencies(graph: DependencyGraph): string[][]
 *    - Tarjan SCC algorithm; returns list of cycles (each cycle is a list of FQCNs)
 *
 *  CircularDetector::check(dirs: string[], throw: bool):
 *    - throw = true → throws CircularDependencyException on first cycle found
 *    - throw = false → returns list of cycle descriptions as strings
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Analyzer\CircularDetector;
use KaririCode\ClassDiscovery\Analyzer\DependencyAnalyzer;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\ReflectionScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 11: DependencyAnalyzer + CircularDetector       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

// ReflectionScanner: populates constructor params, interfaces, traits
$scanner = new ReflectionScanner($resolver);
$result  = $scanner->scan([__DIR__ . '/../src']);

echo "📦 Scan result: " . count($result) . " class(es)\n\n";

// ── DependencyAnalyzer: build dependency graph ───────────────────
$analyzer = new DependencyAnalyzer();
$graph    = $analyzer->buildGraph($result);

echo "── Dependency Graph ───────────────────────────────────────\n";
foreach ($graph as $class => $deps) {
    $shortClass = substr($class, strrpos($class, '\\') + 1);
    if ($deps === []) {
        echo "  {$shortClass}  → (no dependencies)\n";
    } else {
        $shortDeps = array_map(
            static fn (string $d): string => substr($d, strrpos($d, '\\') + 1),
            $deps,
        );
        echo "  {$shortClass}  → " . implode(', ', $shortDeps) . "\n";
    }
}

// ── DependencyAnalyzer: detect circular dependencies ────────────
echo "\n── Circular Dependency Detection ──────────────────────────\n";
$cycles = $analyzer->detectCircularDependencies($graph);
if ($cycles === []) {
    echo "  ✅ No circular dependencies detected.\n";
} else {
    echo "  ⚠  Circular dependency cycle(s) found:\n";
    foreach ($cycles as $cycle) {
        $shortCycle = array_map(
            static fn (string $c): string => substr($c, strrpos($c, '\\') + 1),
            $cycle,
        );
        echo "    → " . implode(' → ', $shortCycle) . "\n";
    }
}

// ── CircularDetector: high-level wrapper (no throw) ─────────────
echo "\n── CircularDetector (throwOnDetection=false) ──────────────\n";
$detector = new CircularDetector($analyzer, throwOnDetection: false);
$detectedCycles = $detector->check($result);
echo "  Cycles detected: " . count($detectedCycles) . "\n";

// ── CircularDetector: high-level wrapper (throw on detection) ───
echo "\n── CircularDetector (throwOnDetection=true) ───────────────\n";
try {
    $strictDetector = new CircularDetector($analyzer, throwOnDetection: true);
    $strictDetector->check($result);
    echo "  ✅ No cycles — exception NOT thrown.\n";
} catch (\KaririCode\ClassDiscovery\Exception\DiscoveryException $e) {
    echo "  ⚠  Exception thrown: " . $e->getMessage() . "\n";
}

echo "\n  ✅ DependencyAnalyzer + CircularDetector: OK\n\n";
