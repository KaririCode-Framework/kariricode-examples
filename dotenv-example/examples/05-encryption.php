<?php

declare(strict_types=1);

/**
 * Example 05: AES-256-GCM Encryption
 *
 * Demonstrates key generation, encrypting .env values, and transparent
 * decryption during load — all with zero external dependencies.
 *
 * !! PARAMETER REFERENCE (confirmed from implementations):
 *
 *  KeyPair::generate(): KeyPair
 *    - Generates a random 256-bit AES key.
 *    - KeyPair->privateKey : string (64-char hex) — used for encrypt/decrypt
 *    - KeyPair->publicId   : string (8-char)      — human-readable identifier
 *
 *  KeyPair::fromPrivateKey(privateKey: string): KeyPair
 *    - Reconstructs a KeyPair from an existing private key string.
 *
 *  Encryptor(privateKey: string):
 *    - $encryptor->encrypt(plaintext: string): string
 *      Returns "encrypted:<base64(nonce+ciphertext+tag)>" prefixed value
 *    - Encryptor::isEncrypted(value: string): bool
 *      Returns true if value starts with "encrypted:" AND payload is long enough
 *
 *  DotenvConfiguration(encryptionKey: string, ...):
 *    - If provided, all "encrypted:..." values are auto-decrypted on load().
 *    - ⚠️ The key must be the same privateKey used during encryption.
 *
 *  Encrypted values format: encrypted:<base64(nonce+ciphertext+tag)>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Security\Encryptor;
use KaririCode\Dotenv\Security\KeyPair;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

echo "═══════════════════════════════════════════════════════════\n";
echo "  05 — AES-256-GCM Encryption\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// ── 1. Generate a key pair ─────────────────────────────────────────────────
echo "── Key generation ────────────────────────────────────────\n";

$keyPair = KeyPair::generate();
echo "  Private key (hex-256): " . $keyPair->privateKey . "\n";
echo "  Public ID  (8-char)  : " . $keyPair->publicId . "\n";

// ── 2. Encrypt some secrets ────────────────────────────────────────────────
echo "\n── Encrypting secrets ────────────────────────────────────\n";

$encryptor = new Encryptor($keyPair->privateKey);

$secrets = [
    'DB_PASSWORD'     => 's3cr3t!',
    'API_PRIVATE_KEY' => 'pk_live_abc123xyz',
    'JWT_SECRET'      => 'super-secret-jwt-signing-key',
];

$encrypted = [];
foreach ($secrets as $name => $plaintext) {
    $ciphertext = $encryptor->encrypt($plaintext);
    $encrypted[$name] = $ciphertext;
    echo "  {$name}:\n";
    echo "    plain    : {$plaintext}\n";
    echo "    encrypted: {$ciphertext}\n\n";
}

// ── 3. Write a temporary encrypted .env ────────────────────────────────────
echo "── Writing encrypted .env ────────────────────────────────\n";

$tmpDir = sys_get_temp_dir() . '/kariricode-dotenv-enc-test';
@mkdir($tmpDir, 0o755, true);

$envContent = "APP_NAME=Encrypted App\n";
foreach ($encrypted as $name => $value) {
    $envContent .= "{$name}={$value}\n";
}
file_put_contents($tmpDir . '/.env', $envContent);
echo "  Written to: {$tmpDir}/.env\n";

// ── 4. Load with transparent decryption ───────────────────────────────────
echo "\n── Loading with transparent decryption ───────────────────\n";

$config = new DotenvConfiguration(encryptionKey: $keyPair->privateKey, loadMode: LoadMode::Overwrite);
$dotenv = new Dotenv($tmpDir, $config);
$dotenv->load();

echo "  APP_NAME    : " . $dotenv->get('APP_NAME') . "\n";
foreach (array_keys($secrets) as $name) {
    $decrypted = $dotenv->get($name);
    $original = $secrets[$name];
    $match = $decrypted === $original ? '✓ match' : '✗ MISMATCH';
    echo "  {$name} : {$decrypted} [{$match}]\n";
}

// ── 5. Encryptor::isEncrypted() detection ─────────────────────────────────
echo "\n── isEncrypted() detection ───────────────────────────────\n";

$testValues = [
    'hello world',
    $encrypted['DB_PASSWORD'],
    'encrypted:not_real_base64',
    'encrypted:' . base64_encode('short'),
];

foreach ($testValues as $val) {
    $flag = Encryptor::isEncrypted($val) ? 'yes' : 'no';
    $short = strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val;
    echo "  isEncrypted('{$short}'): {$flag}\n";
}

// ── 6. fromPrivateKey reconstruction ──────────────────────────────────────
echo "\n── KeyPair::fromPrivateKey() ─────────────────────────────\n";

$reconstructed = KeyPair::fromPrivateKey($keyPair->privateKey);
echo "  Same public ID? " . ($reconstructed->publicId === $keyPair->publicId ? '✓ yes' : '✗ no') . "\n";

// Cleanup
@unlink($tmpDir . '/.env');
@rmdir($tmpDir);

echo "\n✓ Example 05 completed.\n\n";
