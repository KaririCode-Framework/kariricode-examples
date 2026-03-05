<?php

declare(strict_types=1);

/**
 * Example 05 — Advanced Logic: Groups, Programmatic Engine, Custom Rules
 *
 * Real-world scenario: a multi-step checkout form where different validation
 * groups apply at each step, combined with custom callback rules and
 * programmatic engine usage without PHP Attributes.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  ValidationGroup attribute:
 *    groups(array<string>) — property belongs to the named validation group(s)
 *    Validate rules only run when validate($dto, group: 'name') matches.
 *
 *  not_null     : no params — value must not be null (arrays and '' are allowed)
 *  nullable     : no params — passes even if value is null (null-permissive guard)
 *  equal_to     : 'value'(mixed) — value must === the given value
 *  not_equal_to : 'value'(mixed) — value must !== the given value
 *  greater_than_or_equal : 'threshold'(numeric) — value >= threshold
 *  less_than_or_equal    : 'threshold'(numeric) — value <= threshold
 *  negative              : no params — value < 0
 *  negative_or_zero      : no params — value ≤ 0
 *  instance_of  : 'class'(string FQCN) — value must be instanceof the given class
 *  json         : no params — string must be valid JSON
 *  uuid         : no params — must be a UUID v4 (lowercase)
 *  ulid         : no params — must be a ULID (26-char base32)
 *  ip           : no params — valid IPv4 or IPv6 address
 *  hostname     : no params — valid hostname (RFC 1123)
 *
 *  Engine API (no Attributes):
 *    $engine->validate($data, $fieldRules, group: 'partial')
 *
 *  Custom / Programmatic rules (not registered via alias):
 *    Pass a ValidationRule instance directly in the fieldRules array.
 *    Use CallbackRule([closure]) | CompositeRule([...rules]) | etc.
 *    These cannot be registered as aliases because they need constructor args.
 *
 * Run: php examples/05-logic-advanced.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Attribute\ValidationGroup;
use KaririCode\Validator\Configuration\ValidatorConfiguration;
use KaririCode\Validator\Provider\ValidatorServiceProvider;
use KaririCode\Validator\Rule\Logic\AtLeastOneOfRule;
use KaririCode\Validator\Rule\Logic\CallbackRule;
use KaririCode\Validator\Rule\Logic\CompositeRule;
use KaririCode\Validator\Rule\Logic\ConditionalRule;
use KaririCode\Validator\Rule\Logic\RequiredRule;
use KaririCode\Validator\Rule\String\EmailRule;
use KaririCode\Validator\Rule\String\UrlRule;

// ── Setup ─────────────────────────────────────────────────────────────────────

$provider  = new ValidatorServiceProvider();
$validator = $provider->createAttributeValidator();

function printResult05(string $label, object $result): void
{
    $valid = $result->isValid();
    echo "\n─── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo '  valid  : ' . ($valid ? '✅ true' : '❌ false') . PHP_EOL;
    if (! $valid) {
        foreach ($result->getErrors() as $field => $errors) {
            foreach ($errors as $err) {
                printf("  ✗ %-20s %s\n", $field . ':', $err->getMessage());
            }
        }
    }
    echo PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  PART 1 — Validation Groups (multi-step checkout)                         │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Part 1 — Validation Groups ═══\n";

/**
 * Checkout DTO with two validation groups:
 *   'contact' → validated at step 1
 *   'payment' → validated at step 2
 */
final class CheckoutInput
{
    // ── Step 1: Contact ───────────────────────────────────────────────────
    #[ValidationGroup('contact', 'payment')]
    #[Validate('required', 'email')]
    public string $email = '';

    #[ValidationGroup('contact')]
    #[Validate('required', ['length', ['min' => 2, 'max' => 80]])]
    public string $firstName = '';

    #[ValidationGroup('contact')]
    #[Validate('required', ['length', ['min' => 2, 'max' => 80]])]
    public string $lastName = '';

    // ── Step 2: Payment ───────────────────────────────────────────────────
    #[ValidationGroup('payment')]
    #[Validate('required', 'luhn', ['length', ['min' => 13, 'max' => 19]])]
    public string $cardNumber = '';

