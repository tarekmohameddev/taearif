<?php

namespace App\Domain\Communication\Services;

class WebhookEventNormalizer
{
    /** Keys to strip from payload for deterministic event_hash (spec minimum set). */
    private const STRIP_KEYS = [
        'timestamp',
        'delivery_timestamp',
        'event_time',
        'retry_count',
        'retry_attempt',
        'received_at',
        'processed_at',
        'webhook_received_at',
        'signature',
        'x_hub_signature_256',
        'x_sms_signature',
    ];

    /**
     * Normalize payload for hashing: strip dynamic/volatile keys recursively.
     */
    public function normalizeForHash(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            $keyLower = is_string($key) ? strtolower($key) : $key;
            if (in_array($keyLower, self::STRIP_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeForHash($value);
            } else {
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    /**
     * Compute deterministic event_hash from payload.
     */
    public function computeEventHash(array $payload): string
    {
        $normalized = $this->normalizeForHash($payload);
        $this->sortRecursive($normalized);
        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function sortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
    }
}
