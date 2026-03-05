<?php

declare(strict_types=1);

/**
 * Example 01 — User Profile Normalization (AttributeTransformer)
 *
 * Real-world scenario: API receives raw user data from a form submission.
 * We declare transformation rules via #[Transform] attributes on DTO properties
 * then call AttributeTransformer::transform(). The library reads the attributes,
 * builds the rule pipeline per field, applies it, and writes results back.
 *
 * Demonstrates:
 *   - Attribute-based pipeline (#[Transform] on DTO)
 *   - String rules: camel_case, pascal_case, snake_case, kebab_case, mask
 *   - Brazilian rules: cpf_to_digits, phone_format
 *   - Date rules: age (default from: Y-m-d), date_to_iso8601 (from: d/m/Y)
 *   - TransformationResult: get(), wasTransformed(), transformedFields(),
 *     transformationCount(), getTransformations()
 *
 * Run: php examples/01-user-profile.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Provider\TransformerServiceProvider;

// ── DTO with #[Transform] attribute declarations ──────────────────────────────

final class UserProfileDto
{
    /** "walmir silva" → "walmirSilva" */
    #[Transform('camel_case')]
    public string $username = '';

    /** "walmir silva" → "WalmirSilva" */
    #[Transform('pascal_case')]
    public string $displayName = '';

    /** "My Amazing Blog Post" → "my_amazing_blog_post" */
    #[Transform('snake_case')]
    public string $slug = '';

    /** "My Amazing Blog Post" → "my-amazing-blog-post" */
    #[Transform('kebab_case')]
    public string $urlSlug = '';

    /**
     * "123.456.789-09" → "12345678909"
     * (strips all non-digit characters)
     */
    #[Transform('cpf_to_digits')]
    public string $cpf = '';

    /**
     * Phone format expects exactly 10 or 11 digits (no country code).
     * 10 → "(DD) XXXX-XXXX"   11 → "(DD) 9XXXX-XXXX"
     */
    #[Transform('phone_format')]
    public string $phone = '';

    /**
     * Age rule reads "Y-m-d" by default (param: from = 'Y-m-d').
     * Returns int (years elapsed).
     */
    #[Transform('age')]
    public mixed $age = null;

    /**
     * date_to_iso8601: the 'from' param (default 'd/m/Y') must match the input's format.
     * Here we pass "20/06/1990" → from = 'd/m/Y' (the default)
     */
    #[Transform('date_to_iso8601')]
    public string $createdAt = '';

    /**
     * mask: keeps $start chars, then fills with $maskChar, then keeps $end chars.
     * "secret123" with start=3, end=2 → "sec***23"
     */
    #[Transform(['mask', ['maskChar' => '*', 'start' => 3, 'end' => 2]])]
    public string $passwordHint = '';

    public function __construct(
        string $username,
        string $displayName,
        string $slug,
        string $urlSlug,
        string $cpf,
        string $phone,
        mixed $age,
        string $createdAt,
        string $passwordHint,
    ) {
        $this->username = $username;
        $this->displayName = $displayName;
        $this->slug = $slug;
        $this->urlSlug = $urlSlug;
        $this->cpf = $cpf;
        $this->phone = $phone;
        $this->age = $age;
        $this->createdAt = $createdAt;
        $this->passwordHint = $passwordHint;
    }
}

// ── Simulating dirty form input ───────────────────────────────────────────────

$dto = new UserProfileDto(
    username: 'walmir silva',
    displayName: 'walmir silva',
    slug: 'My Amazing Blog Post',
    urlSlug: 'My Amazing Blog Post',
    cpf: '123.456.789-09',
    phone: '88999998888',       // 11 digits — no country code!
    age: '1990-03-15',          // Y-m-d — matches AgeRule default 'from'
    createdAt: '15/01/2024',    // d/m/Y — matches DateToIso8601Rule default 'from'
    passwordHint: 'secret123',
);

// ── Transform ────────────────────────────────────────────────────────────────

$transformer = (new TransformerServiceProvider())->createAttributeTransformer();
$result = $transformer->transform($dto);

// ── Inspect the TransformationResult ─────────────────────────────────────────

echo "\n═══ User Profile — Transformed DTO ══════════════════════════════\n";
printf("  username     : %s\n", $dto->username);       // walmirSilva
printf("  displayName  : %s\n", $dto->displayName);    // WalmirSilva
printf("  slug         : %s\n", $dto->slug);            // my_amazing_blog_post
printf("  urlSlug      : %s\n", $dto->urlSlug);         // my-amazing-blog-post
printf("  cpf          : %s\n", $dto->cpf);             // 12345678909
printf("  phone        : %s\n", $dto->phone);           // (88) 99999-8888
printf("  age          : %d years\n", (int) $dto->age); // calculated age
printf("  createdAt    : %s\n", $dto->createdAt);       // 2024-01-15T00:00:00+00:00
printf("  passwordHint : %s\n", $dto->passwordHint);   // sec***23

echo "\n═══ TransformationResult inspection ════════════════════════════\n";
printf("  wasTransformed()        : %s\n", $result->wasTransformed() ? 'true' : 'false');
printf("  transformedFields()     : %s\n", implode(', ', $result->transformedFields()));
printf("  transformationCount()   : %d\n", $result->transformationCount());
printf("  result->get('username') : %s\n", $result->get('username'));

echo "\n═══ Transformation log (ordered pipeline) ═══════════════════════\n";
foreach ($result->getTransformations() as $t) {
    printf(
        "  [%-12s] %-20s  →  %s\n",
        $t->field,
        json_encode($t->before, JSON_UNESCAPED_UNICODE),
        json_encode($t->after, JSON_UNESCAPED_UNICODE),
    );
}
echo "═════════════════════════════════════════════════════════════════\n\n";
