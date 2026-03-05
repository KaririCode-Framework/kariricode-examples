<?php

declare(strict_types=1);

/**
 * Example 02: AttributeFilter — find only classes with a specific attribute.
 *
 * Demonstrates: AttributeFilter, FileScanner, filtering by #[Route]
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  AttributeFilter(attributeClass: string):
 *    - attributeClass : fully-qualified attribute class name (e.g. Route::class)
 *    - Matches classes whose metadata has an entry in $meta->attributes[]
 *    - ⚠️ With FileScanner: uses raw class name string (no instantiation)
 *    - ⚠️ With ReflectionScanner: has access to instantiated attribute objects
 *
 *  $scanner->scan(dirs, filters: Filter[]): DiscoveryResult
 *    - filters: applied as AND conjunction (class must pass ALL filters)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\Route;
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 02: Attribute Filter — Route Discovery         ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$scanner = new FileScanner($resolver);
$scanner->addFilter(new AttributeFilter(Route::class));

$result = $scanner->scan([__DIR__ . '/../src/Controller']);

echo "🛣  Controllers with #[Route]: " . count($result) . "\n\n";

foreach ($result as $fqcn => $metadata) {
    echo "  📌 {$fqcn}\n";

    // Class-level route
    if ($metadata->hasAttribute('Route')) {
        $baseRoute = $metadata->getAttribute('Route');
        echo "     Class route: {$baseRoute->name}(path=...)\n";
    }

    // Method-level routes
    foreach ($metadata->methods as $methodMeta) {
        if ($methodMeta->hasAttribute('Route')) {
            echo "     Method [{$methodMeta->name}]: #[Route]\n";
        }
    }
}

echo "\n  ✅ AttributeFilter (Route): OK\n\n";
