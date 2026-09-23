<?php

declare(strict_types=1);

namespace Demo\Security;

use RuntimeException;

final class Encryption
{
    private string $key;

    public function __construct(string $base64Key)
    {
        $decoded = base64_decode($base64Key, true);

        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException(
                'The encryption key must be a valid base64-encoded Sodium secretbox key.'
            );
        }

        $this->key = $decoded;
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $ciphertext = sodium_crypto_secretbox(
            $plaintext,
            $nonce,
            $this->key
        );

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $encryptedValue): string
    {
        $decoded = base64_decode($encryptedValue, true);

        if ($decoded === false) {
            throw new RuntimeException('Encrypted value is not valid base64.');
        }

        $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        if (strlen($decoded) <= $nonceLength) {
            throw new RuntimeException('Encrypted value is malformed.');
        }

        $nonce = substr($decoded, 0, $nonceLength);
        $ciphertext = substr($decoded, $nonceLength);

        $plaintext = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $this->key
        );

        if ($plaintext === false) {
            throw new RuntimeException('Unable to decrypt value.');
        }

        return $plaintext;
    }
}
