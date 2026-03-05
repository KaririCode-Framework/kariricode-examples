<?php

declare(strict_types=1);

/**
 * Example 01 — User Registration Form Sanitization
 *
 * Real-world scenario: sanitizing data coming from an HTTP form submission
 * before persisting to the database.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  trim          : no params — strips leading/trailing whitespace
 *  capitalize    : no params — capitalizes first letter of each word
 *  lower_case    : no params — converts the entire string to lowercase
 *  upper_case    : no params — converts the entire string to uppercase
 *  email_filter  : no params — removes characters invalid in email addresses
 *  digits_only   : no params — strips everything except 0-9
 *  normalize_whitespace: no params — collapses multiple whitespace to single space
 *  truncate      : 'maxLength' (int)  — cuts string to at most N chars
 *
 * Run: php examples/01-user-registration.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

// ── DTO ──────────────────────────────────────────────────────────────────────

final class UserRegistrationDto
{
    /**
     * trim → removes surrounding whitespace
     * capitalize → "walmir" → "Walmir"
     */
    #[Sanitize('trim', 'capitalize')]
    public string $firstName = '';

    #[Sanitize('trim', 'capitalize')]
    public string $lastName = '';

    /**
     * trim → strips spaces
     * lower_case → "WALMIR@..." → "walmir@..."
     * email_filter → removes any char not valid in an email (keeps a-z 0-9 @ . _ + -)
     */
    #[Sanitize('trim', 'lower_case', 'email_filter')]
    public string $email = '';

    /**
     * digits_only → strips every non-digit character
     * "+55 (88) 9.9999-8888" → "5588999988888"
     */
    #[Sanitize('digits_only')]
    public string $phone = '';

    /**
     * trim → removes surrounding whitespace
     * normalize_whitespace → collapses \r\n and multiple spaces to single space
     * truncate → maxLength: 150 cuts the string to at most 150 characters
     */
    #[Sanitize('trim', 'normalize_whitespace', ['truncate', ['maxLength' => 150]])]
    public string $bio = '';

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $bio,
    ) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;
        $this->phone     = $phone;
        $this->bio       = $bio;
    }
}

// ── Dirty input simulating a form POST ───────────────────────────────────────

$dto = new UserRegistrationDto(
    firstName: '  walmir   ',
    lastName:  '   SILVA  ',
    email:     '  WALMIR@KARIRICODE.ORG  ',
    phone:     '+55 (88) 9.9999-8888',
    bio:       "PHP   developer.\r\nPHP lead at KaririCode.   Loves   clean   code.",
);

// ── Sanitize ─────────────────────────────────────────────────────────────────

$sanitizer = (new SanitizerServiceProvider())->createAttributeSanitizer();
$sanitizer->sanitize($dto);

// ── Print result ─────────────────────────────────────────────────────────────

echo "\n═══ User Registration Form Sanitization ════════════════════════\n";
printf("  firstName : '%s'\n", $dto->firstName);  // 'Walmir'
printf("  lastName  : '%s'\n", $dto->lastName);   // 'Silva'
printf("  email     : '%s'\n", $dto->email);      // 'walmir@kariricode.org'
printf("  phone     : '%s'\n", $dto->phone);      // '5588999988888' (digits only)
printf("  bio       : '%s'\n", $dto->bio);        // normalized, no double spaces or \r\n
echo "═════════════════════════════════════════════════════════════════\n";

assert($dto->firstName === 'Walmir',               "firstName should be 'Walmir'");
assert($dto->lastName  === 'Silva',                "lastName should be 'Silva'");
assert($dto->email     === 'walmir@kariricode.org', "email should be lowercased");
assert(ctype_digit($dto->phone),                   "phone should be digits only");
assert($dto->bio       === 'PHP developer. PHP lead at KaririCode. Loves clean code.', "bio normalized");

echo "\n✅ All assertions passed!\n\n";