    #[ValidationGroup('payment')]
    #[Validate('required', ['pattern', ['pattern' => '/^(0[1-9]|1[0-2])\/\d{2}$/']])]
    public string $cardExpiry = '';

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        string $cardNumber,
        string $cardExpiry,
    ) {
        $this->email      = $email;
        $this->firstName  = $firstName;
        $this->lastName   = $lastName;
        $this->cardNumber = $cardNumber;
        $this->cardExpiry = $cardExpiry;
    }
}

// Step 1: Only 'contact' group — payment fields not validated yet
$checkout = new CheckoutInput(
    email:      'walmir@kariricode.org',
    firstName:  'Walmir',
    lastName:   'Silva',
    cardNumber: '',      // ignored in 'contact' group
    cardExpiry: '',      // ignored in 'contact' group
);

$result = $validator->validate($checkout, group: 'contact');
printResult05('Group contact — step 1 (payment fields blank)', $result);
assert($result->isValid(), 'Group contact: should pass even with blank card fields');

// Step 2: Only 'payment' group — name fields re-validated via group or not
$checkout->cardNumber = '4539578763621486';
$checkout->cardExpiry = '08/27';

$result = $validator->validate($checkout, group: 'payment');
printResult05('Group payment — step 2 (card filled)', $result);
assert($result->isValid(), 'Group payment: valid card should pass');

