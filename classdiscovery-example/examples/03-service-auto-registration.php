<?php

declare(strict_types=1);

/**
 * Example 03: Service auto-registration simulation.
 *
 * Demonstrates: scanning for #[Service] and building a DI container map.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  AttributeFilter(attributeClass: string):
 *    - Matches classes annotated with the given PHP Attribute
 *    - AttributeMetadata.arguments[] : raw constructor argument values
 *      (use ReflectionScanner for instantiated attribute objects)
 *
 *  Registration pattern:
 *    foreach ($result as $fqcn => $meta) {
 *        $args = $meta->attributes[Service::class]->arguments; // raw values
 *        $container->bind($args['interface'] ?? $fqcn, $fqcn);
 *    }
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\Service;
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 03: Service Auto-Registration (DI Container)   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$scanner = new FileScanner($resolver);
$scanner->addFilter(new AttributeFilter(Service::class));

$result = $scanner->scan([__DIR__ . '/../src/Service']);

/** @var array<string, array{class: string, singleton: bool}> $container */
$container = [];

echo "⚙  Auto-registered services:\n\n";

foreach ($result as $fqcn => $metadata) {
    // Token scanner stores short attribute name
    $attrMeta = $metadata->getAttribute('Service') ?? null;
    $serviceId = $fqcn; // fallback to FQCN

    // Build the container registration map
    $container[$serviceId] = [
        'class'     => $fqcn,
        'singleton' => true, // default
    ];

    echo "  🔧 {$fqcn}\n";
    echo "     → registered as: {$serviceId}\n";
    echo "     → singleton: yes (default)\n";
}

echo "\n  Container map (JSON):\n";
echo json_encode($container, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "\n  ✅ Service auto-registration: OK\n\n";
