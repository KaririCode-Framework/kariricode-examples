<?php

declare(strict_types=1);

/**
 * Example 05 — Content Management: All Date & Encoding Rules
 *
 * !! IMPORTANT PARAMETER NOTES (confirmed from implementations):
 *
 *  date_to_iso8601 params:
 *    - 'from'     string: format of the INPUT string (default 'd/m/Y')
 *    - 'timezone' string: timezone for output (default 'UTC')
 *    Input must match 'from' format exactly, otherwise rule returns value unchanged.
 *
 *  age params:
 *    - 'from'  string: format of INPUT date string (default 'Y-m-d')
 *
 *  date_to_timestamp params:
 *    - 'format'  string: format of INPUT date string (default 'Y-m-d')  ← NOTE: 'format' not 'from'
 *
 *  relative_date params:
 *    - 'from'  string: format of INPUT datetime string (default 'Y-m-d H:i:s')
 *    - 'now'   DateTimeInterface: override current time (useful for testing)
 *
 *  hash params:
 *    - 'algo'  string: algorithm passed to PHP's hash() function (default 'sha256')
 *               ← NOTE: 'algo' not 'algorithm'
 *
 * Run: php examples/05-content-management.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Provider\TransformerServiceProvider;

$provider = new TransformerServiceProvider();
$engine = $provider->createEngine();

// ── DTO: article metadata (AttributeTransformer) ─────────────────────────────

final class ArticleDto
{
    /**
     * relative_date: default 'from' is 'Y-m-d H:i:s'
     * Input MUST be in Y-m-d H:i:s format for the default to work.
     */
    #[Transform('relative_date')]
    public string $publishedAt = '';

    /**
     * date_to_iso8601: default 'from' is 'd/m/Y'
     * Input "20/06/1990" matches → converts to ISO 8601 atomic
     */
    #[Transform('date_to_iso8601')]
    public string $authorBorn = '';

    /**
     * age: default 'from' is 'Y-m-d'
     * Input "1985-08-12" matches → int years elapsed
     */
    #[Transform('age')]
    public mixed $authorAge = null;

    /**
     * base64_encode: no params
     */
    #[Transform('base64_encode')]
    public string $encodedTitle = '';

    /**
     * hash: param key is 'algo' (NOT 'algorithm')
     * Default algo is 'sha256'
     */
    #[Transform('hash')]
    public string $tokenAudit = '';

    /**
     * hash with algo: md5 → 32-char hex string
     */
    #[Transform(['hash', ['algo' => 'md5']])]
    public string $cacheKey = '';

    public function __construct(
        string $publishedAt,
        string $authorBorn,
        mixed $authorAge,
        string $encodedTitle,
        string $tokenAudit,
        string $cacheKey,
    ) {
        $this->publishedAt = $publishedAt;
        $this->authorBorn = $authorBorn;
        $this->authorAge = $authorAge;
        $this->encodedTitle = $encodedTitle;
        $this->tokenAudit = $tokenAudit;
        $this->cacheKey = $cacheKey;
    }
}

// Input formats must match the rule's 'from' parameter
$past = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-2 hours');

$dto = new ArticleDto(
    publishedAt: $past->format('Y-m-d H:i:s'),   // matches relative_date default from: 'Y-m-d H:i:s'
    authorBorn: '20/06/1990',                     // matches date_to_iso8601 default from: 'd/m/Y'
    authorAge: '1985-08-12',                       // matches age default from: 'Y-m-d'
    encodedTitle: 'KaririCode Transformer v2 — Real-World PHP Transform Engine',
    tokenAudit: 'sk_live_kariricode_api_abc123',  // sha256 (default)
    cacheKey: 'article-slug-kariricode-v2',        // md5 via ['hash', ['algo' => 'md5']]
);

$result = $provider->createAttributeTransformer()->transform($dto);

echo "\n═══ AttributeTransformer — Date & Encoding ══════════════════════\n";
printf("  publishedAt    : %s\n", $dto->publishedAt);                          // "2 hours ago"
printf("  authorBorn     : %s\n", $dto->authorBorn);                           // ISO 8601
printf("  authorAge      : %d years\n", (int) $dto->authorAge);                // years
printf("  encodedTitle   : %s...\n", substr($dto->encodedTitle, 0, 44));       // base64
printf("  tokenAudit     : %s...  (%d chars = sha256)\n",
    substr($dto->tokenAudit, 0, 20), strlen($dto->tokenAudit));
printf("  cacheKey (md5) : %s  (%d chars = md5)\n",
    $dto->cacheKey, strlen($dto->cacheKey));

// ── base64 round-trip ──────────────────────────────────────────────────────────

$b64 = $dto->encodedTitle;
$roundTrip = $engine->transform(['encoded' => $b64], ['encoded' => ['base64_decode']]);
echo "\n═══ base64 Round-trip ═══════════════════════════════════════════\n";
printf("  decoded : %s\n", $roundTrip->get('encoded'));

// ── All 3 hash algorithms compared ───────────────────────────────────────────

$input = 'kariricode-transformer-v2.0.0';
$hashResult = $engine->transform(
    ['sha256' => $input, 'md5' => $input, 'sha1' => $input],
    [
        'sha256' => [['hash', ['algo' => 'sha256']]],  // 64 chars
        'md5'    => [['hash', ['algo' => 'md5']]],     // 32 chars
        'sha1'   => [['hash', ['algo' => 'sha1']]],    // 40 chars
    ],
);

echo "\n═══ Hash — 3 algorithms compared ════════════════════════════════\n";
printf("  sha256 (%2d chars) : %s\n", strlen($hashResult->get('sha256')), $hashResult->get('sha256'));
printf("  md5    (%2d chars) : %s\n", strlen($hashResult->get('md5')),    $hashResult->get('md5'));
printf("  sha1   (%2d chars) : %s\n", strlen($hashResult->get('sha1')),   $hashResult->get('sha1'));

// ── All date rules via Engine API with correct params ─────────────────────────

$threeHoursAgo = (new DateTimeImmutable())->modify('-3 hours')->format('Y-m-d H:i:s');
$dateResult = $engine->transform(
    [
        'age'       => '1990-06-20',            // Y-m-d — matches AgeRule default 'from'
        'iso8601'   => '15/01/2024 10:30:00',   // d/m/Y H:i:s — must pass custom 'from'
        'timestamp' => '2024-06-30',             // Y-m-d — matches DateToTimestampRule default 'format'
        'relative'  => $threeHoursAgo,           // Y-m-d H:i:s — matches RelativeDateRule default 'from'
    ],
    [
        'age'       => ['age'],
        'iso8601'   => [['date_to_iso8601', ['from' => 'd/m/Y H:i:s']]],
        'timestamp' => ['date_to_timestamp'],    // 'format' param defaults to 'Y-m-d'
        'relative'  => ['relative_date'],
    ],
);

echo "\n═══ All 4 Date Rules via Engine API ════════════════════════════\n";
printf("  age             : %d years\n", (int) $dateResult->get('age'));
printf("  date_to_iso8601 : %s\n", $dateResult->get('iso8601'));   // 2024-01-15T10:30:00+00:00
printf("  date_to_timestamp: %d  (unix)\n", (int) $dateResult->get('timestamp'));
printf("  relative_date   : %s\n", $dateResult->get('relative')); // "3 hours ago"
echo "═════════════════════════════════════════════════════════════════\n\n";
