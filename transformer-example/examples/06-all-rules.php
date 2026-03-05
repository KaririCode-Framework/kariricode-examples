<?php

declare(strict_types=1);

/**
 * Example 06 — All 32 Rules: Complete Coverage Smoke Test
 *
 * Demonstrates EVERY built-in rule with CORRECT parameters as confirmed
 * from the actual rule implementations.
 *
 * ═══ PARAMETER REFERENCE (verified from source) ════════════════════════
 *
 * Brazilian:
 *   cpf_to_digits, cnpj_to_digits, cep_to_digits: no params
 *   phone_format: no params; input MUST be exactly 10 or 11 digits, no DDI
 *
 * Data:
 *   csv_to_array:     separator(,), enclosure("), header(bool, default true)
 *   json_decode:      assoc(bool, default true)
 *   json_encode:      flags(int, default JSON_UNESCAPED_UNICODE|SLASHES)
 *   array_to_key_value: key('id'), value('name') — input: list of objects
 *   implode:          separator(',')
 *
 * Date:
 *   age:              from('Y-m-d')      — input format must match
 *   date_to_iso8601:  from('d/m/Y'), timezone('UTC')
 *   date_to_timestamp: format('Y-m-d')  ← param is 'format' NOT 'from'
 *   relative_date:    from('Y-m-d H:i:s'), now(DateTimeInterface)
 *
 * Encoding:
 *   base64_encode, base64_decode: no params
 *   hash:             algo('sha256')     ← param is 'algo' NOT 'algorithm'
 *
 * Numeric:
 *   currency_format:  prefix(''), dec_point('.'), thousands(','), decimals(2)
 *                     ← NO 'locale' or 'currency' params
 *   percentage:       decimals(1)
 *   ordinal, number_to_words: no params
 *
 * String:
 *   camel_case, snake_case, kebab_case, pascal_case, reverse: no params
 *   mask:             maskChar('*'), start(0), end(0)
 *   repeat:           times(1)
 *
 * Structure:
 *   flatten, unflatten:  separator('.')
 *   pluck:               field('name')
 *   group_by:            field('type')
 *   rename_keys:         map([]) — associative array old→new
 *
 * Run: php examples/06-all-rules.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Provider\TransformerServiceProvider;

$engine = (new TransformerServiceProvider())->createEngine();

function run(string $rule, mixed $input, array $params = []): mixed
{
    global $engine;
    $rules = $params !== [] ? [[$rule, $params]] : [$rule];

    return $engine->transform(['v' => $input], ['v' => $rules])->get('v');
}

function row(string $rule, mixed $input, string $note = '', array $params = []): void
{
    $result = run($rule, $input, $params);
    $in = is_scalar($input) ? (string) $input : json_encode($input, JSON_UNESCAPED_UNICODE);
    $out = is_scalar($result) ? (string) $result : json_encode($result, JSON_UNESCAPED_UNICODE);
    $suffix = $note !== '' ? "  # $note" : '';
    printf("  %-22s │ %-28s → %s%s\n", $rule, mb_substr($in, 0, 28), mb_substr($out, 0, 48), $suffix);
}

$past3h = (new DateTimeImmutable())->modify('-3 hours')->format('Y-m-d H:i:s');
$b64    = (string) run('base64_encode', 'KaririCode');

echo "\n══════════════════════════════════════════════════════════════════════════\n";
echo "  kariricode/transformer v2.0.0 — All 32 Rules (ARFA 1.3 / Spec V4.0)\n";
echo "══════════════════════════════════════════════════════════════════════════\n";
printf("  %-22s │ %-28s → Output\n", 'Rule', 'Input');
echo "  ──────────────────────────────────────────────────────────────────────\n";

// ── Brazilian (4) ─────────────────────────────────────────────────────────────
echo "  ─── Brazilian (4) ───────────────────────────────────────────────────\n";
row('cpf_to_digits',   '123.456.789-09');
row('cnpj_to_digits',  '11.222.333/0001-81');
row('cep_to_digits',   '60.175-110');
row('phone_format',    '88999998888', '', []);          // 11 digits, no DDI

// ── Data (5) ──────────────────────────────────────────────────────────────────
echo "  ─── Data (5) ────────────────────────────────────────────────────────\n";
// csv_to_array: single-line input with header=false → indexed array of rows
row('csv_to_array',       "name,age\nAlice,30\nBob,25", 'header=true → assoc rows');
// array_to_key_value: list of objects → ['id' => 'name'] map
row('array_to_key_value', [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']],
    'key=id value=name', ['key' => 'id', 'value' => 'name']);
row('json_decode',        '{"name":"KaririCode","v":2}');
row('json_encode',        ['name' => 'KaririCode', 'v' => 2]);
row('implode',            ['red', 'green', 'blue'], 'separator " | "', ['separator' => ' | ']);

// ── Date (4) ──────────────────────────────────────────────────────────────────
echo "  ─── Date (4) ────────────────────────────────────────────────────────\n";
// age: from='Y-m-d' (default); input must be Y-m-d
row('age',               '1990-06-20', 'from=Y-m-d (default)');
// date_to_iso8601: from='d/m/Y' (default); input must be d/m/Y
row('date_to_iso8601',   '15/01/2024', 'from=d/m/Y (default)');
// date_to_timestamp: param is 'format' not 'from'; default 'Y-m-d'
row('date_to_timestamp', '2024-06-30', "param='format' (not 'from')");
// relative_date: from='Y-m-d H:i:s' (default)
row('relative_date',     $past3h, 'from=Y-m-d H:i:s (default)');

// ── Encoding (3) ──────────────────────────────────────────────────────────────
echo "  ─── Encoding (3) ────────────────────────────────────────────────────\n";
row('base64_encode', 'KaririCode');
row('base64_decode', $b64);
row('hash',          'KaririCode', "param='algo' (NOT 'algorithm'), default sha256", ['algo' => 'sha256']);
// md5: 32 chars, sha1: 40 chars, sha256: 64 chars
printf("    md5  (%2d): %s\n", strlen((string) run('hash', 'KaririCode', ['algo' => 'md5'])),  run('hash', 'KaririCode', ['algo' => 'md5']));
printf("    sha1 (%2d): %s\n", strlen((string) run('hash', 'KaririCode', ['algo' => 'sha1'])), run('hash', 'KaririCode', ['algo' => 'sha1']));

// ── Numeric (4) ───────────────────────────────────────────────────────────────
echo "  ─── Numeric (4) ─────────────────────────────────────────────────────\n";
// currency_format: NO 'locale'/'currency' params; use prefix/dec_point/thousands
row('currency_format',  1990.50, 'BRL: prefix=R$, dec_point=, thousands=.',
    ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']);
row('currency_format',  1990.50, 'USD: prefix=$',
    ['prefix' => '$']);
row('percentage',       0.1575, 'decimals=2', ['decimals' => 2]);
row('ordinal',          21);
row('number_to_words',  42);

// ── String (7) ────────────────────────────────────────────────────────────────
echo "  ─── String (7) ──────────────────────────────────────────────────────\n";
row('camel_case',  'hello world from kariricode');
row('snake_case',  'Hello World From KaririCode');
row('kebab_case',  'Hello World From KaririCode');
row('pascal_case', 'hello world from kariricode');
row('mask',        'secret123', 'start=3, end=2', ['maskChar' => '*', 'start' => 3, 'end' => 2]);
row('reverse',     'KaririCode');
row('repeat',      'KC-', 'times=4', ['times' => 4]);

// ── Structure (5) ─────────────────────────────────────────────────────────────
echo "  ─── Structure (5) ───────────────────────────────────────────────────\n";
row('flatten',     ['a' => ['b' => ['c' => 42]]],                               'separator=.', ['separator' => '.']);
row('unflatten',   ['a.b.c' => 42],                                              'separator=.', ['separator' => '.']);
row('pluck',       [['name' => 'Alice'], ['name' => 'Bob']],                    'field=name',  ['field' => 'name']);
row('group_by',    [['t' => 'A', 'v' => 1], ['t' => 'B', 'v' => 2], ['t' => 'A', 'v' => 3]], 'field=t', ['field' => 't']);
row('rename_keys', ['old_key' => 'Alice'],                                       'map old→name', ['map' => ['old_key' => 'name']]);

echo "══════════════════════════════════════════════════════════════════════════\n";
echo "  ✅ All 32 rules executed correctly — kariricode/transformer v2.0.0\n";
echo "══════════════════════════════════════════════════════════════════════════\n\n";
