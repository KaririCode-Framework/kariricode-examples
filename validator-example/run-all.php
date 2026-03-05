<?php

declare(strict_types=1);

/**
 * run-all.php — Runs all 6 validator examples in sequence.
 *
 * Usage:
 *   php run-all.php
 *
 * Each example is run as a child process so that failures are isolated.
 * A green summary is printed at the end.
 */

$examples = [
    'examples/01-user-registration.php',
    'examples/02-ecommerce-product.php',
    'examples/03-brazilian-documents.php',
    'examples/04-crossfield-financial.php',
    'examples/05-logic-advanced.php',
    'examples/06-all-rules-coverage.php',
];

$php   = PHP_BINARY;
$root  = __DIR__;
$ok    = 0;
$fail  = 0;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       KaririCode Validator — Example Suite               ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

foreach ($examples as $script) {
    $path  = "{$root}/{$script}";
    $label = basename($script);

    echo "┌── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo "│\n";

    // Run the example and capture output + exit code
    $output   = [];
    $exitCode = 0;
    exec("{$php} " . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

    foreach ($output as $line) {
        echo "│  {$line}\n";
    }

    echo "│\n";
    if ($exitCode === 0) {
        echo "└── ✅ PASSED\n\n";
        ++$ok;
    } else {
        echo "└── ❌ FAILED (exit code {$exitCode})\n\n";
        ++$fail;
    }
}

echo "╔══════════════════════════════════════════════════════════╗\n";
printf("║  Results: %d/%d passed", $ok, count($examples));
echo str_repeat(' ', 46 - strlen("  Results: {$ok}/" . count($examples) . " passed")) . "║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

if ($fail > 0) {
    printf("❌  %d example(s) FAILED — review output above.\n\n", $fail);
    exit(1);
}

echo "✅  All examples passed — kariricode/validator is working correctly!\n\n";
