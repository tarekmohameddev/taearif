<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class TempTokenService
{
    protected static function getKey()
    {
        return base64_decode(env('GOOGLE_TEMP_TOKEN_SECRET'));
    }

    public static function generate(array $payload): string
    {
        $plaintext = json_encode($payload);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', self::getKey(), 0, $iv);

        if (!$encrypted) {
            throw new Exception('Encryption failed');
        }

        return base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'value' => $encrypted,
        ]));
    }

    public static function decrypt(string $token): ?array
    {
        try {
            $decoded = json_decode(base64_decode($token), true);

            if (!isset($decoded['iv'], $decoded['value'])) {
                throw new Exception('Malformed token');
            }

            $iv = base64_decode($decoded['iv']);
            $decrypted = openssl_decrypt($decoded['value'], 'aes-256-cbc', self::getKey(), 0, $iv);

            $data = json_decode($decrypted, true);

            if (!is_array($data) || !isset($data['expires_at'])) {
                throw new Exception('Invalid token payload');
            }

            if (now()->timestamp > $data['expires_at']) {
                return null;
            }

            return $data;
        } catch (Exception $e) {
            Log::warning('Temp token decryption failed: ' . $e->getMessage());
            return null;
        }
    }
}
