<?php

declare(strict_types=1);

/**
 * Example 03 — Brazilian Documents Validation
 *
 * Real-world scenario: validating the identity and tax documents required
 * by a Brazilian fintech onboarding flow.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  cpf    : no params — validates a Brazilian CPF (11-digit individual tax number)
 *           Luhn-like algorithm with two check digits.
 *  cnpj   : no params — validates a Brazilian CNPJ (14-digit company tax number)
 *           Módulo 11 with two check digits (supports XX.XXX.XXX/XXXX-XX format).
 *  cep    : no params — validates a Brazilian postal code (CEP).
 *           Accepts 8 digits optionally formatted as XXXXX-XXX.
 *  pis    : no params — validates a Brazilian PIS/PASEP/NIT social contribution number (11 digits).
 *  pattern: 'regex'(string PCRE) — regex the value must match
 *  length : 'min'(int), 'max'(int) — character count bounds
 *
 * Run: php examples/03-brazilian-documents.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Validator\Attribute\Validate;
use KaririCode\Validator\Provider\ValidatorServiceProvider;

// ── DTO ───────────────────────────────────────────────────────────────────────

/**
 * Onboarding DTO for Brazilian individual (Pessoa Física).
 */
final class BrazilianPersonInput
{
    /**
     * cpf → validates the CPF check digits (accepts "000.000.000-00" or "00000000000")
     */
    #[Validate('required', 'cpf')]
    public string $cpf = '';

    /**
     * pis → validates the PIS/PASEP/NIT (social contribution number)
     */
    #[Validate('required', 'pis')]
    public string $pis = '';

    /**
     * cep → validates the postal code (8 digits, optional hyphen)
     */
    #[Validate('required', 'cep')]
    public string $cep = '';

    /** length + pattern → simple phone check (10–11 digits, no formatting) */
    #[Validate('required', ['length', ['min' => 10, 'max' => 11]], ['pattern', ['pattern' => '/^\d+$/']])]
    public string $phone = '';

    public function __construct(
        string $cpf,
        string $pis,
        string $cep,
        string $phone,
    ) {
        $this->cpf   = $cpf;
        $this->pis   = $pis;
        $this->cep   = $cep;
        $this->phone = $phone;
    }
}

/**
 * Onboarding DTO for Brazilian company (Pessoa Jurídica).
 */
final class BrazilianCompanyInput
{
    /**
     * cnpj → validates the CNPJ check digits
     *         Accepts "XX.XXX.XXX/XXXX-XX" or "XXXXXXXXXXXXXX" (14 raw digits)
     */
    #[Validate('required', 'cnpj')]
    public string $cnpj = '';

    #[Validate('required', 'not_empty', ['length', ['min' => 5, 'max' => 200]])]
    public string $companyName = '';

    #[Validate('required', 'cep')]
    public string $cep = '';

    public function __construct(string $cnpj, string $companyName, string $cep)
    {
        $this->cnpj        = $cnpj;
        $this->companyName = $companyName;
        $this->cep         = $cep;
    }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

$provider  = new ValidatorServiceProvider();
$validator = $provider->createAttributeValidator();

function printResult03(string $label, object $result): void
{
    $valid = $result->isValid();
    echo "\n─── {$label} " . str_repeat('─', max(1, 55 - strlen($label))) . "\n";
    echo '  valid  : ' . ($valid ? '✅ true' : '❌ false') . PHP_EOL;
    if (! $valid) {
        foreach ($result->getErrors() as $err) {
            printf("  ✗ %-12s %s\n", $err->field . ':', $err->message);
        }
    }
    echo PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  CPF / PIS / CEP — Pessoa Física                                          │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Brazilian Person (Pessoa Física) ═══\n";

// Valid CPF: 529.982.247-25 (a well-known test CPF)
$person = new BrazilianPersonInput(
    cpf:   '529.982.247-25',
    pis:   '12345678919',   // valid PIS with correct check digit
    cep:   '60820-210',     // Fortaleza, CE
    phone: '88999998888',
);
$result = $validator->validate($person);
printResult03('Person A — valid documents', $result);
assert($result->isValid(), 'Person A: valid Brazilian person should pass');

// Invalid CPF (wrong check digits)
$badPerson = new BrazilianPersonInput(
    cpf:   '111.111.111-11', // sequential — invalid by definition
    pis:   '00000000000',    // all zeros — invalid
    cep:   '00000-00X',     // non-numeric — invalid
    phone: '123',            // too short (< 10 digits)
);
$result = $validator->validate($badPerson);
printResult03('Person B — invalid documents', $result);
assert(! $result->isValid(), 'Person B: invalid docs should fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  CNPJ — Pessoa Jurídica                                                   │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Brazilian Company (Pessoa Jurídica) ═══\n";

// Valid CNPJ: 11.222.333/0001-81 (test CNPJ with correct check digits)
$company = new BrazilianCompanyInput(
    cnpj:        '11.222.333/0001-81',
    companyName: 'KaririCode Tecnologia Ltda',
    cep:         '01310100',  // Av. Paulista, SP
);
$result = $validator->validate($company);
printResult03('Company A — valid CNPJ', $result);
assert($result->isValid(), 'Company A: should pass');

$badCompany = new BrazilianCompanyInput(
    cnpj:        '00.000.000/0000-00', // all zeros — invalid
    companyName: 'A',                   // too short
    cep:         '99999-999',          // may or may not exist — just format check
);
$result = $validator->validate($badCompany);
printResult03('Company B — invalid CNPJ', $result);
assert(! $result->isValid(), 'Company B: invalid CNPJ should fail');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  Engine API — batch validate a list of CPFs                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "─── Engine API: batch CPF validation ─────────────────────────────────────\n\n";

$engine = $provider->createEngine();
$cpfs   = [
    '529.982.247-25', // ✅ valid
    '111.111.111-11', // ❌ sequential
    '000.000.000-00', // ❌ all zeros
];

foreach ($cpfs as $cpf) {
    $r = $engine->validate(['cpf' => $cpf], ['cpf' => ['cpf']]);
    $icon = $r->isValid() ? '✅' : '❌';
    printf("  cpf %-20s %s\n", $cpf, $icon);
}

echo PHP_EOL;
echo "═══════════════════════════════════════════════════════\n";
echo "✅ Example 03 — Brazilian Documents validation complete!\n";
echo "═══════════════════════════════════════════════════════\n\n";
