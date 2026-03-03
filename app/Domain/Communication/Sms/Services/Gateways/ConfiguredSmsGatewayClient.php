<?php

namespace App\Domain\Communication\Sms\Services\Gateways;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use Illuminate\Support\Str;

class ConfiguredSmsGatewayClient implements SmsGatewayClient
{
    public function sendText(string $to, string $body, ?string $from = null, array $meta = []): SmsGatewaySendResult
    {
        $provider = (string) config('communication.sms.provider', 'noop');

        if (!$this->isEnabled()) {
            return new SmsGatewaySendResult(
                false,
                null,
                $provider ?: 'noop',
                'sms_disabled'
            );
        }

        if ($provider === '' || $provider === 'noop' || $provider === 'null') {
            return new SmsGatewaySendResult(
                true,
                'noop-' . Str::uuid()->toString(),
                'noop',
                null,
                ['mode' => 'noop']
            );
        }

        // Provider adapter hook for future gateways.
        return new SmsGatewaySendResult(
            true,
            $provider . '-' . Str::uuid()->toString(),
            $provider,
            null
        );
    }

    public function verifyWebhookSignature(string $rawBody, array $headers, string $secret): bool
    {
        $signatureHeader = $this->firstHeaderValue($headers, 'X-SMS-Signature');
        if ($signatureHeader === null || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, trim($signatureHeader));
    }

    public function parseDeliveryWebhook(array $payload): array
    {
        $records = [];
        $provider = (string) ($payload['provider'] ?? config('communication.sms.provider', 'unknown'));

        if (isset($payload['events']) && is_array($payload['events'])) {
            foreach ($payload['events'] as $event) {
                if (!is_array($event) || empty($event['gateway_message_id']) || empty($event['status'])) {
                    continue;
                }
                $records[] = [
                    'gateway_message_id' => (string) $event['gateway_message_id'],
                    'status' => (string) $event['status'],
                    'error_message' => isset($event['error_message']) ? (string) $event['error_message'] : null,
                    'delivered_at' => isset($event['delivered_at']) ? (string) $event['delivered_at'] : null,
                    'provider' => isset($event['provider']) ? (string) $event['provider'] : $provider,
                ];
            }

            return $records;
        }

        if (!empty($payload['gateway_message_id']) && !empty($payload['status'])) {
            $records[] = [
                'gateway_message_id' => (string) $payload['gateway_message_id'],
                'status' => (string) $payload['status'],
                'error_message' => isset($payload['error_message']) ? (string) $payload['error_message'] : null,
                'delivered_at' => isset($payload['delivered_at']) ? (string) $payload['delivered_at'] : null,
                'provider' => $provider,
            ];
        }

        return $records;
    }

    private function firstHeaderValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) !== 0) {
                continue;
            }

            if (is_array($value)) {
                return isset($value[0]) ? (string) $value[0] : null;
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    private function isEnabled(): bool
    {
        return (bool) config('communication.enabled', false)
            && (bool) config('communication.sms.enabled', false);
    }
}

