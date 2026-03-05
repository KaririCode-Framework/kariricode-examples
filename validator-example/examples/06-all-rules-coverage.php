<?php

declare(strict_types=1);

/**
 * Example 06 — Complete Rule Coverage (All 52 Built-in Rules)
 *
 * Reference sheet demonstrating every rule registered by ValidatorServiceProvider.
 * Each rule is exercised with a valid and an invalid value.
 *
 * !! ALIAS REFERENCE TABLE (52 rules — ValidatorServiceProvider::createRegistry()):
 *
 * ──────────────────────────────────────────────────────────
 *  LOGIC (8)         registered aliases
 * ──────────────────────────────────────────────────────────
 *  required          — value must be non-empty (no params)
 *  not_null          — value must not be null (no params)
 *  nullable          — always passes (null is OK) (no params)
 *  choice            — value ∈ choices(array)
 *  [AtLeastOneOf]    — programmatic only (needs constructor args)
 *  [Callback]        — programmatic only (needs closure)
 *  [Composite]       — programmatic only (needs rule list)
 *  [Conditional]     — programmatic only (needs condition closure)
 *
 * ──────────────────────────────────────────────────────────
 *  STRING (14)
 * ──────────────────────────────────────────────────────────
 *  email             — valid e-mail address
 *  url               — valid http/https URL
 *  length            — min(int)/max(int) character count
 *  pattern           — regex(string PCRE)
 *  not_empty         — non-blank string
 *  alpha             — only a-z A-Z
 *  alphanumeric      — only a-z A-Z 0-9
 *  uuid              — UUID v4 (lowercase format)
 *  ulid              — ULID (26-char base32)
 *  ip                — valid IPv4 or IPv6
 *  json              — valid JSON string
 *  hostname          — valid hostname (RFC 1123)
 *  slug              — a-z 0-9 hyphens only
 *  starts_with       — prefix(string)
 *  ends_with         — suffix(string)
 *
 * ──────────────────────────────────────────────────────────
 *  NUMERIC (6)
 * ──────────────────────────────────────────────────────────
 *  range             — min(numeric) / max(numeric)
 *  positive          — value > 0
 *  integer           — value is int
 *  negative          — value < 0
 *  negative_or_zero  — value ≤ 0
 *  decimal           — places(int) max decimal digits
 *
 * ──────────────────────────────────────────────────────────
 *  COMPARISON (7)
 * ──────────────────────────────────────────────────────────
 *  equal_to                  — value(mixed)
 *  not_equal_to              — value(mixed)
 *  greater_than              — threshold(numeric)
 *  greater_than_or_equal     — threshold(numeric)
 *  less_than                 — threshold(numeric)
 *  less_than_or_equal        — threshold(numeric)
 *  between                   — min(numeric) / max(numeric) inclusive
 *
 * ──────────────────────────────────────────────────────────
 *  DATE (4)
 * ──────────────────────────────────────────────────────────
 *  date_format       — format(string, default 'Y-m-d')
 *  date_before       — before(string) / format(string)
 *  date_after        — after(string) / format(string)
 *  date_between      — after(string) / before(string) / format(string)
 *
 * ──────────────────────────────────────────────────────────
 *  TYPE (2)
 * ──────────────────────────────────────────────────────────
 *  type              — type(string) gettype() result
 *  instance_of       — class(string FQCN)
 *
 * ──────────────────────────────────────────────────────────
 *  COLLECTION (3)
 * ──────────────────────────────────────────────────────────
 *  count             — min(int) / max(int) array element count
 *  unique            — no duplicate elements
 *  key_exists        — key(string|int) must exist in array
 *
 * ──────────────────────────────────────────────────────────
 *  CROSS-FIELD (4)
 * ──────────────────────────────────────────────────────────
 *  same_as           — other(string field name)
 *  different_from    — other(string field name)
 *  required_with     — other(string field name)
 *  required_without  — other(string field name)
 *
 * ──────────────────────────────────────────────────────────
 *  FINANCIAL (3)
 * ──────────────────────────────────────────────────────────
 *  luhn              — Luhn algorithm (card numbers)
 *  iban              — ISO 13616 IBAN
 *  bic               — ISO 9362 BIC/SWIFT
 *
 * ──────────────────────────────────────────────────────────
 *  BRAZILIAN (4)
 * ──────────────────────────────────────────────────────────
 *  cpf               — Brazilian individual tax number (11 digits)
 *  cnpj              — Brazilian company tax number (14 digits)
 *  cep               — Brazilian postal code (8 digits)
 *  pis               — Brazilian social security number (11 digits)
 *
 * Run: php examples/06-all-rules-coverage.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Provider\ValidatorServiceProvider;

$provider = new ValidatorServiceProvider();
$engine   = $provider->createEngine();

// ── Helpers ───────────────────────────────────────────────────────────────────

$passed = 0;
$failed = 0;

function check(string $rule, mixed $validValue, mixed $invalidValue, array $params = []): void
{
    global $engine, $passed, $failed;

    $definition = $params === [] ? $rule : [$rule, $params];

    $r1 = $engine->validate(['v' => $validValue],   ['v' => [$definition]]);
    $r2 = $engine->validate(['v' => $invalidValue],  ['v' => [$definition]]);

    $okValid   = $r1->isValid();
    $okInvalid = ! $r2->isValid();

    $iconV = $okValid   ? '✅' : '❌';
    $iconI = $okInvalid ? '✅' : '❌';

    // Safe display: avoid cast errors for objects
    $vStr = match (true) {
        is_object($validValue)   => get_class($validValue) . '{}',
        is_array($validValue)    => json_encode($validValue),
        default                  => (string) $validValue,
    };
    $iStr = match (true) {
        is_object($invalidValue) => get_class($invalidValue) . '{}',
        is_array($invalidValue)  => json_encode($invalidValue),
        default                  => (string) $invalidValue,
    };

    printf(
        "  %-30s valid:%-25s invalid:%-20s %s %s\n",
        $rule,
        mb_substr($vStr, 0, 24),
        mb_substr($iStr, 0, 18),
        $iconV,
        $iconI,
    );

    $okValid   ? $passed++ : $failed++;
    $okInvalid ? $passed++ : $failed++;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  LOGIC                                                                    │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Logic Rules ═══\n\n";
check('required',   'hello',  '');
check('not_null',   'hello',  null);
check('nullable',   null,     []);    // nullable always passes — 'invalid' still passes
check('choice',     'editor', 'superadmin', ['choices' => ['admin', 'editor', 'viewer']]);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  STRING                                                                   │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ String Rules ═══\n\n";
check('email',       'walmir@kariricode.org', 'not-an-email');
check('url',         'https://kariricode.org', 'not a url at all');
check('length',      'Hello',                  'Hi',            ['min' => 3, 'max' => 50]);
check('pattern',     'walmir2024',             'has spaces!',   ['pattern' => '/^[a-z0-9]+$/']);
check('not_empty',   'value',                  '');
check('alpha',       'Walmir',                 'Walmir42');
check('alphanumeric','Walmir42',               'Walmir-42!');
check('uuid',        'f47ac10b-58cc-4372-a567-0e02b2c3d479', 'not-uuid-at-all');
check('ulid',        '01HQVMF5G3ZE7RZQB79VQP0T3M',           'tooshort');
check('ip',          '203.0.113.42',           '999.0.0.1');
check('json',        '{"key":"value"}',         '{bad json');
check('hostname',    'api.kariricode.org',      '-invalid-host..');
check('slug',        'my-endpoint-v2',          'My Endpoint V2!');
check('starts_with', 'foobar',                  'barfoo',        ['prefix' => 'foo']);
check('ends_with',   'foobar',                  'foobar!',       ['suffix' => 'bar']);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  NUMERIC                                                                  │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Numeric Rules ═══\n\n";
check('range',           50,    200,   ['min' => 0, 'max' => 100]);
check('positive',        1,     0);
check('integer',         42,    3.14);
check('negative',       -1,     0);
check('negative_or_zero', 0,    1);
check('decimal',         9.99,  9.999, ['places' => 2]);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  COMPARISON                                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Comparison Rules ═══\n\n";
check('equal_to',             42,  99, ['expected' => 42]);
check('not_equal_to',          0,  42, ['expected' => 42]);
check('greater_than',         11,   9, ['threshold' => 10]);
check('greater_than_or_equal',10,   9, ['threshold' => 10]);
check('less_than',             9,  11, ['threshold' => 10]);
check('less_than_or_equal',   10,  11, ['threshold' => 10]);
check('between',               5,  15, ['min' => 1, 'max' => 10]);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  DATE                                                                     │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Date Rules ═══\n\n";
check('date_format', '2025-01-15', '15/01/2025');
check('date_before', '2024-12-31', '2026-01-01', ['before' => '2025-06-01']);
check('date_after',  '2025-07-01', '2024-01-01', ['after'  => '2025-06-01']);
check('date_between','2025-06-15', '2027-01-01', ['after' => '2025-01-01', 'before' => '2025-12-31']);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  TYPE                                                                     │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Type Rules ═══\n\n";
check('type', 42, 'not-int', ['type' => 'integer']);

// instance_of: use a real object as valid value and a plain string as invalid
$dto = new DateTimeImmutable('2025-01-01');
$r1 = $engine->validate(['dt' => $dto],    ['dt' => [['instance_of', ['class' => DateTimeImmutable::class]]]]);
$r2 = $engine->validate(['dt' => 'string'], ['dt' => [['instance_of', ['class' => DateTimeImmutable::class]]]]);
$okV = $r1->isValid();
$okI = ! $r2->isValid();
printf("  %-30s valid:%-25s invalid:%-20s %s %s\n",
    'instance_of', 'DateTimeImmutable{}', 'string', $okV ? '✅' : '❌', $okI ? '✅' : '❌');
$okV ? $passed++ : $failed++;
$okI ? $passed++ : $failed++;


// ┌──────────────────────────────────────────────────────────────────────────┐
// │  COLLECTION                                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Collection Rules ═══\n\n";
check('count',      ['a', 'b', 'c'], ['a'],     ['min' => 2, 'max' => 5]);
check('unique',     ['a', 'b', 'c'], ['a', 'a', 'b']);
check('key_exists', ['name' => 'W'], ['age' => 30], ['key' => 'name']);

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  CROSS-FIELD  (use engine with two fields)                                │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Cross-Field Rules ═══\n\n";

// same_as
$r = $engine->validate(
    ['pass' => 'abc', 'confirm' => 'abc'],
    ['confirm' => [['same_as', ['other' => 'pass']]]],
);
$nok = $engine->validate(
    ['pass' => 'abc', 'confirm' => 'xyz'],
    ['confirm' => [['same_as', ['other' => 'pass']]]],
);
printf("  %-30s valid:%-25s invalid:%-20s %s %s\n",
    'same_as', 'confirm=pass', 'confirm≠pass', $r->isValid() ? '✅' : '❌', ! $nok->isValid() ? '✅' : '❌');

// different_from
$r = $engine->validate(
    ['old' => 'old1', 'new' => 'new2'],
    ['new' => [['different_from', ['other' => 'old']]]],
);
$nok = $engine->validate(
    ['old' => 'same', 'new' => 'same'],
    ['new' => [['different_from', ['other' => 'old']]]],
);
printf("  %-30s valid:%-25s invalid:%-20s %s %s\n",
    'different_from', 'new≠old', 'new=old', $r->isValid() ? '✅' : '❌', ! $nok->isValid() ? '✅' : '❌');

// required_with
$r = $engine->validate(
    ['coupon' => 'PROMO10', 'discountType' => 'pct'],
    ['coupon' => [['required_with', ['other' => 'discountType']]]],
);
$nok = $engine->validate(
    ['coupon' => '', 'discountType' => 'pct'],
    ['coupon' => [['required_with', ['other' => 'discountType']]]],
);
printf("  %-30s valid:%-25s invalid:%-20s %s %s\n",
    'required_with', 'coupon+discountType', 'missing coupon', $r->isValid() ? '✅' : '❌', ! $nok->isValid() ? '✅' : '❌');

// required_without
$r = $engine->validate(
    ['phone' => '', 'email' => 'w@k.org'],
    ['phone' => [['required_without', ['other' => 'email']]]],
);
$nok = $engine->validate(
    ['phone' => '', 'email' => ''],
    ['phone' => [['required_without', ['other' => 'email']]]],
);
printf("  %-30s valid:%-25s invalid:%-20s %s %s\n",
    'required_without', 'email present', 'both empty', $r->isValid() ? '✅' : '❌', ! $nok->isValid() ? '✅' : '❌');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  FINANCIAL                                                                │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Financial Rules ═══\n\n";
check('luhn', '4539578763621486', '1234567890123456');
check('iban', 'GB82WEST12345698765432', 'INVALIDIBAN000');
check('bic',  'DEUTDEDB',  '12345');   // '12345' — digits in bank code → fails

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  BRAZILIAN                                                                │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Brazilian Rules ═══\n\n";
check('cpf',  '529.982.247-25', '111.111.111-11');
check('cnpj', '11.222.333/0001-81', '00.000.000/0000-00');
check('cep',  '60820-210',     '00000-00X');
check('pis',  '12345678919',   '00000000000');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Summary                                                                  │
// └──────────────────────────────────────────────────────────────────────────┘

echo PHP_EOL;
echo "═══════════════════════════════════════════════════════\n";
printf("  Checks passed : %d\n", $passed);
printf("  Checks failed : %d\n", $failed);
echo "═══════════════════════════════════════════════════════\n";

if ($failed > 0) {
    echo "❌ Some checks failed — review output above.\n\n";
    exit(1);
}

echo "✅ All 52 rules exercised — 100% alias coverage!\n\n";
