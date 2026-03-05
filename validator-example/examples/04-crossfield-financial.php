<?php

declare(strict_types=1);

/**
 * Example 04 — Cross-Field Rules & Financial Instruments
 *
 * Real-world scenario: validating a bank transfer form that requires
 * cross-field consistency (password ≠ confirmation, dependent fields)
 * and financial instrument identifiers (Luhn, IBAN, BIC).
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  same_as          : 'other'(string field name) — value must equal the other field's value
 *  different_from   : 'other'(string field name) — value must differ from the other field
 *  required_with    : 'other'(string field name) — required only when other field is not empty
 *  required_without : 'other'(string field name) — required only when other field is empty
 *  luhn             : no params — Luhn algorithm (credit/debit card numbers)
 *  iban             : no params — ISO 13616 IBAN (international bank account number)
 *  bic              : no params — ISO 9362 BIC/SWIFT code
 *
 * Run: php examples/04-crossfield-financial.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Provider\ValidatorServiceProvider;

// ── DTO ───────────────────────────────────────────────────────────────────────

/**
 * Password change form: newPassword must differ from current, confirm must match new.
 */
final class PasswordChangeInput
{
    #[Validate('required', ['length', ['min' => 8]])]
    public string $currentPassword = '';

    /**
     * different_from → new password must differ from current
     * length → minimum 8 chars
     */
    #[Validate('required', ['length', ['min' => 8]], ['different_from', ['other' => 'currentPassword']])]
    public string $newPassword = '';

    /**
     * same_as → confirmation must be identical to newPassword
     */
    #[Validate('required', ['same_as', ['other' => 'newPassword']])]
    public string $confirmPassword = '';

    public function __construct(
        string $currentPassword,
        string $newPassword,
        string $confirmPassword,
    ) {
        $this->currentPassword  = $currentPassword;
        $this->newPassword      = $newPassword;
        $this->confirmPassword  = $confirmPassword;
    }
}

/**
 * Bank transfer form with conditional fields:
 * - promoCode is only required when discounted is true (required_with)
 * - fallbackBank is only required when primaryBank is empty (required_without)
 */
final class TransferInput
{
    #[Validate('required', 'positive', ['decimal', ['places' => 2]])]
    public float $amount = 0.0;

    /**
     * iban → full ISO 13616 checksum validation
     * (country code + check digits + BBAN)
     */
    #[Validate('required', 'iban')]
    public string $destinationIban = '';

    /**
     * bic → ISO 9362 BIC/SWIFT: 8 or 11 characters
     */
    #[Validate('required', 'bic')]
    public string $destinationBic = '';

    /**
     * required_with → promoCode is required if discountType is not empty
     */
    #[Validate(['required_with', ['other' => 'discountType']])]
    public string $promoCode = '';

    #[Validate()] // no validation — just drives the conditional
    public string $discountType = '';

    /**
     * required_without → fallbackBank required if primaryBank is empty
     */
    #[Validate(['required_without', ['other' => 'primaryBank']])]
    public string $fallbackBank = '';

    #[Validate()]
    public string $primaryBank = '';

    public function __construct(
        float $amount,
        string $destinationIban,
        string $destinationBic,
        string $promoCode,
        string $discountType,
        string $fallbackBank,
        string $primaryBank,
    ) {
        $this->amount          = $amount;
        $this->destinationIban = $destinationIban;
        $this->destinationBic  = $destinationBic;
        $this->promoCode       = $promoCode;
        $this->discountType    = $discountType;
        $this->fallbackBank    = $fallbackBank;
        $this->primaryBank     = $primaryBank;
    }
}

/**
 * Payment card DTO using Luhn validation.
 */
final class PaymentCardInput
{
    /**
     * luhn → Luhn algorithm check (industry standard for card numbers)
     * length → 13 .. 19 digits
     * pattern → digits only
     */
    #[Validate('required', 'luhn', ['length', ['min' => 13, 'max' => 19]], ['pattern', ['pattern' => '/^\d+$/']])]
    public string $cardNumber = '';

    #[Validate('required', ['pattern', ['pattern' => '/^(0[1-9]|1[0-2])\/\d{2}$/']])]
    public string $expiry = '';   // MM/YY

    #[Validate('required', ['length', ['min' => 3, 'max' => 4]], ['pattern', ['pattern' => '/^\d+$/']])]
    public string $cvv = '';

