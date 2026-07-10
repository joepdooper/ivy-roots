<?php

namespace Ivy\Shared\Infrastructure\Service;

use Random\RandomException;
use RuntimeException;

class CryptedService
{
    private string $key;

    public function __construct()
    {
        $appKey = $_ENV['APP_KEY'] ?? null;

        if (! is_string($appKey) || $appKey === '') {
            throw new RuntimeException('APP_KEY is missing');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7), true);

            if ($appKey === false) {
                throw new RuntimeException('APP_KEY base64 decode failed');
            }
        }

        if (strlen($appKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_KEY must be 32 bytes after decoding');
        }

        $this->key = $appKey;
    }

    /**
     * @throws RandomException
     */
    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return base64_encode($nonce.$cipher);
    }

    public function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid encrypted payload');
        }

        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        if (strlen($decoded) < $nonceSize) {
            throw new RuntimeException('Corrupted encrypted payload');
        }

        $nonce = substr($decoded, 0, $nonceSize);
        $cipher = substr($decoded, $nonceSize);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);

        if ($plain === false) {
            throw new RuntimeException('Decryption failed (invalid key or tampered data)');
        }

        return $plain;
    }
}
