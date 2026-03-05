<?php

declare(strict_types=1);

/**
 * Example 06 — Complete Rule Coverage (All 33 Built-in Rules)
 *
 * Demonstrates the 13 rules NOT covered by examples 01–05:
 *   String: normalize_line_endings, replace, regex_replace, strip_non_printable
 *   Html:   html_encode, html_decode, url_encode
 *   Type:   to_bool, to_string, to_array
 *   Date:   timestamp_to_date
 *   Filter: alpha_only, alphanumeric_only
 *
 * Combined with SanitizerEngine (no DTOs) to show all rules in action.
 *
 * !! PARAMETER REFERENCE (confirmed from rule implementations):
 *
 *  normalize_line_endings : no params — \r\n and \r → \n
 *  replace                : 'search'(string), 'replace'(string) — exact string match
 *  regex_replace          : 'pattern'(string PCRE), 'replacement'(string)
 *  strip_non_printable    : no params — removes control chars (\x00–\x1F, \x7F)
 *  html_encode            : no params — converts <>&"' to HTML entities
 *  html_decode            : no params — inverse: HTML entities → characters
 *  url_encode             : no params — percent-encodes the entire string
 *  to_bool                : no params — "1","true","yes","on" → true; rest → false
 *  to_string              : no params — casts any value via (string) cast
 *  to_array               : no params — wraps non-array in array; leaves arrays unchanged
 *  timestamp_to_date      : 'format'(string, default 'Y-m-d H:i:s') — Unix → date string
 *  alpha_only             : no params — keeps only a-z A-Z
 *  alphanumeric_only      : no params — keeps only a-z A-Z 0-9
 *
 * Run: php examples/06-all-rules-coverage.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

$engine    = (new SanitizerServiceProvider())->createEngine();
$sanitizer = (new SanitizerServiceProvider())->createAttributeSanitizer();

// ── Helper ────────────────────────────────────────────────────────────────────

function s(string $in): string
{
    return mb_substr($in, 0, 60);
}

function ok(string $label): void
{
    echo "  ✅ {$label}" . PHP_EOL;
}

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  STRING GROUP                                                            │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ String Rules ═══" . PHP_EOL . PHP_EOL;

// ── normalize_line_endings ────────────────────────────────────────────────────
// Converts \r\n (Windows) and \r (old Mac) to \n (Unix)

$raw  = "Walmir\r\nSilva\rKaririCode\n";
$res  = $engine->sanitize(['text' => $raw], ['text' => ['normalize_line_endings']]);
$norm = $res->getSanitizedData()['text'];

echo "normalize_line_endings:" . PHP_EOL;
echo "  in  : " . json_encode($raw)  . PHP_EOL;
echo "  out : " . json_encode($norm) . PHP_EOL;
assert(!str_contains($norm, "\r"), "\\r should be gone");
ok('normalize_line_endings');
echo PHP_EOL;

// ── replace ───────────────────────────────────────────────────────────────────
// Exact string replacement (no regex)

$raw = 'Hello World! This is KaririCode World!';
$res = $engine->sanitize(
    ['text' => $raw],
    ['text' => [['replace', ['search' => 'World', 'replace' => 'PHP']]]],
);
$out = $res->getSanitizedData()['text'];

echo "replace:" . PHP_EOL;
echo "  in  : '{$raw}'" . PHP_EOL;
echo "  out : '{$out}'" . PHP_EOL;
assert(!str_contains($out, 'World'), "'World' should be replaced");
assert(str_contains($out, 'PHP'),    "'PHP' should be present");
ok('replace');
echo PHP_EOL;

// ── regex_replace ─────────────────────────────────────────────────────────────
// Pattern-based replacement

$raw = 'walmir.silva+tag@kariricode.org';
$res = $engine->sanitize(
    ['email' => $raw],
    ['email' => [['regex_replace', ['pattern' => '/\+[^@]+/', 'replacement' => '']]]],
);
$out = $res->getSanitizedData()['email'];

echo "regex_replace (strip email plus alias):" . PHP_EOL;
echo "  in  : '{$raw}'" . PHP_EOL;
echo "  out : '{$out}'" . PHP_EOL;
assert(!str_contains($out, '+tag'), "plus alias should be stripped");
ok('regex_replace');
echo PHP_EOL;

// ── strip_non_printable ───────────────────────────────────────────────────────
// Removes control characters and non-printable bytes

$raw = "Walmir\x00\x01\x1F Silva\x7F";
$res = $engine->sanitize(['text' => $raw], ['text' => ['strip_non_printable']]);
$out = $res->getSanitizedData()['text'];

echo "strip_non_printable:" . PHP_EOL;
echo "  in  : " . json_encode($raw) . " (contains control chars)" . PHP_EOL;
echo "  out : '{$out}'" . PHP_EOL;
assert(!str_contains($out, "\x00"), "null byte should be stripped");
assert(!str_contains($out, "\x1F"), "control char should be stripped");
ok('strip_non_printable');
echo PHP_EOL;

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  HTML GROUP                                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Html Rules ═══" . PHP_EOL . PHP_EOL;

// ── html_encode ───────────────────────────────────────────────────────────────
// Converts < > & " ' to HTML entities

$raw = '<script>alert("XSS & injection \'attack\'")</script>';
$res = $engine->sanitize(['html' => $raw], ['html' => ['html_encode']]);
$out = $res->getSanitizedData()['html'];

echo "html_encode:" . PHP_EOL;
echo "  in  : {$raw}" . PHP_EOL;
echo "  out : {$out}" . PHP_EOL;
assert(str_contains($out, '&lt;'),  "< should be encoded");
assert(str_contains($out, '&amp;'), "& should be encoded");
assert(!str_contains($out, '<'),    "no raw < should remain");
ok('html_encode');
echo PHP_EOL;

// ── html_decode ───────────────────────────────────────────────────────────────
// Converts HTML entities back to characters (inverse of html_encode)

$raw = '&lt;h1&gt;KaririCode &amp; PHP&lt;/h1&gt;';
$res = $engine->sanitize(['html' => $raw], ['html' => ['html_decode']]);
$out = $res->getSanitizedData()['html'];

echo "html_decode:" . PHP_EOL;
echo "  in  : {$raw}" . PHP_EOL;
echo "  out : {$out}" . PHP_EOL;
assert(str_contains($out, '<h1>'), "should decode to <h1>");
assert(str_contains($out, '&'),    "& should be decoded");
ok('html_decode');
echo PHP_EOL;

// ── url_encode ────────────────────────────────────────────────────────────────
// Percent-encodes a string for use in a URL

$raw = 'name=Walmir Silva&lang=PHP 8.4+components';
$res = $engine->sanitize(['url' => $raw], ['url' => ['url_encode']]);
$out = $res->getSanitizedData()['url'];

echo "url_encode:" . PHP_EOL;
echo "  in  : '{$raw}'" . PHP_EOL;
echo "  out : '{$out}'" . PHP_EOL;
assert(str_contains($out, '%'),   "should be percent-encoded");
assert(!str_contains($out, ' '), "spaces should be encoded");
ok('url_encode');
echo PHP_EOL;

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  TYPE GROUP                                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Type Rules ═══" . PHP_EOL . PHP_EOL;

// ── to_bool ───────────────────────────────────────────────────────────────────
// Converts truthy strings ("1", "true", "yes", "on") to true, rest to false

$cases = ['active' => '1', 'newsletter' => 'true', 'terms' => 'yes', 'spam' => '0'];
$res   = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), ['to_bool']),
);
$out = $res->getSanitizedData();

echo "to_bool:" . PHP_EOL;
foreach ($cases as $k => $v) {
    $bool = $out[$k] ? 'true' : 'false';
    echo "  '{$v}' → {$bool}" . PHP_EOL;
}
assert($out['active']     === true,  "'1' should be true");
assert($out['newsletter'] === true,  "'true' should be true");
assert($out['terms']      === true,  "'yes' should be true");
assert($out['spam']       === false, "'0' should be false");
ok('to_bool');
echo PHP_EOL;

// ── to_string ─────────────────────────────────────────────────────────────────
// Casts any value to string

$cases = ['num' => 42, 'flt' => 3.14, 'bool' => true, 'nil' => null];
$res   = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), ['to_string']),
);
$out = $res->getSanitizedData();

echo "to_string:" . PHP_EOL;
foreach ($out as $k => $v) {
    echo "  {$k} : '" . $v . "' (" . gettype($v) . ")" . PHP_EOL;
}
assert(is_string($out['num']),  "int should become string");
assert(is_string($out['flt']),  "float should become string");
assert(is_string($out['bool']), "bool should become string");
ok('to_string');
echo PHP_EOL;

// ── to_array ──────────────────────────────────────────────────────────────────
// Wraps a non-array value into an array; leaves arrays untouched

$cases = ['single' => 'PHP', 'multi' => ['PHP', 'Go']];
$res   = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), ['to_array']),
);
$out = $res->getSanitizedData();

echo "to_array:" . PHP_EOL;
echo "  single → " . json_encode($out['single']) . PHP_EOL;
echo "  multi  → " . json_encode($out['multi'])  . PHP_EOL;
assert(is_array($out['single']),          "'PHP' should be wrapped in array");
assert($out['single'] === ['PHP'],        "wrapped correctly");
assert($out['multi']  === ['PHP', 'Go'], "array stays unchanged");
ok('to_array');
echo PHP_EOL;

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  DATE GROUP                                                               │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Date Rules ═══" . PHP_EOL . PHP_EOL;

// ── timestamp_to_date ────────────────────────────────────────────────────────
// Converts a Unix timestamp to a formatted date string

$cases = [
    'created_at' => '1741132800',  // 2025-03-05 UTC
    'updated_at' => time(),
];
$res = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), [['timestamp_to_date', ['format' => 'Y-m-d']]]),
);
$out = $res->getSanitizedData();

echo "timestamp_to_date:" . PHP_EOL;
echo "  created_at : {$out['created_at']}" . PHP_EOL;
echo "  updated_at : {$out['updated_at']}" . PHP_EOL;
assert(preg_match('/^\d{4}-\d{2}-\d{2}$/', $out['created_at']) === 1, "should be Y-m-d");
ok('timestamp_to_date');
echo PHP_EOL;

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  FILTER GROUP                                                             │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Filter Rules ═══" . PHP_EOL . PHP_EOL;

// ── alpha_only ────────────────────────────────────────────────────────────────
// Removes everything except a-z A-Z

$cases = [
    'first_name' => 'Walmir123!@#',
    'last_name'  => 'Silva 2024 & KaririCode',
];
$res = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), ['alpha_only']),
);
$out = $res->getSanitizedData();

echo "alpha_only:" . PHP_EOL;
foreach ($cases as $k => $v) {
    echo "  '{$v}' → '{$out[$k]}'" . PHP_EOL;
    assert(ctype_alpha($out[$k]), "result should be alpha-only");
}
ok('alpha_only');
echo PHP_EOL;

// ── alphanumeric_only ─────────────────────────────────────────────────────────
// Removes everything except a-z A-Z 0-9

$cases = [
    'username' => 'walmir.silva_2024!',
    'code'     => 'ABC-123 #special',
];
$res = $engine->sanitize(
    $cases,
    array_fill_keys(array_keys($cases), ['alphanumeric_only']),
);
$out = $res->getSanitizedData();

echo "alphanumeric_only:" . PHP_EOL;
foreach ($cases as $k => $v) {
    echo "  '{$v}' → '{$out[$k]}'" . PHP_EOL;
    assert(ctype_alnum($out[$k]), "result should be alphanumeric-only");
}
ok('alphanumeric_only');
echo PHP_EOL;

// ┌──────────────────────────────────────────────────────────────────────────┐
// │  BONUS: Attribute-based DTO combining multiple new rules                  │
// └──────────────────────────────────────────────────────────────────────────┘

echo "═══ Bonus: Real DTO — Developer Profile ===" . PHP_EOL . PHP_EOL;

final class DeveloperProfileDto
{
    /** Strip non-printable bytes that may come from copy-paste */
    #[Sanitize('trim', 'strip_non_printable', 'capitalize')]
    public string $name = '';

    /** Normalize all line endings before storing */
    #[Sanitize('normalize_line_endings', 'normalize_whitespace', ['truncate', ['maxLength' => 300]])]
    public string $bio = '';

    /** Strip email subaddress (plus alias) */
    #[Sanitize('trim', 'lower_case', ['regex_replace', ['pattern' => '/\+[^@]+/', 'replacement' => '']], 'email_filter')]
    public string $email = '';

    /** Replace legacy tokens in a template string */
    #[Sanitize(['replace', ['search' => '{YEAR}', 'replace' => '2025']])]
    public string $copyright = '';

    /** Accept "true"/"false"/"1"/"0" and normalise to boolean */
    #[Sanitize('to_bool')]
    public mixed $isPublic = false;

    /** Encode bio for safe HTML attribute embedding */
    #[Sanitize('html_encode')]
    public string $bioAttr = '';

    public function __construct(
        string $name,
        string $bio,
        string $email,
        string $copyright,
        mixed $isPublic,
        string $bioAttr,
    ) {
        $this->name      = $name;
        $this->bio       = $bio;
        $this->email     = $email;
        $this->copyright = $copyright;
        $this->isPublic  = $isPublic;
        $this->bioAttr   = $bioAttr;
    }
}

