<?php

declare(strict_types=1);

/**
 * Example 02 — Brazilian Documents: cpf_to_digits, cnpj_to_digits, cep_to_digits, phone_format
 *
 * Real-world scenario: Brazilian e-commerce normalizes customer documents before
 * sending to payment gateways (digits-only) and displaying in the UI (formatted).
 *
 * Demonstrates:
 *   - All 4 Brazilian rules with correct inputs
 *   - AttributeTransformer (DTO mode) vs Engine API (raw array mode)
 *   - string.reverse and string.repeat alongside Brazilian rules
 *   - phone_format: 10 digits → "(DD) XXXX-XXXX", 11 digits → "(DD) 9XXXX-XXXX"
 *
 * Run: php examples/02-brazilian-documents.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Provider\TransformerServiceProvider;

$provider = new TransformerServiceProvider();

// ── Mode 1: AttributeTransformer with a DTO ───────────────────────────────────

final class CustomerDto
{
    /** "123.456.789-09" → "12345678909" (strips non-digits) */
    #[Transform('cpf_to_digits')]
    public string $cpf = '';

    /** "11.222.333/0001-81" → "11222333000181" (strips non-digits) */
    #[Transform('cnpj_to_digits')]
    public string $cnpj = '';

    /** "60.175-110" → "60175110" (strips non-digits) */
    #[Transform('cep_to_digits')]
    public string $cep = '';

    /**
     * 10 digits → "(DD) XXXX-XXXX"
     * 11 digits → "(DD) 9XXXX-XXXX"
     * Input MUST have exactly 10 or 11 digits (no country code).
     */
    #[Transform('phone_format')]
    public string $mobile = '';        // 11 digits

    #[Transform('phone_format')]
    public string $landline = '';      // 10 digits

    /** Badge code repeated 5x using string.repeat */
    #[Transform(['repeat', ['times' => 5]])]
    public string $badgeCode = '';

    public function __construct(
        string $cpf, string $cnpj, string $cep,
        string $mobile, string $landline, string $badgeCode,
    ) {
        $this->cpf = $cpf;
        $this->cnpj = $cnpj;
        $this->cep = $cep;
        $this->mobile = $mobile;
        $this->landline = $landline;
        $this->badgeCode = $badgeCode;
    }
}

$dto = new CustomerDto(
    cpf: '123.456.789-09',
    cnpj: '11.222.333/0001-81',
    cep: '60.175-110',
    mobile: '88999998888',      // 11 digits (no country code)
    landline: '8530304040',     // 10 digits (no country code)
    badgeCode: 'KC',
);

$result = $provider->createAttributeTransformer()->transform($dto);

echo "\n═══ Mode 1: AttributeTransformer (DTO) ══════════════════════════\n";
printf("  cpf       : %-20s → %s\n", '123.456.789-09',     $dto->cpf);      // 12345678909
printf("  cnpj      : %-20s → %s\n", '11.222.333/0001-81', $dto->cnpj);     // 11222333000181
printf("  cep       : %-20s → %s\n", '60.175-110',         $dto->cep);      // 60175110
printf("  mobile    : %-20s → %s\n", '88999998888',        $dto->mobile);   // (88) 99999-8888
printf("  landline  : %-20s → %s\n", '8530304040',         $dto->landline); // (85) 3030-4040
printf("  badgeCode : %-20s → %s\n", 'KC',                 $dto->badgeCode);// KCKCKCKCKC
printf("  fields changed: %d\n", $result->transformationCount());

// ── Mode 2: Engine API (raw array, no DTO) ────────────────────────────────────

$engine = $provider->createEngine();

$rawData = [
    'customer_cpf'   => '987.654.321-00',
    'company_cnpj'   => '99.888.777/0001-99',
    'address_cep'    => '01310-100',
    'whatsapp'       => '11987654321',  // 11 digits
    'phone'          => '1130304040',   // 10 digits
];

$result2 = $engine->transform($rawData, [
    'customer_cpf'   => ['cpf_to_digits'],
    'company_cnpj'   => ['cnpj_to_digits'],
    'address_cep'    => ['cep_to_digits'],
    'whatsapp'       => ['phone_format'],
    'phone'          => ['phone_format'],
]);

echo "\n═══ Mode 2: Engine API (raw array) ═════════════════════════════\n";
printf("  customer_cpf   : %s\n", $result2->get('customer_cpf'));   // 98765432100
printf("  company_cnpj   : %s\n", $result2->get('company_cnpj'));   // 99888777000199
printf("  address_cep    : %s\n", $result2->get('address_cep'));    // 01310100
printf("  whatsapp       : %s\n", $result2->get('whatsapp'));       // (11) 98765-4321
printf("  phone          : %s\n", $result2->get('phone'));          // (11) 3030-4040
printf("  wasTransformed : %s\n", $result2->wasTransformed() ? 'true' : 'false');
printf("  isFieldTransformed('customer_cpf') : %s\n",
    $result2->isFieldTransformed('customer_cpf') ? 'true' : 'false');
echo "═════════════════════════════════════════════════════════════════\n\n";
