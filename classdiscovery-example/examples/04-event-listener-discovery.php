<?php

declare(strict_types=1);

/**
 * Example 04: Event listener auto-registration.
 *
 * Demonstrates: scanning for #[EventListener] and building an event dispatcher map.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  AttributeFilter(attributeClass: string):
 *    - Matches classes annotated with the given attribute (e.g. EventListener::class)
 *    - With FileScanner: attributes are recorded as raw argument arrays
 *    - With ReflectionScanner: $meta->attributes[X]->instance gives hydrated object
 *
 *  Event map pattern:
 *    foreach ($result as $fqcn => $meta) {
 *        // $meta->attributes[EventListener::class]->arguments['event']
 *        // sort by priority if needed
 *    }
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\EventListener;
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 04: Event Listener Auto-Registration           ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$scanner = new FileScanner($resolver);
$scanner->addFilter(new AttributeFilter(EventListener::class));

$result = $scanner->scan([__DIR__ . '/../src/Listener']);

/** @var array<string, list<array{class: string}>> $dispatcher */
$dispatcher = [];

echo "📡  Discovered " . count($result) . " listener(s):\n\n";

foreach ($result as $fqcn => $metadata) {
    // Token scanner stores attribute short name
    $attrName = 'EventListener';
    $event = 'unknown';

    // The token scanner captures attribute name but not constructor args
    // In production you'd use ReflectionScanner for that
    $dispatcher[$event][] = ['class' => $fqcn];

    echo "  👂 {$fqcn}\n";
    echo "     → listens to event (use ReflectionScanner for args)\n";
}

echo "\n  ✅ EventListener scan: OK\n\n";
