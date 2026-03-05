<?php

declare(strict_types=1);

/**
 * Example 01: Basic FileScanner — scan a directory and list all discovered classes.
 *
 * Demonstrates: FileScanner, ComposerNamespaceResolver, DiscoveryResult
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  ComposerNamespaceResolver(composerJsonPath: string, includeDevAutoload: bool):
 *    - composerJsonPath    : path to composer.json
 *    - includeDevAutoload  : include autoload-dev namespaces (default false)
 *
 *  FileScanner(resolver: NamespaceResolver):
 *    - Token-based parser — does NOT load classes (fast, no side-effects)
 *    - ⚠️ methods[] and properties[] are NEVER populated (use ReflectionScanner)
 *
 *  $scanner->scan(directories: string[]): DiscoveryResult
 *    - Returns iterable DiscoveryResult; keys = FQCN, values = ClassMetadata
 *    - ClassMetadata: filePath, namespace, attributes[], isAbstract, isFinal,
 *      isReadonly, isInterface, isEnum, isTrait, parent, implements[]
 *
 *  $result->getScanDuration(): float (seconds)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 01: Basic FileScanner                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
    includeDevAutoload: false,
);

$scanner = new FileScanner($resolver);

$result = $scanner->scan([__DIR__ . '/../src']);

echo "📦 Discovered " . count($result) . " class(es) in src/\n\n";

foreach ($result as $fqcn => $metadata) {
    echo "  ✔ {$fqcn}\n";
    echo "    Attributes: " . implode(', ', array_keys($metadata->attributes)) . "\n";
    echo "    File:       " . basename($metadata->filePath) . "\n";
    echo "    Structure:  "
        . ($metadata->isFinal ? 'final ' : '')
        . ($metadata->isAbstract ? 'abstract ' : '')
        . ($metadata->isReadonly ? 'readonly ' : '')
        . ($metadata->isInterface ? 'interface' : ($metadata->isEnum ? 'enum' : ($metadata->isTrait ? 'trait' : 'class')))
        . ($metadata->parent !== null ? " extends {$metadata->parent}" : '')
        . "\n";
    // NOTE: methods/properties are NEVER populated by FileScanner (token-based parsing).
    // Use ReflectionScanner (example 06) to get full method + property metadata.
}

echo "\n  ⏱  Scan duration: " . round($result->getScanDuration() * 1000, 2) . "ms\n";
echo "\n  ✅ FileScanner: OK\n\n";
