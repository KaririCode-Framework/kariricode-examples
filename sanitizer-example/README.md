# KaririCode Sanitizer — Real-World Examples

Exemplos prontos para validar a implementação do `kariricode/sanitizer` em cenários reais.

## Estrutura

```
sanitizer-example/
├── composer.json
└── examples/
    ├── 01-user-registration.php   # trim, capitalize, email_filter, digits_only, normalize_whitespace
    ├── 02-blog-post.php           # slug, strip_tags, html_purify, truncate
    ├── 03-brazilian-documents.php # format_cpf, format_cnpj, format_cep
    ├── 04-product-import.php      # to_float, to_int, clamp, round, html_purify
    └── 05-engine-api.php          # SanitizerEngine programmatic API (no DTOs)
```

## Executar

```bash
composer install
php examples/01-user-registration.php
php examples/02-blog-post.php
php examples/03-brazilian-documents.php
php examples/04-product-import.php
php examples/05-engine-api.php
```

Ou todos de uma vez:
```bash
for f in examples/0*.php; do echo "--- $f ---"; php "$f"; echo; done
```

---

## Exemplo 01 — User Registration Form

Sanitiza dados de um formulário HTTP antes de persistir no banco.

```php
#[Sanitize('trim', 'capitalize')]
public string $firstName = '';

#[Sanitize('trim', 'lower_case', 'email_filter')]
public string $email = '';

#[Sanitize('digits_only')]
public string $phone = '';
```

**Input → Output:**
```
firstName : '  walmir   '    → 'Walmir'
email     : '  WALMIR@...  ' → 'walmir@kariricode.org'
phone     : '+55 (88) 9...'  → '5588999998888'
bio       : "PHP   dev.\r\n" → 'PHP developer. PHP lead at KaririCode. Loves clean code.'
```

---

## Exemplo 02 — Blog Post / CMS

Sanitiza conteúdo HTML gerado por usuário.

```php
#[Sanitize('trim', 'lower_case', 'slug')]
public string $slug = '';

#[Sanitize('strip_tags', 'normalize_whitespace', ['truncate', ['maxLength' => 200]])]
public string $excerpt = '';

#[Sanitize('html_purify')]
public string $body = '';
```

**Nota sobre `html_purify`:** remove tags perigosas (`<script>`) mas pode deixar o conteúdo de texto. Use em conjunto com `strip_tags` para excerpt puro.

---

## Exemplo 03 — Documentos Brasileiros

```php
#[Sanitize('digits_only', 'format_cpf')]
public string $cpf = '';         // '529.982.247-25'

#[Sanitize('digits_only', 'format_cnpj')]
public string $cnpj = '';        // '11.222.333/0001-81'

#[Sanitize('digits_only', 'format_cep')]
public string $postalCode = '';  // '63050-210'
```

---

## Exemplo 04 — Import de Produtos (E-commerce)

```php
#[Sanitize('to_float', ['round', ['precision' => 2]], ['clamp', ['min' => 0.01, 'max' => 9999.99]])]
public mixed $price = 0.0;

#[Sanitize('to_int', ['clamp', ['min' => 0, 'max' => 9999]])]
public mixed $stock = 0;
```

**Clamp em ação:**
```
price='-100'    → 0.01    (clamped to min)
price='150000'  → 9999.99 (clamped to max)
stock='-3'      → 0       (clamped to min)
```

---

## Exemplo 05 — SanitizerEngine Programmatic API

`SanitizerEngine::sanitize()` recebe `array $data` + `array $fieldRules` (mapa `campo → regras`):

```php
$engine = (new SanitizerServiceProvider())->createEngine();

$result = $engine->sanitize(
    data: ['name' => '  walmir  ', 'cpf' => '529.982.247-25'],
    fieldRules: [
        'name' => ['trim', 'capitalize'],
        'cpf'  => ['digits_only', 'format_cpf'],
    ],
);

$data = $result->getSanitizedData();
// $data['name'] === 'Walmir'
// $data['cpf']  === '529.982.247-25'
```

### Dot-notation para dados aninhados

```php
$result = $engine->sanitize(
    ['customer' => ['name' => '  walmir  ', 'email' => '  WALMIR@KARIRICODE.ORG  ']],
    [
        'customer.name'  => ['trim', 'capitalize'],
        'customer.email' => ['trim', 'lower_case', 'email_filter'],
    ],
);
// $result->getSanitizedData()['customer.name'] === 'Walmir'
```

---

## Comportamentos Descobertos / Notas

| Rule | Comportamento Real |
|---|---|
| `html_purify` | Remove tags perigosas mas mantém texto interno (ex: `<script>evil()</script>` → `evil()`) |
| `normalize_date` | Normaliza mas não converte formatos arbitrários — espera formatos reconhecíveis por `strtotime()` |
| `round` + `clamp` | Aplicar `round` **antes** de `clamp` para resultado correto |
| `SanitizerEngine` | Retorna `SanitizationResult` — use `->getSanitizedData()` para o array resultante |
| `format_cpf/cnpj/cep` | Espera apenas dígitos — sempre usar `digits_only` antes |
