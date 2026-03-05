<?php

declare(strict_types=1);

/**
 * Example 08: Composite, Interface, Namespace, and Structural filters.
 *
 * Demonstrates:
 *   - NamespaceFilter  — classes in a specific namespace prefix
 *   - InterfaceFilter  — classes implementing a given interface
 *   - StructuralFilter — final/readonly/abstract/enum/interface/trait
 *   - CompositeFilter  — AND / OR between multiple filters
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  NamespaceFilter(prefix: string):
 *    - Matches classes whose FQCN starts with prefix
 *
 *  InterfaceFilter(interface: string):
 *    - Matches classes implementing the given interface (FQCN)
 *
 *  StructuralFilter(isFinal: ?bool, isAbstract: ?bool, isReadonly: ?bool,
 *                   isInterface: ?bool, isEnum: ?bool, isTrait: ?bool):
 *    - Only criteria that are non-null are applied (null = don't care)
 *
 *  CompositeFilter::and(...filters): CompositeFilter
 *    - All filters must match (AND conjunction)
 *
 *  CompositeFilter::or(...filters): CompositeFilter
 *    - At least one filter must match (OR disjunction)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\ClassDiscovery\Example\Contract\Cacheable;
use KaririCode\ClassDiscovery\Example\Contract\Loggable;
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Filter\CompositeFilter;
use KaririCode\ClassDiscovery\Filter\InterfaceFilter;
use KaririCode\ClassDiscovery\Filter\NamespaceFilter;
use KaririCode\ClassDiscovery\Filter\StructuralFilter;
use KaririCode\ClassDiscovery\Scanner\ComposerNamespaceResolver;
use KaririCode\ClassDiscovery\Scanner\ReflectionScanner;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  Example 08: Composite, Interface & Structural Filters   ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$resolver = new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/../composer.json',
);

// Use ReflectionScanner so interfaces/traits data is available
$scanner = new ReflectionScanner($resolver);
$baseResult = $scanner->scan([__DIR__ . '/../src']);

echo "Total classes discovered: " . count($baseResult) . "\n\n";

// ── NamespaceFilter ─────────────────────────────────────────────
echo "── NamespaceFilter('KaririCode\\\\ClassDiscovery\\\\Example\\\\Service') ─\n";
$nsFilter = new NamespaceFilter('KaririCode\ClassDiscovery\Example\Service');
$nsResult = $baseResult->filter($nsFilter);
echo "  Classes in Service namespace: " . count($nsResult) . "\n";
foreach ($nsResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── InterfaceFilter ─────────────────────────────────────────────
echo "\n── InterfaceFilter(Loggable::class) ───────────────────────\n";
$loggableFilter = new InterfaceFilter(Loggable::class);
$loggables = $baseResult->filter($loggableFilter);
echo "  Classes implementing Loggable: " . count($loggables) . "\n";
foreach ($loggables as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

echo "\n── InterfaceFilter(Cacheable::class) ──────────────────────\n";
$cacheableFilter = new InterfaceFilter(Cacheable::class);
$cacheables = $baseResult->filter($cacheableFilter);
echo "  Classes implementing Cacheable: " . count($cacheables) . "\n";
foreach ($cacheables as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── StructuralFilter ────────────────────────────────────────────
echo "\n── StructuralFilter(isFinal: true) ────────────────────────\n";
$finalFilter = new StructuralFilter(isFinal: true);
$finals = $baseResult->filter($finalFilter);
echo "  Final classes: " . count($finals) . "\n";
foreach ($finals as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

echo "\n── StructuralFilter(isReadonly: true) ─────────────────────\n";
$readonlyFilter = new StructuralFilter(isReadonly: true);
$readonlies = $baseResult->filter($readonlyFilter);
echo "  Readonly classes: " . count($readonlies) . "\n";
foreach ($readonlies as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── CompositeFilter: AND ─────────────────────────────────────────
echo "\n── CompositeFilter::all(Loggable AND Cacheable) ───────────\n";
$andFilter = CompositeFilter::all(
    new InterfaceFilter(Loggable::class),
    new InterfaceFilter(Cacheable::class),
);
$andResult = $baseResult->filter($andFilter);
echo "  Classes implementing BOTH: " . count($andResult) . "\n";
foreach ($andResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── CompositeFilter: OR ──────────────────────────────────────────
echo "\n── CompositeFilter::any(Loggable OR Cacheable) ────────────\n";
$orFilter = CompositeFilter::any(
    new InterfaceFilter(Loggable::class),
    new InterfaceFilter(Cacheable::class),
);
$orResult = $baseResult->filter($orFilter);
echo "  Classes implementing EITHER: " . count($orResult) . "\n";
foreach ($orResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

// ── CompositeFilter: chained with AttributeFilter ────────────────
use KaririCode\ClassDiscovery\Example\Attribute\Service;
echo "\n── CompositeFilter::all(has #[Service] AND is final) ──────\n";
$chainedFilter = CompositeFilter::all(
    new AttributeFilter(Service::class),
    new StructuralFilter(isFinal: true),
);
$chainedResult = $baseResult->filter($chainedFilter);
echo "  Final #[Service] classes: " . count($chainedResult) . "\n";
foreach ($chainedResult as $fqcn => $meta) {
    echo "    ✔ {$fqcn}\n";
}

echo "\n  ✅ CompositeFilter / InterfaceFilter / NamespaceFilter / StructuralFilter: OK\n\n";
