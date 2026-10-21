<?php

namespace App\Helpers;

class CryptoHelper
{
    /**
     * Encrypt sensitive data using AES-256-GCM
     * Returns: iv (12 bytes) + tag (16 bytes) + ciphertext
     */
    public function encrypt(string $plaintext): string
    {
        $key = hash('sha256', $_ENV['ENCRYPTION_KEY'] ?? 'default-key', true);
        $iv = openssl_random_pseudo_bytes(12); // 96-bit IV for GCM
        $tag = '';
        
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        // Return: iv + tag + ciphertext (all binary)
        return $iv . $tag . $ciphertext;
    }

    /**
     * Decrypt data encrypted with encrypt()
     * Input format: iv (12 bytes) + tag (16 bytes) + ciphertext
     */
    public function decrypt(string $encrypted): ?string
    {
        if (strlen($encrypted) < 28) {
            error_log('CryptoHelper::decrypt - invalid encrypted data length: ' . strlen($encrypted));
            return null;
        }

        try {
            $key = hash('sha256', $_ENV['ENCRYPTION_KEY'] ?? 'default-key', true);
            $iv = substr($encrypted, 0, 12);
            $tag = substr($encrypted, 12, 16);
            $ciphertext = substr($encrypted, 28);

            error_log('CryptoHelper::decrypt - Attempting decrypt | data_len=' . strlen($encrypted) . ' | ciphertext_len=' . strlen($ciphertext));

            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                error_log('CryptoHelper::decrypt - Decryption failed (returned false)');
                return null;
            }

            return $plaintext ?: null;
        } catch (\Exception $e) {
            error_log('CryptoHelper::decrypt - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hash national ID for uniqueness checks (SHA-256)
     * Returns raw binary hash (32 bytes)
     */
    public static function hashNationalId(string $nationalId): string
    {
        return hash('sha256', $nationalId, true); // raw binary output
    }

    /**
     * Get last 4 characters of national ID for display
     */
    public static function getLastFour(string $nationalId): string
    {
        return substr($nationalId, -4);
    }

    /**
     * Verify national ID against hash (timing-safe comparison)
     */
    public static function verifyNationalId(string $plaintext, string $hash): bool
    {
        return hash_equals(self::hashNationalId($plaintext), $hash);
    }
}
