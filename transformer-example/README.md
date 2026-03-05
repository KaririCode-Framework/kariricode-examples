# KaririCode Transformer — Examples

Real-world examples for [`kariricode/transformer`](https://github.com/KaririCode-Framework/kariricode-transformer) **v2.0.0** — ARFA 1.3 / Spec V4.0.

## Requirements

- PHP **8.4+**
- Composer

## Installation

```bash
composer install
```

> Uses a local path repository (symlink to `../../kariricode-transformer`). Run against v2.0.0.

## Running

```bash
php examples/01-user-profile.php
php examples/02-brazilian-documents.php
php examples/03-product-import.php
php examples/04-data-pipeline.php
php examples/05-content-management.php
php examples/06-all-rules.php       # smoke test — all 32 rules
```

## Examples

| File | Scenario | Key topics |
|------|----------|------------|
| `01-user-profile.php` | User profile normalization | `#[Transform]`, String, Brazilian, Date, `TransformationResult` |
| `02-brazilian-documents.php` | CPF, CNPJ, CEP, phone | Attribute API vs Engine API, `isFieldTransformed()` |
| `03-product-import.php` | Product import pipeline | Numeric, Data (5), Encoding (3), `TransformerConfiguration` |
| `04-data-pipeline.php` | Analytics ETL | Structure (5), dot notation, inline rules, `merge()`, pipeline log |
| `05-content-management.php` | CMS article metadata | Date (4) + Encoding (3), base64 round-trip, hash comparison |
| `06-all-rules.php` | **All 32 rules smoke test** | Complete parameter reference |

---

## API Quick Reference

```php
// 1. Engine API (raw array)
$engine = (new TransformerServiceProvider())->createEngine();
$result = $engine->transform(
    ['name' => 'hello world', 'cpf' => '123.456.789-09'],
    ['name' => ['camel_case'], 'cpf' => ['cpf_to_digits']]
);
$result->get('name');                  // "helloWorld"
$result->getTransformedData();         // full transformed array
$result->wasTransformed();             // bool
$result->transformedFields();          // list of changed field names
$result->isFieldTransformed('name');   // bool
$result->transformationCount();        // int — number of logged changes (0 if tracking disabled)
$result->transformationsFor('name');   // list<FieldTransformation> per-field log
$result->merge($otherResult);          // combine two results

// 2. Attribute API (DTO)
class MyDto {
    #[Transform('camel_case')]
    public string $name = '';
    #[Transform(['hash', ['algo' => 'sha256']])]
    public string $token = '';
}
$result = (new TransformerServiceProvider())->createAttributeTransformer()->transform($dto);

// 3. Inline rule instance (no alias needed)
use KaririCode\Transformer\Rule\String\CamelCaseRule;
$engine->transform(['v' => 'hello'], ['v' => [new CamelCaseRule()]]);

// 4. Multi-rule ordered pipeline (rules run sequentially)
$engine->transform(['v' => 'Hello World'], ['v' => ['snake_case', 'reverse']]);

// 5. Dot notation (access nested keys)
$engine->transform($nested, ['user.profile.name' => ['pascal_case']]);

// 6. TransformerConfiguration
use KaririCode\Transformer\Configuration\TransformerConfiguration;
$engine = (new TransformerServiceProvider())->createEngine(
    new TransformerConfiguration(trackTransformations: false)  // no log overhead
);
```

---

## All 32 Rules — Parameter Reference

> Parameters shown are confirmed from the actual rule implementations.

### Brazilian (4)

| Alias | Input | Output | Params |
|-------|-------|--------|--------|
| `cpf_to_digits` | `123.456.789-09` | `12345678909` | none |
| `cnpj_to_digits` | `11.222.333/0001-81` | `11222333000181` | none |
| `cep_to_digits` | `60.175-110` | `60175110` | none |
| `phone_format` | `88999998888` | `(88) 99999-8888` | none; **input must be exactly 10 or 11 digits — no country code** |

### Data (5)

| Alias | Params | Notes |
|-------|--------|-------|
| `csv_to_array` | `separator(',')`, `enclosure('"')`, `header(true)` | `header=true` → first row used as keys; `header=false` → indexed rows |
| `json_decode` | `assoc(true)` | Converts JSON string to PHP array |
| `json_encode` | `flags(JSON_UNESCAPED_UNICODE\|SLASHES)` | Converts any value to JSON string |
| `array_to_key_value` | `key('id')`, `value('name')` | **Input must be a list of objects**, e.g. `[['id'=>1,'name'=>'A'],…]` |
| `implode` | `separator(',')` | Joins array elements into a string |

### Date (4)

> **IMPORTANT:** Each date rule has a `from` param matching the **input** date string format. If the format doesn't match, the rule silently returns the original value unchanged.

| Alias | Default `from` | Output | Special param |
|-------|---------------|--------|---------------|
| `age` | `'Y-m-d'` | `int` (years) | — |
| `date_to_iso8601` | `'d/m/Y'` | ISO 8601 string | `timezone('UTC')` |
| `date_to_timestamp` | — | Unix timestamp | **param key is `format` (not `from`), default `'Y-m-d'`** |
| `relative_date` | `'Y-m-d H:i:s'` | `"3 hours ago"` | `now(DateTimeInterface)` for testing |

### Encoding (3)

| Alias | Params | Notes |
|-------|--------|-------|
| `base64_encode` | none | Standard base64 |
| `base64_decode` | none | Returns original if invalid base64 |
| `hash` | **`algo('sha256')`** | ⚠️ param is `algo`, **NOT** `algorithm` |

Hash output lengths: `md5` = 32 chars, `sha1` = 40 chars, `sha256` = 64 chars.

### Numeric (4)

> ⚠️ `currency_format` has **NO** `locale` or `currency` params. Use `prefix`, `dec_point`, `thousands`.

| Alias | Params | BRL example |
|-------|--------|-------------|
| `currency_format` | `prefix('')`, `dec_point('.')`, `thousands(',')`, `decimals(2)` | `prefix='R$ '`, `dec_point=','`, `thousands='.'` |
| `percentage` | `decimals(1)` | `0.15` → `"15%"` |
| `ordinal` | none | `21` → `"21st"` |
| `number_to_words` | none | `42` → `"forty-two"` (0–999) |

### String (7)

| Alias | Params | Example |
|-------|--------|---------|
| `camel_case` | none | `"hello world"` → `"helloWorld"` |
| `snake_case` | none | `"Hello World"` → `"hello_world"` |
| `kebab_case` | none | `"Hello World"` → `"hello-world"` |
| `pascal_case` | none | `"hello world"` → `"HelloWorld"` |
| `mask` | `maskChar('*')`, `start(0)`, `end(0)` | `start=3, end=2`: `"secret123"` → `"sec***23"` |
| `reverse` | none | `"KaririCode"` → `"edoCiriraK"` |
| `repeat` | `times(1)` | `"KC-"` × 4 → `"KC-KC-KC-KC-"` |

### Structure (5)

| Alias | Params | Notes |
|-------|--------|-------|
| `flatten` | `separator('.')` | Nested array → flat dotted keys |
| `unflatten` | `separator('.')` | Flat dotted keys → nested array |
| `pluck` | `field('name')` | Extracts one column from a list |
| `group_by` | `field('type')` | Partitions list by field value → `['A' => [...], 'B' => [...]]` |
| `rename_keys` | `map([])` | `['old' => 'new']` — renames top-level keys |
