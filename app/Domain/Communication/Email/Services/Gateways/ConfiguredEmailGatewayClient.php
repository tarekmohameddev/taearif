<?php

namespace App\Domain\Communication\Email\Services\Gateways;

use App\Domain\Communication\Email\Contracts\EmailGatewayClient;
use App\Domain\Communication\Email\DTOs\EmailGatewaySendResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ConfiguredEmailGatewayClient implements EmailGatewayClient
{
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        array $meta = []
    ): EmailGatewaySendResult {
        $provider = (string) config('communication.email.provider', 'noop');

        if (! $this->isEnabled()) {
            return new EmailGatewaySendResult(
                false,
                null,
                $provider ?: 'noop',
                'email_disabled'
            );
        }

        if ($provider === '' || $provider === 'noop' || $provider === 'null') {
            return new EmailGatewaySendResult(
                true,
                'noop-' . Str::uuid()->toString(),
                'noop',
                null,
                ['mode' => 'noop', 'to' => $toEmail, 'subject' => $subject]
            );
        }

        try {
            $messageId = Str::uuid()->toString();
            $from = $fromEmail ?? (string) config('communication.email.default_from_email');
            $fromNameFinal = $fromName ?? (string) config('communication.email.default_from_name');

            Mail::send([], [], function ($message) use ($toEmail, $subject, $bodyHtml, $bodyText, $from, $fromNameFinal) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->from($from, $fromNameFinal)
                    ->html($bodyHtml);

                if ($bodyText !== null && trim($bodyText) !== '') {
                    $message->text($bodyText);
                }
            });

            return new EmailGatewaySendResult(
                true,
                $messageId,
                $provider,
                null,
                ['to' => $toEmail, 'subject' => $subject]
            );
        } catch (\Throwable $e) {
            return new EmailGatewaySendResult(
                false,
                null,
                $provider,
                $e->getMessage(),
                [],
                true
            );
        }
    }

    public function verifyWebhookSignature(string $rawBody, array $headers, string $secret): bool
    {
        $signatureHeader = $this->firstHeaderValue($headers, 'X-Email-Signature');
        if ($signatureHeader === null || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, trim($signatureHeader));
    }

    public function parseDeliveryWebhook(array $payload): array
    {
        $records = [];
        $provider = (string) ($payload['provider'] ?? config('communication.email.provider', 'unknown'));

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

    private function firstHeaderValue(array $headers, string $key): ?string
    {
        foreach ($headers as $k => $v) {
            if (strcasecmp((string) $k, $key) === 0) {
                return is_array($v) ? (string) ($v[0] ?? '') : (string) $v;
            }
        }

        return null;
    }

    private function isEnabled(): bool
    {
        return (bool) config('communication.enabled', false)
            && (bool) config('communication.email.enabled', false);
    }
}
