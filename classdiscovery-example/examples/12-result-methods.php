<?php

declare(strict_types=1);

/**
 * Example 12: DiscoveryResult — filter(), merge(), hasErrors(), getErrors().
 *
 * Demonstrates post-scan processing of DiscoveryResult:
 *   - result->filter() — creates a new DiscoveryResult with a filter applied
 *   - result->merge()  — combines two results into one (e.g., multi-module scan)
 *   - result->hasErrors() / getErrors() — inspect parse failures
 *   - result->hasClass() / getClass()   — FQCN lookup
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  DiscoveryResult methods:
 *    filter(callable $fn): DiscoveryResult  — $fn(fqcn, ClassMetadata): bool
 *    merge(DiscoveryResult $other): DiscoveryResult  — combines results (later wins)
 *    hasErrors(): bool    — true if any file failed to parse
 *    getErrors(): array   — list of [file, message] parse error entries
 *    hasClass(fqcn: string): bool
 *    getClass(fqcn: string): ?ClassMetadata
 *    count(): int         — total number of discovered classes
 *    getScanDuration(): float  — total scan time in seconds
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Attribute\Service;
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Filter\NamespaceFilter;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\FileScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 12: DiscoveryResult — Post-Scan Processing     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

// Scan two subtrees independently
$scannerA = new FileScanner($resolver);
$resultA  = $scannerA->scan([__DIR__ . '/../src/Controller']);

$scannerB = new FileScanner($resolver);
$resultB  = $scannerB->scan([__DIR__ . '/../src/Service']);

echo "📂 Scan A (Controller): " . count($resultA) . " class(es)\n";
echo "📂 Scan B (Service):    " . count($resultB) . " class(es)\n";

// ── merge() ──────────────────────────────────────────────────────
$merged = $resultA->merge($resultB);
echo "\n── merge(resultA, resultB) ────────────────────────────────\n";
echo "  🔗 Merged total: " . count($merged) . " class(es)\n";
echo "  ⏱  Combined duration: " . round($merged->getScanDuration() * 1000, 3) . "ms\n";

// ── filter() on a merged result ──────────────────────────────────
echo "\n── merged->filter(AttributeFilter(Service)) ───────────────\n";
$attrFilter    = new AttributeFilter(Service::class);
$servicesOnly  = $merged->filter($attrFilter);
echo "  ⚙  Services after filter: " . count($servicesOnly) . "\n";
foreach ($servicesOnly as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── filter() with NamespaceFilter ────────────────────────────────
$allSrc = (new FileScanner($resolver))->scan([__DIR__ . '/../src']);
echo "\n── allSrc->filter(NamespaceFilter('...Listener')) ─────────\n";
$nsFilter  = new NamespaceFilter('KaririCode\ClassDiscovery\Example\Listener');
$listeners = $allSrc->filter($nsFilter);
echo "  👂 Listener namespace classes: " . count($listeners) . "\n";
foreach ($listeners as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── hasClass() / getClass() ──────────────────────────────────────
echo "\n── hasClass() / getClass() ────────────────────────────────\n";
$testFqcn    = 'KaririCode\ClassDiscovery\Example\Service\MailerService';
$hasIt       = $allSrc->hasClass($testFqcn);
echo "  hasClass('{$testFqcn}'): " . ($hasIt ? 'true' : 'false') . "\n";
if ($hasIt) {
    $classMeta = $allSrc->getClass($testFqcn);
    echo "  getClass() → namespace: {$classMeta->namespace}, shortName: {$classMeta->shortName}\n";
}

// ── hasErrors() / getErrors() ────────────────────────────────────
echo "\n── hasErrors() / getErrors() ──────────────────────────────\n";
echo "  hasErrors: " . ($allSrc->hasErrors() ? 'true' : 'false') . "\n";
if ($allSrc->hasErrors()) {
    foreach ($allSrc->getErrors() as $file => $error) {
        echo "  ⚠  {$file}: {$error}\n";
    }
} else {
    echo "  ✅ Scan completed with no parse errors.\n";
}

echo "\n  ✅ DiscoveryResult (filter, merge, hasClass, getClass, hasErrors, getErrors): OK\n\n";
