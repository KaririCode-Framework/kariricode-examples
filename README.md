<div align="center">

# KaririCode Examples

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e.svg)](../LICENSE)
[![ARFA 1.3](https://img.shields.io/badge/ARFA-1.3-blue)](https://kariricode.org)
[![KaririCode Framework](https://img.shields.io/badge/KaririCode-Framework-orange)](https://kariricode.org)

**Real-world PHP 8.4 examples for every library in the KaririCode Framework ecosystem.**

33 example files · 4 libraries · 100% rule and feature coverage · all runnable with a single command.

[Transformer](#-transformer-example) · [Sanitizer](#-sanitizer-example) · [Dotenv](#-dotenv-example) · [ClassDiscovery](#-classdiscovery-example) · [Running All](#running-everything)

</div>

---

## Overview

This monorepo contains runnable example projects for each KaririCode Framework library. Every example is:

- ✅ **Self-contained** — has its own `composer.json` and `vendor/`
- ✅ **Commented** — every parameter documented with `!! PARAMETER REFERENCE` blocks
- ✅ **Verified** — all assertions pass, all rules demonstrated
- ✅ **ARFA 1.3 compliant** — follows KaririCode architectural standards

```
kariricode-examples/
├── transformer-example/       # 6 examples · 32 rules
├── sanitizer-example/         # 6 examples · 33 rules
├── dotenv-example/            # 8 examples · full v4 feature set
└── classdiscovery-example/    # 13 examples · all scanners & filters
```

---

## 🔄 Transformer Example

> **Turning raw data into clean, structured values** — 32 built-in transformation rules.

[`transformer-example/`](transformer-example/) · [README](transformer-example/README.md)

### What it covers

| # | Example | Highlights |
|---|---------|-----------|
| 01 | [user-profile.php](transformer-example/examples/01-user-profile.php) | `#[Transform]` attributes, String + Date + Brazilian rules, `TransformationResult` inspection |
| 02 | [brazilian-documents.php](transformer-example/examples/02-brazilian-documents.php) | CPF, CNPJ, CEP, phone — `AttributeTransformer` vs `TransformerEngine` |
| 03 | [product-import.php](transformer-example/examples/03-product-import.php) | Numeric, Data (5), Encoding (3) rules, `TransformerConfiguration` |
| 04 | [data-pipeline.php](transformer-example/examples/04-data-pipeline.php) | Structure (5) rules, dot notation, inline rules, `merge()` |
| 05 | [content-management.php](transformer-example/examples/05-content-management.php) | Date (4) + Encoding (3), base64 round-trip, hash comparison |
| 06 | [all-rules.php](transformer-example/examples/06-all-rules.php) | **All 32 rules** — smoke test with parameter reference |

### All 32 rules at a glance

```
Brazilian (4): cpf_to_digits  cnpj_to_digits  cep_to_digits  phone_format
Data      (5): csv_to_array  array_to_key_value  json_decode  json_encode  implode
Date      (4): age  date_to_iso8601  date_to_timestamp  relative_date
Encoding  (3): base64_encode  base64_decode  hash
Numeric   (4): currency_format  percentage  ordinal  number_to_words
String    (7): camel_case  snake_case  kebab_case  pascal_case  mask  reverse  repeat
Structure (5): flatten  unflatten  pluck  group_by  rename_keys
```

### Quick API

```php
use KaririCode\Transformer\Provider\TransformerServiceProvider;

$engine = (new TransformerServiceProvider())->createEngine();
$result = $engine->transform(
    ['name' => 'hello world', 'cpf' => '123.456.789-09'],
    ['name' => ['camel_case'], 'cpf' => ['cpf_to_digits']]
);

$result->get('name');                  // "helloWorld"
$result->wasTransformed();             // true
$result->transformedFields();          // ['name', 'cpf']
$result->isFieldTransformed('name');   // true
$result->transformationCount();        // 2
```

```bash
cd transformer-example && composer install && php examples/06-all-rules.php
```

---

## 🧹 Sanitizer Example

> **Cleaning and normalizing untrusted input** — 33 built-in sanitization rules.

[`sanitizer-example/`](sanitizer-example/) · [README](sanitizer-example/README.md)

### What it covers

| # | Example | Highlights |
|---|---------|-----------|
| 01 | [user-registration.php](sanitizer-example/examples/01-user-registration.php) | `#[Sanitize]` DTO, trim/capitalize/email_filter/digits_only, normalized bio |
| 02 | [blog-post.php](sanitizer-example/examples/02-blog-post.php) | `strip_tags` vs `html_purify`, slug generation, truncate |
| 03 | [brazilian-documents.php](sanitizer-example/examples/03-brazilian-documents.php) | CPF, CNPJ, CEP — `digits_only → format_*` pipeline ordering |
| 04 | [product-import.php](sanitizer-example/examples/04-product-import.php) | `to_float → round → clamp`, negative/overflow clamping |
| 05 | [engine-api.php](sanitizer-example/examples/05-engine-api.php) | Raw array Engine API, dot-notation nested fields, `normalize_date` |
| 06 | [all-rules-coverage.php](sanitizer-example/examples/06-all-rules-coverage.php) | **All 33 rules** — smoke test with full parameter reference |

### All 33 rules at a glance

```
String  (8): trim  normalize_whitespace  normalize_line_endings  truncate
             replace  regex_replace  strip_non_printable  slug
Case    (3): capitalize  lower_case  upper_case
Html    (5): strip_tags  html_purify  html_encode  html_decode  url_encode
Type    (4): to_float  to_int  to_bool  to_string  to_array
Numeric (3): round  clamp  to_string
Date    (2): normalize_date  timestamp_to_date
Filter  (3): digits_only  alpha_only  alphanumeric_only  email_filter
Format  (3): format_cpf  format_cnpj  format_cep
```

### Quick API

```php
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;

// Array API — no DTO needed
$engine = (new SanitizerServiceProvider())->createEngine();
$result = $engine->sanitize(
    ['name' => '  walmir silva  ', 'price' => '-5'],
    [
        'name'  => ['trim', 'capitalize'],
        'price' => ['to_float', ['clamp', ['min' => 0.01, 'max' => 9999.99]]],
    ]
);
$result->getSanitizedData(); // ['name' => 'Walmir Silva', 'price' => 0.01]
```

```bash
cd sanitizer-example && composer install && php examples/06-all-rules-coverage.php
```

---

## 🌱 Dotenv Example

> **Type-safe environment configuration** — loading, casting, validation, encryption, and cascading.

[`dotenv-example/`](dotenv-example/) · [README](dotenv-example/README.md)

### What it covers

| # | Example | Highlights |
|---|---------|-----------|
| 01 | [basic-loading.php](dotenv-example/examples/01-basic-loading.php) | `Dotenv::load()`, `get()`, variable interpolation, `isLoaded()` |
| 02 | [type-casting.php](dotenv-example/examples/02-type-casting.php) | Auto type detection: int, float, bool, null, JSON array |
| 03 | [validation-dsl.php](dotenv-example/examples/03-validation-dsl.php) | Fluent DSL: `required`, `between`, `allowedValues`, `custom`, collect-all errors |
| 04 | [schema-validation.php](dotenv-example/examples/04-schema-validation.php) | Declarative `.env.schema` — no PHP validation code needed |
| 05 | [encryption.php](dotenv-example/examples/05-encryption.php) | AES-256-GCM: `KeyPair::generate()`, `Encryptor`, transparent decrypt on load |
| 06 | [processors.php](dotenv-example/examples/06-processors.php) | `addProcessor()`: Trim, Base64, CSV, UrlNormalizer, custom by glob |
| 07 | [boot-env.php](dotenv-example/examples/07-boot-env.php) | Cascade `.env → .env.local → .env.{env} → .env.{env}.local`, `debug()` |
| 08 | [env-helper.php](dotenv-example/examples/08-env-helper.php) | `env()` global helper — typed, default-safe, Laravel-compatible |

### Quick API

```php
use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;
use function KaririCode\Dotenv\env;

$dotenv = new Dotenv(__DIR__, new DotenvConfiguration(
    loadMode: LoadMode::Overwrite,
    typeCasting: true,
));
$dotenv->load();

env('DB_PORT');           // 5432 (int, auto-cast)
env('APP_DEBUG');         // false (bool)
env('MISSING', 'default'); // 'default' — never throws
```

```bash
cd dotenv-example && composer install && php run-all.php
```

---

## 🔍 ClassDiscovery Example

> **Automatic class discovery** — token-based and reflection scanners, multi-tier caching, attribute filters.

[`classdiscovery-example/`](classdiscovery-example/) · [README](classdiscovery-example/README.md)

### What it covers

| # | Example | Highlights |
|---|---------|-----------|
| 01 | [basic-file-scanner.php](classdiscovery-example/examples/01-basic-file-scanner.php) | `FileScanner`, `ComposerNamespaceResolver`, `DiscoveryResult`, scan timing |
| 02 | [attribute-filter-route.php](classdiscovery-example/examples/02-attribute-filter-route.php) | `AttributeFilter` — find only `#[Route]`-annotated classes |
| 03 | [service-auto-registration.php](classdiscovery-example/examples/03-service-auto-registration.php) | DI container auto-registration via `#[Service]` |
| 04 | [event-listener-discovery.php](classdiscovery-example/examples/04-event-listener-discovery.php) | Event dispatcher map from `#[EventListener]` |
| 05 | [file-cache-strategy.php](classdiscovery-example/examples/05-file-cache-strategy.php) | `FileCacheStrategy` — cold vs warm performance comparison |
| 06 | [reflection-scanner.php](classdiscovery-example/examples/06-reflection-scanner.php) | `ReflectionScanner` — full attribute instances, methods, properties |
| 07 | [attribute-scanner.php](classdiscovery-example/examples/07-attribute-scanner.php) | `AttributeScanner::scanForAttribute()` — 10× faster, no class loading |
| 08 | [composite-filter.php](classdiscovery-example/examples/08-composite-filter.php) | `CompositeFilter::and()` / `::or()`, `NamespaceFilter`, `StructuralFilter` |
| 09 | [chain-cache-strategy.php](classdiscovery-example/examples/09-chain-cache-strategy.php) | `ChainCacheStrategy` — Memory (L1) + File (L2) tiered cache |
| 10 | [directory-scanner.php](classdiscovery-example/examples/10-directory-scanner.php) | `DirectoryScanner` — depth limit, glob pattern, symlink policy |
| 11 | [dependency-analyzer.php](classdiscovery-example/examples/11-dependency-analyzer.php) | `DependencyAnalyzer`, Tarjan circular dependency detection |
| 12 | [result-methods.php](classdiscovery-example/examples/12-result-methods.php) | `filter()`, `merge()`, `hasErrors()`, `hasClass()`, `getClass()` |
| 13 | [psr11-integration.php](classdiscovery-example/examples/13-psr11-integration.php) | `PSR11Integration`, `ConfiguratorBridge` — framework integration factories |

### Quick API

```php
use KaririCode\ClassDiscovery\Filter\AttributeFilter;
use KaririCode\ClassDiscovery\Scanner\{ComposerNamespaceResolver, FileScanner};

$scanner = new FileScanner(new ComposerNamespaceResolver(
    composerJsonPath: __DIR__ . '/composer.json',
    includeDevAutoload: false,
));
$scanner->addFilter(new AttributeFilter(Route::class));

$result = $scanner->scan(['src/Controller']);

foreach ($result as $fqcn => $meta) {
    // $meta->filePath, $meta->attributes[], $meta->isFinal, ...
}

echo $result->count() . " controllers in "
   . round($result->getScanDuration() * 1000, 1) . "ms\n";
```

```bash
cd classdiscovery-example && composer install && php run-all.php
```

---

## Running Everything

### Individual projects

```bash
# Transformer
cd transformer-example && composer install
php examples/06-all-rules.php           # all 32 rules

# Sanitizer
cd sanitizer-example && composer install
php examples/06-all-rules-coverage.php  # all 33 rules

# Dotenv
cd dotenv-example && composer install
php run-all.php                          # all 8 examples

# ClassDiscovery
cd classdiscovery-example && composer install
php run-all.php                          # all 13 examples
```

### All at once

```bash
for dir in transformer-example sanitizer-example dotenv-example classdiscovery-example; do
    echo "=== $dir ===" && cd "$dir" && composer install -q && php run-all.php && cd ..
done
```

---

## Library Versions

| Library | Version | Examples | Rules / Features |
|---------|---------|----------|-----------------|
| [`kariricode/transformer`](https://github.com/KaririCode-Framework/kariricode-transformer) | v2.0.0 | 6 | 32 rules |
| [`kariricode/sanitizer`](https://github.com/KaririCode-Framework/kariricode-sanitizer) | v2.x | 6 | 33 rules |
| [`kariricode/dotenv`](https://github.com/KaririCode-Framework/kariricode-dotenv) | v4.x | 8 | Full feature set |
| [`kariricode/class-discovery`](https://github.com/KaririCode-Framework/kariricode-classdiscovery) | v2.x | 13 | All scanners & filters |

---

<div align="center">

Part of the [KaririCode Framework](https://kariricode.org) ecosystem.

[GitHub Organization](https://github.com/KaririCode-Framework) · [Packagist](https://packagist.org/packages/kariricode/) · [Community](https://kariricode.org/community) · [Docs](https://docs.kariricode.org)

*Built with ❤️ by [Walmir Silva](https://github.com/walmirsilva) · KaririCode Framework*

</div>
