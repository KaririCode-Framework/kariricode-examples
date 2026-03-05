<?php

declare(strict_types=1);

/**
 * Example 01 — User Registration Form Validation
 *
 * Real-world scenario: validating data coming from an HTTP form submission
 * (sign-up page) before creating a user account in the database.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  required      : no params — value must be non-empty
 *  email         : no params — must be a valid e-mail address
 *  length        : 'min'(int), 'max'(int) — string length bounds
 *  pattern       : 'regex'(string PCRE) — must match the pattern
 *  not_empty     : no params — value must not be an empty string
 *  alpha         : no params — only a-z A-Z allowed
 *  alphanumeric  : no params — only a-z A-Z 0-9
 *  url           : no params — must be a valid URL
 *  choice        : 'choices'(array) — value must be one of the listed options
 *
 * Run: php examples/01-user-registration.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Provider\ValidatorServiceProvider;

// ── DTO ──────────────────────────────────────────────────────────────────────

/**
 * User registration DTO — each property declares its validation rules
 * via #[Validate] attributes using the registered alias names.
 */
final class UserRegistrationInput
{
    /**
     * required → must not be empty/null
     * not_empty → must not be blank string
     * length  → 2 ≤ name ≤ 80 characters
     * alpha   → letters only (no numbers, no symbols)
     */
    #[Validate('required', 'not_empty', ['length', ['min' => 2, 'max' => 80]], 'alpha')]
    public string $firstName = '';

    #[Validate('required', 'not_empty', ['length', ['min' => 2, 'max' => 80]], 'alpha')]
    public string $lastName = '';

    /**
     * required → must be present
     * email → must be a valid address: local@domain.tld
     * length → max 150 chars (common DB column limit)
     */
    #[Validate('required', 'email', ['length', ['max' => 150]])]
    public string $email = '';

    /**
     * required → must be present
     * length → at least 8 chars for security
     * pattern → must contain ≥1 digit
     */
    #[Validate('required', ['length', ['min' => 8, 'max' => 72]], ['pattern', ['pattern' => '/\d/']])]
    public string $password = '';

    /**
     * required → must be present
     * url → must start with http:// or https://
     */
    #[Validate('url')]
    public string $website = '';

    /**
     * choice → must be one of the declared roles
     */
    #[Validate(['choice', ['choices' => ['admin', 'editor', 'viewer']]])]
    public string $role = '';

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $website,
        string $role,
    ) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;
        $this->password  = $password;
        $this->website   = $website;
        $this->role      = $role;
    }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

$provider  = new ValidatorServiceProvider();
$validator = $provider->createAttributeValidator();

// ── Helper ────────────────────────────────────────────────────────────────────

function printResult(string $label, object $dto, object $result): void
{
    $valid = $result->isValid();
    echo "\n─── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo '  valid  : ' . ($valid ? '✅ true' : '❌ false') . PHP_EOL;
    if (! $valid) {
        foreach ($result->getErrors() as $err) {
            printf("  ✗ %-14s %s\n", $err->field . ':', $err->message);
        }
    }
    echo PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO A — all fields valid                                           │
// └──────────────────────────────────────────────────────────────────────────┘

$valid = new UserRegistrationInput(
    firstName: 'Walmir',
    lastName:  'Silva',
    email:     'walmir@kariricode.org',
    password:  'Secure123',
    website:   'https://kariricode.org',
    role:      'editor',
);

$result = $validator->validate($valid);
printResult('Scenario A — valid registration', $valid, $result);
assert($result->isValid(), 'A: all valid input should pass');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO B — multiple simultaneous errors                               │
// └──────────────────────────────────────────────────────────────────────────┘

$invalid = new UserRegistrationInput(
    firstName: 'W',           // too short (< 2 chars alpha)
    lastName:  '123Silva',    // contains digits → alpha fails
    email:     'not-an-email',
    password:  'short',       // < 8 chars
    website:   'ftp://wrong', // not http(s)
    role:      'superuser',   // not in choices
);

$result = $validator->validate($invalid);
printResult('Scenario B — multiple errors', $invalid, $result);
assert(! $result->isValid(), 'B: invalid input must fail');
// Check that errors exist for the specific expected fields
$errorFields = array_column($result->getErrors(), 'field');
assert(in_array('firstName', $errorFields, true), 'B: firstName error expected');
assert(in_array('email',     $errorFields, true), 'B: email error expected');
assert(in_array('role',      $errorFields, true), 'B: role error expected');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  SCENARIO C — boundary: minimum-length strings                           │
// └──────────────────────────────────────────────────────────────────────────┘

$boundary = new UserRegistrationInput(
    firstName: 'Jo',          // exactly 2 — minimum allowed
    lastName:  'Gu',          // exactly 2 — minimum allowed
    email:     'a@b.io',
    password:  'Pass1234',    // exactly 8 — minimum allowed
    website:   'https://a.io',
    role:      'viewer',
);

$result = $validator->validate($boundary);
printResult('Scenario C — boundary valid', $boundary, $result);
assert($result->isValid(), 'C: boundary-minimum values should pass');

echo "═══════════════════════════════════════════════════════\n";
echo "✅ Example 01 — User Registration validation complete!\n";
echo "═══════════════════════════════════════════════════════\n\n";