// Bad card in payment group
$badCard           = clone $checkout;
$badCard->cardNumber = '1111222233334444'; // fails Luhn
$result = $validator->validate($badCard, group: 'payment');
printResult05('Group payment — bad card number', $result);
assert(! $result->isValid(), 'Group payment: bad Luhn should fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  PART 2 — Engine API + Advanced String & Comparison Rules                 │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Part 2 — Engine API: uuid / ulid / ip / hostname / json ═══\n";

$engine = $provider->createEngine();

$data = [
    'requestId'  => 'f47ac10b-58cc-4372-a567-0e02b2c3d479', // UUID v4
    'correlId'   => '01HQVMF5G3ZE7RZQB79VQP0T3M',            // ULID (26 chars)
    'clientIp'   => '203.0.113.42',                            // valid IPv4 (TEST-NET-3)
    'callbackUrl' => 'callback.kariricode.org',                // valid hostname
    'metadata'   => '{"env":"prod","version":"3.1.0"}',        // valid JSON
];

$result = $engine->validate($data, [
    'requestId'   => ['uuid'],
    'correlId'    => ['ulid'],
    'clientIp'    => ['ip'],
    'callbackUrl' => ['hostname'],
    'metadata'    => ['json'],
]);

echo "\n─── Network fields validation ─────────────────────────────────────────────\n";
foreach ($data as $k => $v) {
    $errors  = $result->getErrors();
    $fieldOk = ! isset($errors[$k]);
    printf("  %-14s %-42s %s\n", $k . ':', mb_substr((string) $v, 0, 40), $fieldOk ? '✅' : '❌');
}
assert($result->isValid(), 'Network fields: all should pass');

// Bad values
$badNet = [
    'requestId'   => 'not-a-uuid',
    'correlId'    => 'tooshort',
    'clientIp'    => '999.0.0.1',
    'callbackUrl' => '-invalid-..',
    'metadata'    => '{bad json',
];
$result2 = $engine->validate($badNet, [
    'requestId'   => ['uuid'],
    'correlId'    => ['ulid'],
    'clientIp'    => ['ip'],
    'callbackUrl' => ['hostname'],
    'metadata'    => ['json'],
]);
echo "\n─── Bad network fields ────────────────────────────────────────────────────\n";
foreach ($badNet as $k => $v) {
    $errors  = $result2->getErrors();
    $fieldOk = ! isset($errors[$k]);
    printf("  %-14s %-20s %s\n", $k . ':', $v, ! $fieldOk ? '✅ (correctly invalid)' : '❌');
}
assert(! $result2->isValid(), 'Network fields bad: should fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  PART 3 — Programmatic Rules (CallbackRule, CompositeRule, etc.)          │
// └──────────────────────────────────────────────────────────────────────────┘

echo "\n═══ Part 3 — Programmatic Rules (no Attributes) ═══\n";

$engine2 = $provider->createEngine();

// ── CallbackRule ──────────────────────────────────────────────────────────────
// A closure that acts as an inline validation rule

$isEvenPositive = new CallbackRule(
    fn (mixed $v): bool => is_int($v) && $v > 0 && $v % 2 === 0,
    errorCode: 'not_even_positive',
    message:   'The {field} must be an even positive integer.',
);

$result = $engine2->validate(['quantity' => 4], ['quantity' => [$isEvenPositive]]);
echo "\n─── CallbackRule — even positive integer ─────────────────────────────────\n";
echo '  quantity 4  : ' . ($result->isValid() ? '✅' : '❌') . PHP_EOL;
assert($result->isValid(), 'Callback: 4 is even positive');

$result = $engine2->validate(['quantity' => 3], ['quantity' => [$isEvenPositive]]);
echo '  quantity 3  : ' . (! $result->isValid() ? '✅ (correctly invalid)' : '❌') . PHP_EOL;
assert(! $result->isValid(), 'Callback: 3 is odd — should fail');

// ── CompositeRule ─────────────────────────────────────────────────────────────
// CompositeRule(errorCode, message, ValidationRule ...$rules)
// AND-composes multiple rules — all must pass for composite to pass.

$composite = new CompositeRule(
    'contact_invalid',
    'The {field} must be a non-empty valid email address.',
    new RequiredRule(),
    new EmailRule(),
);

$resultC = $engine2->validate(
    ['contact' => 'walmir@kariricode.org'],
    ['contact' => [$composite]],
);
echo "\n─── CompositeRule — required + email ─────────────────────────────────────\n";
echo '  valid email   : ' . ($resultC->isValid() ? '✅' : '❌') . PHP_EOL;
assert($resultC->isValid(), 'Composite: valid email should pass');

$resultC2 = $engine2->validate(
    ['contact' => ''],
    ['contact' => [$composite]],
);
echo '  empty contact : ' . (! $resultC2->isValid() ? '✅ (correctly invalid)' : '❌') . PHP_EOL;
assert(! $resultC2->isValid(), 'Composite: empty contact should fail');


// ── ConditionalRule ───────────────────────────────────────────────────────────
// Only runs the inner rule when the condition closure returns true.
// Condition receives: (mixed $value, ValidationContext $context)
// $context is the field-level context — it holds rule parameters, not siblings.
// Use it for value-based conditions, not cross-field logic.

// Scenario: token field is required only when it is a non-empty string prefix
//           (the engine calls: condition($value, $fieldContext))
$requiresNonEmpty = fn (mixed $v, object $ctx): bool => is_string($v);
$adminOnly        = new ConditionalRule($requiresNonEmpty, new RequiredRule());

$ctx = ['authToken' => 'secret-admin-token'];
$r   = $engine2->validate($ctx, ['authToken' => [$adminOnly]]);
echo "\n─── ConditionalRule — required when value is a string ────────────────────\n";
echo '  token string non-empty  : ' . ($r->isValid() ? '✅' : '❌') . PHP_EOL;
assert($r->isValid(), 'Conditional: string non-empty token passes');

$ctxEmpty = ['authToken' => ''];
$r2 = $engine2->validate($ctxEmpty, ['authToken' => [$adminOnly]]);
echo '  token string empty      : ' . (! $r2->isValid() ? '✅ (correctly invalid)' : '❌') . PHP_EOL;
assert(! $r2->isValid(), 'Conditional: string but empty token fails required');

$ctxNull = ['authToken' => null];
$r3 = $engine2->validate($ctxNull, ['authToken' => [$adminOnly]]);
echo '  token null (skip)       : ' . ($r3->isValid() ? '✅ (condition false — skipped)' : '❌') . PHP_EOL;
assert($r3->isValid(), 'Conditional: null is not a string — rule skipped → passes');

// ── AtLeastOneOfRule ──────────────────────────────────────────────────────────
// AtLeastOneOfRule(ValidationRule ...$rules) — variadic args, not an array
// Passes if ANY of the provided rules passes (OR logic)

$atLeastOne = new AtLeastOneOfRule(
    new EmailRule(),
    new UrlRule(),
);

echo "\n─── AtLeastOneOfRule — email OR url ──────────────────────────────────────\n";
foreach (['walmir@kariricode.org', 'https://kariricode.org', 'neither-email-nor-url'] as $val) {
    $r    = $engine2->validate(['contact' => $val], ['contact' => [$atLeastOne]]);
    $icon = $r->isValid() ? '✅' : '❌';
    printf("  %-35s %s\n", $val, $icon);
}

echo PHP_EOL;
echo "═══════════════════════════════════════════════════════\n";
echo "✅ Example 05 — Logic & Advanced validation complete!\n";
echo "═══════════════════════════════════════════════════════\n\n";
