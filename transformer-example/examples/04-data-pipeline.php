<?php

declare(strict_types=1);

/**
 * Example 04 — Data Pipeline: Structure, Dot Notation & Advanced Engine Features
 *
 * Demonstrates the most advanced Engine API capabilities:
 *   - flatten / unflatten: nested array ↔ flat dotted keys
 *   - pluck: extract one field from a list of records
 *   - group_by: partition a list by a field value
 *   - rename_keys: remap field names (useful for schema migrations)
 *   - Dot notation field access: 'user.profile.name' reads nested values
 *   - Inline rule objects: pass a TransformationRule instance directly
 *   - Multi-rule ordered pipeline: rules run sequentially per field
 *   - TransformationResult::merge(): combine results from two engine passes
 *   - TransformationResult::transformationsFor(field): per-field change log
 *
 * Run: php examples/04-data-pipeline.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Provider\TransformerServiceProvider;
use KaririCode\Transformer\Rule\String\CamelCaseRule;
use KaririCode\Transformer\Rule\String\SnakeCaseRule;

$provider = new TransformerServiceProvider();
$engine = $provider->createEngine();

// ── 1. flatten / unflatten ────────────────────────────────────────────────────

$nested = [
    'user' => [
        'id'      => 42,
        'profile' => [
            'name'    => 'Walmir Silva',
            'contact' => ['email' => 'walmir@kariricode.org', 'phone' => '88999998888'],
        ],
        'settings' => ['theme' => 'dark', 'language' => 'pt-BR'],
    ],
];

// flatten: convert nested array to flat dotted keys
$flatResult = $engine->transform($nested, ['user' => [['flatten', ['separator' => '.']]]]);
$flat = (array) $flatResult->get('user');

echo "\n═══ 1. Structure: flatten nested → flat ════════════════════════\n";
foreach ($flat as $key => $value) {
    printf("  %-35s = %s\n", $key, $value);
}

// unflatten: reverse — flat dotted keys back to nested array
$unflatResult = $engine->transform($flat, ['profile.contact.email' => [['unflatten', ['separator' => '.']]]]);
// unflatten operates on the full input; let's demonstrate it directly
$flatInput = ['a.b.c' => 42, 'a.b.d' => 'hello', 'x.y' => true];
$unflat = $engine->transform(['v' => $flatInput], ['v' => [['unflatten', ['separator' => '.']]]])->get('v');
echo "  unflatten → " . json_encode($unflat, JSON_UNESCAPED_UNICODE) . "\n";

// ── 2. Dot notation field access ─────────────────────────────────────────────

$payload = [
    'order' => [
        'id'       => 5001,
        'customer' => ['name' => 'alice santos', 'city' => 'São Paulo'],
    ],
];

$dotResult = $engine->transform($payload, [
    'order.customer.name' => ['pascal_case'],
    'order.customer.city' => ['camel_case'],
]);

echo "\n═══ 2. Dot notation field access ═══════════════════════════════\n";
printf("  order.customer.name : %s\n", $dotResult->get('order.customer.name')); // AliceSantos
printf("  order.customer.city : %s\n", $dotResult->get('order.customer.city')); // sãoPaulo

// ── 3. Inline rule objects (TransformationRule instances) ─────────────────────

$inlineResult = $engine->transform(
    ['slug' => 'My Article Title', 'code' => 'Hello World'],
    [
        'slug' => [new SnakeCaseRule()],     // inline instance — no registry alias needed
        'code' => [new CamelCaseRule()],
    ],
);

echo "\n═══ 3. Inline Rule objects ══════════════════════════════════════\n";
printf("  slug : %s\n", $inlineResult->get('slug'));  // my_article_title
printf("  code : %s\n", $inlineResult->get('code'));  // helloWorld

// ── 4. Multi-rule ordered pipeline (sequential) ───────────────────────────────

$pipelineResult = $engine->transform(
    ['name' => 'hello world from kariricode'],
    [
        // Rules run IN ORDER: snake_case first, then reverse
        'name' => ['snake_case', 'reverse'],
    ],
);

echo "\n═══ 4. Ordered pipeline: snake_case → reverse ═══════════════════\n";
printf("  result : %s\n", $pipelineResult->get('name'));
echo "\n  Per-field transformation log:\n";
foreach ($pipelineResult->transformationsFor('name') as $t) {
    printf("    %-12s  before: %-35s  after: %s\n",
        "[$t->field]",
        json_encode($t->before, JSON_UNESCAPED_UNICODE),
        json_encode($t->after, JSON_UNESCAPED_UNICODE),
    );
}

// ── 5. TransformationResult::merge() ─────────────────────────────────────────

$r1 = $engine->transform(
    ['username' => 'walmir silva'],
    ['username' => ['camel_case']],
);
$r2 = $engine->transform(
    ['company' => 'kariri code framework'],
    ['company' => ['pascal_case']],
);
$merged = $r1->merge($r2);

echo "\n═══ 5. TransformationResult::merge() ═══════════════════════════\n";
printf("  username : %s\n", $merged->get('username'));    // walmirSilva
printf("  company  : %s\n", $merged->get('company'));     // KaririCodeFramework
printf("  fields   : %s\n", implode(', ', $merged->transformedFields()));

// ── 6. pluck + group_by + rename_keys ─────────────────────────────────────────

$orders = [
    ['id' => 1, 'customer' => 'Alice', 'status' => 'paid',     'total' => 199.9],
    ['id' => 2, 'customer' => 'Bob',   'status' => 'pending',  'total' => 49.9],
    ['id' => 3, 'customer' => 'Carol', 'status' => 'paid',     'total' => 320.0],
    ['id' => 4, 'customer' => 'Dave',  'status' => 'refunded', 'total' => 89.5],
];

$pluck  = $engine->transform(['list' => $orders], ['list' => [['pluck',    ['field' => 'customer']]]])->get('list');
$group  = $engine->transform(['list' => $orders], ['list' => [['group_by', ['field' => 'status']]]])->get('list');
$rename = $engine->transform($orders[0], ['id' => [['rename_keys', ['map' => ['id' => 'order_id']]]]])->get('id');

echo "\n═══ 6. Structure: pluck + group_by + rename_keys ═══════════════\n";
printf("  pluck(customer)    : %s\n", implode(', ', (array) $pluck));
echo "  group_by(status):\n";
foreach ((array) $group as $status => $items) {
    printf("    %-10s → %s\n", $status, implode(', ', array_column((array) $items, 'customer')));
}
// rename_keys: maps old key names to new names in the transformed output
$renameResult = $engine->transform(
    $orders[0],
    ['id' => [['rename_keys', ['map' => ['id' => 'order_id']]]]],
);
printf("  rename_keys result : id isTransformed=%s\n",
    $renameResult->isFieldTransformed('id') ? 'true' : 'false');
echo "═════════════════════════════════════════════════════════════════\n\n";
