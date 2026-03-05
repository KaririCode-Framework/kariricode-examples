<div align="center">

# KaririCode Dotenv — Examples

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e.svg)](../../LICENSE)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-4F46E5)](https://phpstan.org/)
[![kariricode/dotenv](https://img.shields.io/badge/kariricode%2Fdotenv-v4.x-orange)](https://github.com/KaririCode-Framework/kariricode-dotenv)
[![ARFA 1.3](https://img.shields.io/badge/ARFA-1.3-blue)](https://kariricode.org)
[![KaririCode Framework](https://img.shields.io/badge/KaririCode-Framework-orange)](https://kariricode.org)

**8 real-world examples demonstrating every feature of `kariricode/dotenv` v4.**

From zero-config loading to AES-256-GCM encrypted secrets, cascading environments, schema validation, and the global `env()` helper — all covered and runnable.

[Installation](#installation) · [Quick Start](#quick-start) · [Examples](#examples) · [API Reference](#api-reference) · [Running](#running)

</div>

---

## The Problem

Environment variables are the standard mechanism for application configuration — but raw PHP access is untyped, unvalidated, and exposes sensitive data risks:

```php
// ❌ Raw anti-patterns — no type safety, no validation, no encryption
$port = $_ENV['DB_PORT'];           // "5432" (string, not int)
$debug = $_ENV['APP_DEBUG'];        // "true" (string, not bool)
$secret = $_ENV['API_KEY'];         // plaintext — leaked in logs
$missing = $_ENV['MISSING_VAR'];    // PHP Warning: Undefined array key
```

Schema drift, type bugs, and plaintext secrets in `.env` files cause **real production incidents**.

## The Solution

```php
// ✅ kariricode/dotenv — type-safe, validated, encrypted, cascading
$dotenv = new Dotenv(__DIR__, new DotenvConfiguration(typeCasting: true));
$dotenv->load();

$port   = $dotenv->get('DB_PORT');   // 5432    (int)
$debug  = $dotenv->get('APP_DEBUG'); // false   (bool)
$secret = $dotenv->get('API_KEY');   // auto-decrypted on load (AES-256-GCM)
$safe   = env('MISSING', 'default'); // 'default' — never throws
```

One library. **Zero manual casting. Zero undefined key warnings. Zero plaintext secrets.**

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.4 or higher |
| Composer | 2.x |

---

## Installation

```bash
git clone https://github.com/KaririCode-Framework/kariricode-examples.git
cd kariricode-examples/dotenv-example
composer install
```

> Uses a local path repository pointing to `../../kariricode-dotenv` (v4.x).  
> To use the released package: `composer require kariricode/dotenv`.

---

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

// 1. Configure
$config = new DotenvConfiguration(
    loadMode: LoadMode::Overwrite,   // Overwrite | Skip | Immutable
    typeCasting: true,               // "42" → int, "true" → bool, "null" → null
);

// 2. Load
$dotenv = new Dotenv(__DIR__, $config);
$dotenv->load();

// 3. Read (typed)
$port   = $dotenv->get('DB_PORT');         // int
$debug  = $dotenv->get('APP_DEBUG');       // bool
$config = $dotenv->get('JSON_CONFIG');     // array (auto-decoded from JSON)
$count  = $dotenv->get('MISSING', 0);     // 0 (default when absent)

// 4. Or use the global helper (after load)
use function KaririCode\Dotenv\env;
$port = env('DB_PORT', 5432);             // int
```

---

## Examples

| # | Script | Feature | Key APIs |
|---|--------|---------|----------|
| 01 | [`01-basic-loading.php`](examples/01-basic-loading.php) | Basic load + `get()` + `isLoaded()` + variable interpolation | `Dotenv`, `DotenvConfiguration`, `LoadMode` |
| 02 | [`02-type-casting.php`](examples/02-type-casting.php) | Auto type detection: int, float, bool, null, JSON array | `typeCasting: true`, `TypeSystem` |
| 03 | [`03-validation-dsl.php`](examples/03-validation-dsl.php) | Fluent validation DSL + collect-all errors | `validate()`, `required()`, `between()`, `custom()` |
| 04 | [`04-schema-validation.php`](examples/04-schema-validation.php) | Declarative `.env.schema` — no PHP validation code | `loadWithSchema()`, `ValidationException` |
| 05 | [`05-encryption.php`](examples/05-encryption.php) | AES-256-GCM encryption round-trip | `KeyPair::generate()`, `Encryptor`, `isEncrypted()` |
| 06 | [`06-processors.php`](examples/06-processors.php) | Variable processors — Trim, Base64, CSV, UrlNormalizer, custom | `addProcessor()`, `VariableProcessor` |
| 07 | [`07-boot-env.php`](examples/07-boot-env.php) | Cascading environments + `debug()` source tracking | `bootEnv()`, `DotenvConfiguration::environmentName` |
| 08 | [`08-env-helper.php`](examples/08-env-helper.php) | `env()` global helper — Laravel-style, typed | `use function KaririCode\Dotenv\env` |

---

## Running

```bash
# Run all examples sequentially
php run-all.php
# or
composer run run:all

# Run individually
php examples/01-basic-loading.php
php examples/05-encryption.php
# etc.
```

---

## API Reference

### `DotenvConfiguration` — all parameters

```php
new DotenvConfiguration(
    loadMode: LoadMode::Overwrite,   // Overwrite | Skip | Immutable
    typeCasting: true,               // Auto-cast: int, float, bool, null, array
    autoload: false,                 // Load .env on Dotenv::__construct()
    environmentName: 'local',        // Default env for bootEnv()
    encryptionKey: $key,             // 64-char hex key → enables auto-decrypt
);
```

### Type System Pipeline

| Raw `.env` value | PHP type | Example |
|-----------------|----------|---------|
| `42` | `int` | `DB_PORT=5432` |
| `3.14` | `float` | `SCORE=9.5` |
| `true` / `false` / `yes` / `no` | `bool` | `APP_DEBUG=true` |
| `null` / `~` / `""` | `null` | `NULLABLE_VAR=null` |
| `{"k":"v"}` | `array` | `JSON_CONFIG={"theme":"dark"}` |
| anything else | `string` | `APP_NAME=KaririCode` |

> ⚠️ Type detection order is fixed: null → bool → numeric → json → string.

### Validation DSL

```php
$dotenv->validate()
    ->required('APP_NAME', 'DB_HOST')         // must exist
    ->notEmpty('APP_NAME')                    // must not be ""
    ->isInteger('DB_PORT')->between(1, 65535) // int range
    ->isBoolean('APP_DEBUG')                  // true/false/1/0
    ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
    ->matchesRegex('APP_VERSION', '/^\d+\.\d+\.\d+$/')
    ->url('BASE_URL')
    ->email('ADMIN_EMAIL')
    ->ifPresent('REDIS_HOST')->notEmpty()     // conditional
    ->custom('MAX_UPLOAD_MB', fn($v) => (int)$v <= 100, 'Must be ≤ 100')
    ->assert();                               // throws ValidationException with ALL errors
```

### `.env.schema` Reference

```ini
# .env.schema — declarative, no PHP code needed
APP_ENV:      required, type=string, allowed=local,staging,production
APP_PORT:     required, type=int, min=1024, max=65535
APP_DEBUG:    required, type=bool
DB_POOL_SIZE: required, type=int, min=1, max=100
API_TIMEOUT:  required, type=int, min=1, max=300
```

### Encryption

```php
use KaririCode\Dotenv\Security\{Encryptor, KeyPair};

// 1. Generate key pair (once, store privateKey securely)
$keyPair = KeyPair::generate();
// $keyPair->privateKey : "a3f0..." (64-char hex)
// $keyPair->publicId   : "a3f0e1b2" (8-char identifier)

// 2. Encrypt secrets before writing to .env
$encryptor = new Encryptor($keyPair->privateKey);
$ciphertext = $encryptor->encrypt('s3cr3t_password');
// → "encrypted:<base64(nonce+ciphertext+tag)>"

// 3. Load with transparent decryption
$config = new DotenvConfiguration(encryptionKey: $keyPair->privateKey);
$dotenv = new Dotenv(__DIR__, $config);
$dotenv->load();
// All "encrypted:..." values are decrypted automatically
```

### Processors

```php
// Register before load() — glob patterns supported
$dotenv->addProcessor('*_NAME',    new TrimProcessor());
$dotenv->addProcessor('ENCODED_*', new Base64DecodeProcessor());
$dotenv->addProcessor('ALLOWED_IPS', new CsvToArrayProcessor());
$dotenv->addProcessor('*_URL',     new UrlNormalizerProcessor());

// Custom processor — implement VariableProcessor
$dotenv->addProcessor('APP_ENV', new class implements VariableProcessor {
    public function process(string $raw, mixed $typed): string {
        return strtoupper($raw);
    }
});
```

### `bootEnv()` — Cascade Loading

```
Cascade order:
  .env                   ← base defaults (commit to VCS)
  .env.local             ← local machine overrides (gitignored)
  .env.{env}             ← environment-specific (e.g. .env.staging)
  .env.{env}.local       ← local env override (skipped for 'test')
```

```php
$dotenv->bootEnv();               // auto-detects from APP_ENV
$dotenv->bootEnv('staging');      // explicit environment name

// Trace which file provided which variable
$report = $dotenv->debug();
// [ ['key' => 'DB_HOST', 'value' => 'localhost', 'source' => '.env'], ... ]
```

---

## Project Structure

```
dotenv-example/
├── .env                   # Base variables (committed)
├── .env.local             # Local overrides (gitignored)
├── .env.schema            # Declarative validation schema
├── composer.json
├── run-all.php            # Runs all 8 examples
└── examples/
    ├── 01-basic-loading.php     # Basic load + get() + interpolation
    ├── 02-type-casting.php      # Auto type detection
    ├── 03-validation-dsl.php    # Fluent validation
    ├── 04-schema-validation.php # .env.schema validation
    ├── 05-encryption.php        # AES-256-GCM encryption
    ├── 06-processors.php        # Variable processors
    ├── 07-boot-env.php          # Cascading environments
    └── 08-env-helper.php        # env() global helper
```

---

## Architecture & Standards

| Decision | Detail |
|----------|--------|
| ARFA 1.3 | All examples follow the KaririCode ARFA 1.3 standard |
| Type System | Deterministic pipeline: null → bool → numeric → json → string |
| Security | AES-256-GCM via PHP `sodium_crypto_aead_aes256gcm_*` (zero ext deps) |
| LoadMode | Overwrite (default) · Skip (idempotent) · Immutable (lockdown) |
| Cascade | Symfony-inspired `.env` → `.env.local` → `.env.{env}` → `.env.{env}.local` |
| Processors | Applied by glob pattern BEFORE type casting |
| Spec | SPEC-001 (type system), SPEC-002 (validation DSL), SPEC-003 (schema format) |

---

<div align="center">

Part of the [KaririCode Framework](https://kariricode.org) ecosystem.

[GitHub](https://github.com/KaririCode-Framework/kariricode-dotenv) · [Packagist](https://packagist.org/packages/kariricode/dotenv) · [Community](https://kariricode.org/community) · [Docs](https://docs.kariricode.org/dotenv)

*Built with ❤️ by [Walmir Silva](https://github.com/walmirsilva) · KaririCode Framework*

</div>
