<?php

declare(strict_types=1);

/**
 * Run All Examples
 *
 * Executes all kariricode/dotenv validation examples in sequence.
 * Each example is self-contained and demonstrates a specific feature.
 */

$examples = [
    '01-basic-loading'     => 'Basic Loading & get() method',
    '02-type-casting'      => 'Auto Type Casting (int, float, bool, null, JSON)',
    '03-validation-dsl'    => 'Fluent Validation DSL',
    '04-schema-validation' => 'Schema-Based Validation (.env.schema)',
    '05-encryption'        => 'AES-256-GCM Encryption & Decryption',
    '06-processors'        => 'Variable Processors (Trim, Base64, CSV, URL)',
    '07-boot-env'          => 'bootEnv() Cascade Loading',
    '08-env-helper'        => 'env() Helper Function (use function)',
];

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       KaririCode\\Dotenv — Full Feature Validation         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$total = count($examples);
$passed = 0;
$failed = 0;

foreach ($examples as $file => $description) {
    $path = __DIR__ . "/examples/{$file}.php";

    echo "┌─────────────────────────────────────────────────────────\n";
    echo "│  Running: {$description}\n";
    echo "└─────────────────────────────────────────────────────────\n";

    ob_start();
    try {
        require $path;
        $output = ob_get_clean();
        echo $output;
        ++$passed;
    } catch (Throwable $e) {
        $output = ob_get_clean();
        if ($output !== '') {
            echo $output;
        }
        echo "\n  ✗ EXCEPTION: " . $e::class . "\n";
        echo "    " . $e->getMessage() . "\n";
        ++$failed;
    }
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  RESULTS                                                  ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
printf("║  Examples: %-4d  Passed: %-4d  Failed: %-4d            ║\n", $total, $passed, $failed);
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($failed > 0) {
    echo "✗ Some examples failed. Review the output above.\n\n";
    exit(1);
}

echo "✓ All {$total} examples completed successfully!\n\n";
