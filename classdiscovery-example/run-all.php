#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Run all kariricode/classdiscovery examples in sequence.
 * Validates the library end-to-end against real use cases.
 *
 * Usage: php run-all.php
 */

$examples = [
    '01' => 'Basic FileScanner',
    '02' => 'Attribute Filter (Route)',
    '03' => 'Service Auto-Registration',
    '04' => 'Event Listener Discovery',
    '05' => 'File Cache Strategy',
    '06' => 'ReflectionScanner + Attribute Instances',
    '07' => 'AttributeScanner — scanForAttribute / scanForAttributes',
    '08' => 'CompositeFilter / InterfaceFilter / NamespaceFilter / StructuralFilter',
    '09' => 'ChainCacheStrategy — Memory (L1) + File (L2)',
    '10' => 'DirectoryScanner — maxDepth, setPattern, setFollowSymlinks',
    '11' => 'DependencyAnalyzer + CircularDetector',
    '12' => 'DiscoveryResult — filter, merge, hasClass, getErrors',
    '13' => 'PSR11Integration + ConfiguratorBridge',
];

$examplesDir = __DIR__ . '/examples';
$passed      = 0;
$failed      = 0;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        kariricode/classdiscovery — Example Validation        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

foreach ($examples as $number => $name) {
    $file = "{$examplesDir}/{$number}-*.php";
    $files = glob($file);

    if (empty($files)) {
        echo "\n  ⚠  [{$number}] {$name} — file not found\n";
        ++$failed;
        continue;
    }

    $script = $files[0];
    $output = null;
    $exitCode = null;

    exec("php -f " . escapeshellarg($script) . " 2>&1", $output, $exitCode);

    if ($exitCode === 0) {
        echo implode("\n", $output) . "\n";
        ++$passed;
    } else {
        echo "\n  ✗  [{$number}] {$name} FAILED (exit {$exitCode}):\n";
        foreach ($output as $line) {
            echo "       {$line}\n";
        }
        ++$failed;
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Results: {$passed} passed";
if ($failed > 0) {
    echo " / {$failed} failed";
}
$total = $passed + $failed;
echo " ({$total} total)";
$padding = str_repeat(' ', max(0, 43 - strlen("{$passed} passed") - strlen($failed > 0 ? " / {$failed} failed" : "") - strlen("({$total} total)")));
echo "{$padding}║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

exit($failed > 0 ? 1 : 0);
