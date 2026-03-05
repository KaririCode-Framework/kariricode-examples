<?php

declare(strict_types=1);

/**
 * Example 02 — Blog Post / CMS Content Sanitization
 *
 * Real-world scenario: sanitizing user-generated HTML content (blog post title,
 * slug, excerpt and body) before storing to a CMS.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  trim          : no params — strips leading/trailing whitespace
 *  lower_case    : no params — converts to lowercase
 *  capitalize    : no params — capitalizes each word
 *  slug          : no params — converts to URL-safe slug (spaces→dash, lowercase)
 *  strip_tags    : no params — removes ALL HTML tags; leaves plain text
 *  html_purify   : no params — removes DANGEROUS tags (e.g. <script>) but keeps
 *                              safe ones (e.g. <strong>, <p>, <a>)
 *  normalize_whitespace: no params — collapses all whitespace sequences to single space
 *  truncate      : 'maxLength' (int) — cuts string at N chars; input over limit is cut
 *
 *  strip_tags vs html_purify:
 *    strip_tags  → plain text; all  HTML removed (use for excerpts, search indexes)
 *    html_purify → safe HTML; dangerous tags removed (use for rich-text bodies)
 *
 * Run: php examples/02-blog-post.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

// ── DTO ──────────────────────────────────────────────────────────────────────

final class BlogPostDto
{
    /**
     * trim → removes surrounding whitespace
     * truncate → maxLength: 100 — title must fit in 100 chars
     */
    #[Sanitize('trim', ['truncate', ['maxLength' => 100]])]
    public string $title = '';

    /**
     * trim → strips spaces
     * lower_case → forces lowercase before slugifying
     * slug → "PHP 8.4 — New Features!" → "php-84-new-features"
     *        (spaces → dash, special chars stripped, already lowercase)
     */
    #[Sanitize('trim', 'lower_case', 'slug')]
    public string $slug = '';

    /**
     * strip_tags → removes ALL HTML tags; output is plain text only
     * normalize_whitespace → collapses <br> remnants into single space
     * truncate → maxLength: 200 — limit excerpt length
     */
    #[Sanitize('strip_tags', 'normalize_whitespace', ['truncate', ['maxLength' => 200]])]
    public string $excerpt = '';

    /**
     * html_purify → removes only DANGEROUS tags like <script>, <iframe>, <on*>
     *               SAFE tags like <p>, <strong>, <em>, <a> are preserved
     */
    #[Sanitize('html_purify')]
    public string $body = '';

    public function __construct(
        string $title,
        string $slug,
        string $excerpt,
        string $body,
    ) {
        $this->title   = $title;
        $this->slug    = $slug;
        $this->excerpt = $excerpt;
        $this->body    = $body;
    }
}

// ── Dirty input ───────────────────────────────────────────────────────────────

$dto = new BlogPostDto(
    title:   '  PHP 8.4 — New Features & Improvements!  ',
    slug:    '  PHP 8.4 New Features & Improvements!  ',
    excerpt: '<p>PHP <strong>8.4</strong> brings <em>property hooks</em>, asymmetric visibility, and much more!</p>',
    body:    '<h1>PHP 8.4</h1><p>Brings <strong>property hooks</strong>.</p><script>alert("XSS")</script>',
);

// ── Sanitize ─────────────────────────────────────────────────────────────────

$sanitizer = (new SanitizerServiceProvider())->createAttributeSanitizer();
$sanitizer->sanitize($dto);

// ── Print result ─────────────────────────────────────────────────────────────

echo "\n═══ Blog Post / CMS Content Sanitization ═══════════════════════\n";
printf("  title   : '%s'\n", $dto->title);    // trimmed, truncated
printf("  slug    : '%s'\n", $dto->slug);     // 'php-84-new-features-improvements'
printf("  excerpt : '%s'\n", $dto->excerpt);  // plain text, no HTML tags
printf("  body    : '%s'\n", $dto->body);     // safe HTML — <script> removed
echo "═════════════════════════════════════════════════════════════════\n";

assert(!str_contains($dto->slug, ' '),       "slug should have no spaces");
assert(!str_contains($dto->slug, '&'),       "slug should have no special chars");
assert(!str_contains($dto->excerpt, '<'),    "excerpt should have no HTML tags");
assert(!str_contains($dto->body, '<script>'), "body should have no <script> tags");

echo "\n✅ All assertions passed!\n\n";
