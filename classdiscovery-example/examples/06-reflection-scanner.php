<?php

declare(strict_types=1);

/**
 * Example 06: ReflectionScanner — full metadata with instantiated attribute objects.
 *
 * Demonstrates: ReflectionScanner reads actual PHP Attribute instances via newInstance(),
 * giving direct access to constructor argument values (path, event, priority, etc.)
 * through the AttributeMetadata::$instance property.
 *
 * Contrast with FileScanner: token-based, no class-loading, no attribute instances.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  ReflectionScanner(resolver: NamespaceResolver):
 *    - Uses PHP's Reflection API — LOADS classes (triggers autoloader)
 *    - AttributeMetadata.instance: hydrated PHP attribute object (via newInstance())
 *    - ClassMetadata.methods[]    : full method metadata including parameters
 *    - ClassMetadata.properties[] : full property metadata with types
 *    ⚠️  Slower than FileScanner; only use when you need attribute constructor values
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\EventListener;
use KaririCode\ClassDiscovery\Example\Attribute\Route;
use KaririCode\ClassDiscovery\Example\Attribute\Service;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\ReflectionScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 06: ReflectionScanner — Instantiated Attributes ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

$scanner = new ReflectionScanner($resolver);

$result = $scanner->scan([__DIR__ . '/../src']);

echo "🔍  ReflectionScanner discovered " . count($result) . " class(es):\n\n";

foreach ($result as $fqcn => $metadata) {
    echo "  📋 {$fqcn}\n";

    // ── Class-level attributes with instantiated objects ──────────
    foreach ($metadata->attributes as $attrName => $attrMeta) {
        $instance = $attrMeta->instance;

        if ($instance instanceof Route) {
            echo "     🛣  #[Route] path=\"{$instance->path}\" method={$instance->method}\n";
        } elseif ($instance instanceof Service) {
            $singleton = $instance->singleton ? 'true' : 'false';
            echo "     ⚙  #[Service] id=\"{$instance->id}\" singleton={$singleton}\n";
        } elseif ($instance instanceof EventListener) {
            echo "     📡 #[EventListener] event=\"{$instance->event}\" priority={$instance->priority}\n";
        } elseif ($instance !== null) {
            // Generic fallback for other attributes
            echo "     📎 #[{$attrMeta->name}] args=" . json_encode($attrMeta->arguments) . "\n";
        }
    }

    // ── Method-level attributes ───────────────────────────────────
    foreach ($metadata->methods as $methodMeta) {
        foreach ($methodMeta->attributes as $attrMeta) {
            $instance = $attrMeta->instance;
            if ($instance instanceof Route) {
                echo "     └─ [{$methodMeta->name}()] #[Route] path=\"{$instance->path}\" method={$instance->method}\n";
            }
        }
    }

    // ── Property-level attributes (if any) ───────────────────────
    foreach ($metadata->properties as $propMeta) {
        foreach ($propMeta->attributes as $attrMeta) {
            if ($attrMeta->instance !== null) {
                echo "     └─ \${$propMeta->name} #[{$attrMeta->name}]\n";
            }
        }
    }

    // ── Structural metadata (exclusively from ReflectionScanner) ─
    echo "     ✏  Methods: " . count($metadata->methods)
        . " | Properties: " . count($metadata->properties)
        . " | Traits: " . count($metadata->traits) . "\n";
}

echo "\n  ✅ ReflectionScanner (instantiated attribute values): OK\n\n";