    public function __construct(string $cardNumber, string $expiry, string $cvv)
    {
        $this->cardNumber = $cardNumber;
        $this->expiry     = $expiry;
        $this->cvv        = $cvv;
    }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

$provider  = new ValidatorServiceProvider();
$validator = $provider->createAttributeValidator();

function printResult04(string $label, object $result): void
{
    $valid = $result->isValid();
    echo "\n─── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo '  valid  : ' . ($valid ? '✅ true' : '❌ false') . PHP_EOL;
    if (! $valid) {
        foreach ($result->getErrors() as $err) {
            printf("  ✗ %-20s %s\n", $err->field . ':', $err->message);
        }
    }
    echo PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Cross-Field: Password Change                                             │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Cross-Field Rules ═══\n";

// Valid change: new ≠ current, confirm = new
$pwd = new PasswordChangeInput('OldPass1!', 'NewPass99!', 'NewPass99!');
printResult04('Password A — valid change', $validator->validate($pwd));
assert($validator->validate($pwd)->isValid(), 'Password A: should pass');

// Invalid: new same as current + confirm mismatch
$badPwd = new PasswordChangeInput('SamePass1', 'SamePass1', 'DifferentPass!');
$result  = $validator->validate($badPwd);
printResult04('Password B — same password + mismatch', $result);
assert(! $result->isValid(), 'B: should fail (different_from + same_as)');
$errorFields = array_column($result->getErrors(), 'field');
assert(in_array('newPassword',     $errorFields, true), 'B: newPassword same as current');
assert(in_array('confirmPassword', $errorFields, true), 'B: confirmPassword mismatch');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Cross-Field: Bank Transfer                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Bank Transfer ═══\n";

// IBAN: GB82 WEST 1234 5698 7654 32 (Mokebank test IBAN)
// BIC : DEUTDEDB (Deutsche Bank example BIC)
$transfer = new TransferInput(
    amount:         1500.00,
    destinationIban: 'GB82WEST12345698765432',
    destinationBic:  'DEUTDEDB',
    promoCode:       'SUMMER10',
    discountType:    'percentage',  // promoCode becomes required
    fallbackBank:    '',
    primaryBank:     'Banco do Brasil',  // primaryBank set → fallbackBank optional
);
printResult04('Transfer A — valid with promo code', $validator->validate($transfer));
assert($validator->validate($transfer)->isValid(), 'Transfer A: should pass');

// Missing promoCode when discountType is set → required_with fails
$badTransfer = new TransferInput(
    amount:          500.00,
    destinationIban: 'GB82WEST12345698765432',
    destinationBic:  'DEUTDEDB',
    promoCode:       '',              // ← missing while discountType is set!
    discountType:    'fixed',
    fallbackBank:    '',
    primaryBank:     '',              // ← empty → fallbackBank required but also empty
);
$result = $validator->validate($badTransfer);
printResult04('Transfer B — missing promo + no bank', $result);
assert(! $result->isValid(), 'Transfer B: should fail (required_with + required_without)');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Financial: Luhn (Credit Card)                                            │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Payment Card (Luhn) ═══\n";

// 4 539 578 763 621 486 — Visa test number passing Luhn
$card = new PaymentCardInput('4539578763621486', '12/27', '123');
printResult04('Card A — valid Luhn', $validator->validate($card));
assert($validator->validate($card)->isValid(), 'Card A: valid Luhn should pass');

$badCard = new PaymentCardInput('1234567890123456', '13/99', '12');
$result  = $validator->validate($badCard);
printResult04('Card B — Luhn fail + invalid expiry + short CVV', $result);
assert(! $result->isValid(), 'Card B: should fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Financial: IBAN + BIC via Engine API                                     │
// └──────────────────────────────────────────────────────────────────────────┘

echo "─── Engine API: IBAN + BIC spot checks ───────────────────────────────────\n\n";

$engine = $provider->createEngine();

$cases = [
    ['iban' => 'GB82WEST12345698765432', 'bic' => 'DEUTDEDB',    'expect' => true],
    ['iban' => 'INVALIDIBAN000000000',   'bic' => 'TOOSHORT',    'expect' => false],
    ['iban' => 'DE89370400440532013000','bic' => 'COBADEFFXXX', 'expect' => true],
];

foreach ($cases as $c) {
    $r    = $engine->validate($c, ['iban' => ['iban'], 'bic' => ['bic']]);
    $icon = ($r->isValid() === $c['expect']) ? '✅' : '❌ UNEXPECTED';
    printf("  IBAN %-25s BIC %-14s %s\n", $c['iban'], $c['bic'], $icon);
}

echo PHP_EOL;
echo "═══════════════════════════════════════════════════════\n";
echo "✅ Example 04 — Cross-Field & Financial validation complete!\n";
echo "═══════════════════════════════════════════════════════\n\n";