$dto = new DeveloperProfileDto(
    name:      "  walmir\x00 silva\x1F  ",
    bio:       "PHP developer.\r\nLead at KaririCode.\r\n\r\nLoves clean   code.",
    email:     '  WALMIR+newsletter@KARIRICODE.ORG  ',
    copyright: '© Walmir Silva {YEAR}. All rights reserved.',
    isPublic:  'true',
    bioAttr:   'PHP developer <expert> & open-source "advocate"',
);

$sanitizer->sanitize($dto);

echo "name      : '{$dto->name}'"      . PHP_EOL;
echo "bio       : '{$dto->bio}'"       . PHP_EOL;
echo "email     : '{$dto->email}'"     . PHP_EOL;
echo "copyright : '{$dto->copyright}'" . PHP_EOL;
echo "isPublic  : " . ($dto->isPublic ? 'true' : 'false') . PHP_EOL;
echo "bioAttr   : '{$dto->bioAttr}'"   . PHP_EOL;
echo PHP_EOL;

assert($dto->name      === 'Walmir Silva',                          "name clean + capitalized");
assert(!str_contains($dto->bio, "\r"),                              "bio: no CR");
assert(!str_contains($dto->email, '+newsletter'),                   "email: plus alias stripped");
assert($dto->email     === 'walmir@kariricode.org',                 "email clean");
assert(str_contains($dto->copyright, '2025'),                       "copyright: year replaced");
assert($dto->isPublic  === true,                                    "isPublic: 'true' → true");
assert(str_contains($dto->bioAttr, '&lt;'),                        "bioAttr: < encoded");
assert(!str_contains($dto->bioAttr, '<'),                           "bioAttr: no raw <");

echo "✅ All DeveloperProfileDto assertions passed!" . PHP_EOL . PHP_EOL;

echo "═══════════════════════════════════════════════════════" . PHP_EOL;
echo "✅ All 13 new rules validated — 33/33 rules covered!" . PHP_EOL;
echo "═══════════════════════════════════════════════════════" . PHP_EOL;
