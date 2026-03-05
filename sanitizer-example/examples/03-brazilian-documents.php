<?php

declare(strict_types=1);

/**
 * Example 03 — Brazilian Documents & Address (CPF, CNPJ, CEP)
 *
 * Real-world scenario: formatting Brazilian government IDs and postal codes
 * coming from APIs, imports, or user input.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  digits_only : no params — strips everything except 0-9
 *  format_cpf  : no params — formats 11 raw digits as 999.999.999-99
 *                ⚠️ PIPELINE ORDER: always run digits_only BEFORE format_cpf
 *                   so formatted input (529.982.247-25) is normalized first
 *  format_cnpj : no params — formats 14 raw digits as 99.999.999/9999-99
 *                ⚠️ PIPELINE ORDER: always run digits_only BEFORE format_cnpj
 *  format_cep  : no params — formats 8 raw digits as 99999-999
 *                ⚠️ PIPELINE ORDER: always run digits_only BEFORE format_cep
 *  trim        : no params — strips surrounding whitespace
 *  capitalize  : no params — capitalizes each word
 *  upper_case  : no params — converts to FULL UPPERCASE
 *
 * Run: php examples/03-brazilian-documents.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

// ── DTOs ─────────────────────────────────────────────────────────────────────

final class IndividualDto
{
    /**
     * digits_only → strips mask first: "529.982.247-25" → "52998224725"
     * format_cpf  → re-formats: "52998224725" → "529.982.247-25"
     * Both directions (formatted input OR raw digits) work correctly.
     */
    #[Sanitize('digits_only', 'format_cpf')]
    public string $cpf = '';

    #[Sanitize('trim', 'capitalize')]
    public string $name = '';

    /**
     * digits_only → strips mask: "63.050-210" → "63050210"
     * format_cep  → re-formats: "63050210" → "63050-210"
     */
    #[Sanitize('digits_only', 'format_cep')]
    public string $postalCode = '';

    public function __construct(string $cpf, string $name, string $postalCode)
    {
        $this->cpf        = $cpf;
        $this->name       = $name;
        $this->postalCode = $postalCode;
    }
}

final class CompanyDto
{
    /**
     * digits_only → strips any separator: "11222333000181" stays "11222333000181"
     * format_cnpj → formats as: "11.222.333/0001-81"
     */
    #[Sanitize('digits_only', 'format_cnpj')]
    public string $cnpj = '';

    /** upper_case → "kariricode framework ltda" → "KARIRICODE FRAMEWORK LTDA" */
    #[Sanitize('trim', 'upper_case')]
    public string $tradeName = '';

    #[Sanitize('digits_only', 'format_cep')]
    public string $postalCode = '';

    public function __construct(string $cnpj, string $tradeName, string $postalCode)
    {
        $this->cnpj       = $cnpj;
        $this->tradeName  = $tradeName;
        $this->postalCode = $postalCode;
    }
}

// ── Sanitize ─────────────────────────────────────────────────────────────────

$sanitizer = (new SanitizerServiceProvider())->createAttributeSanitizer();

$person = new IndividualDto(
    cpf:        '529.982.247-25',   // formatted input — digits_only strips mask first
    name:       '  walmir silva  ',
    postalCode: '63.050-210',
);
$sanitizer->sanitize($person);

$company = new CompanyDto(
    cnpj:      '11222333000181',   // raw digits input — both work
    tradeName: '  kariricode framework ltda  ',
    postalCode: '63050210',
);
$sanitizer->sanitize($company);

// ── Print result ─────────────────────────────────────────────────────────────

echo "\n═══ Brazilian Documents ════════════════════════════════════════\n";
printf("  Person CPF         : %s\n", $person->cpf);        // 529.982.247-25
printf("  Person Name        : %s\n", $person->name);        // Walmir Silva
printf("  Person CEP         : %s\n", $person->postalCode);  // 63050-210
echo "\n";
printf("  Company CNPJ       : %s\n", $company->cnpj);       // 11.222.333/0001-81
printf("  Company Trade Name : %s\n", $company->tradeName);  // KARIRICODE FRAMEWORK LTDA
printf("  Company CEP        : %s\n", $company->postalCode); // 63050-210
echo "═════════════════════════════════════════════════════════════════\n";

assert($person->cpf        === '529.982.247-25',             "CPF should be formatted");
assert($person->name       === 'Walmir Silva',                "name should be capitalized");
assert($person->postalCode === '63050-210',                   "CEP should be formatted");
assert(str_contains($company->cnpj, '/'),                    "CNPJ should contain /");
assert($company->tradeName === 'KARIRICODE FRAMEWORK LTDA',  "tradeName should be uppercase");

echo "\n✅ All assertions passed!\n\n";
