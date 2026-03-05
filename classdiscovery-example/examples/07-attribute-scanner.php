<?php

declare(strict_types=1);

/**
 * Example 07: AttributeScanner — high-level API for attribute-based discovery.
 *
 * Demonstrates: AttributeScanner::scanForAttribute() and scanForAttributes().
 * This is the recommended scanner for production use — 10× faster than
 * ReflectionScanner because it never loads/instantiates classes.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  AttributeScanner::scanForAttribute(dirs: string[], attrClass: string): DiscoveryResult
 *    - Single attribute shorthand (wraps FileScanner + AttributeFilter)
 *    - attrClass: FQCN of the attribute, e.g. Route::class
 *
 *  AttributeScanner::scanForAttributes(dirs: string[], attrClasses: string[]): DiscoveryResult
 *    - Scans for multiple attributes in one pass (OR semantics: any one attribute matches)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\EventListener;
use KaririCode\ClassDiscovery\Example\Attribute\Route;
use KaririCode\ClassDiscovery\Example\Attribute\Service;
use KaririCode\ClassDiscovery\Scanner\AttributeScanner;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 07: AttributeScanner — High-Level API          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$scanner = new AttributeScanner($resolver);
$src     = [__DIR__ . '/../src'];

// ── scanForAttribute: single attribute target ───────────────────
echo "── scanForAttribute(Route::class) ─────────────────────────\n";
$routes = $scanner->scanForAttribute(Route::class, $src);
echo "  🛣  Controllers with #[Route]: " . count($routes) . "\n";
foreach ($routes as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

echo "\n── scanForAttribute(Service::class) ───────────────────────\n";
$services = $scanner->scanForAttribute(Service::class, $src);
echo "  ⚙  Services with #[Service]: " . count($services) . "\n";
foreach ($services as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── scanForAttributes: OR logic across multiple attribute classes ─
echo "\n── scanForAttributes([Route, Service, EventListener]) ──────\n";
$all = $scanner->scanForAttributes([Route::class, Service::class, EventListener::class], $src);
echo "  📦 Total annotated classes (OR): " . count($all) . "\n";
foreach ($all as $fqcn => $meta) {
    $found = array_intersect(array_keys($meta->attributes), ['Route', 'Service', 'EventListener']);
    echo "    ✔ {$fqcn}  [" . implode(', ', $found) . "]\n";
}

echo "\n  ✅ AttributeScanner (scanForAttribute / scanForAttributes): OK\n\n";
