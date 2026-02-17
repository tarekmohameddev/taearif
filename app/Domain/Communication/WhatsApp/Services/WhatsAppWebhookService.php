<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaNumber;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookService
{
    /**
     * Resolve tenant (user_id) and wa_number_id from webhook payload.
     * Returns ['user_id' => int, 'wa_number_id' => int] or null if unresolved.
     */
    public function resolveTenantFromPayload(array $payload, string $provider = 'meta'): ?array
    {
        if ($provider === 'meta') {
            $phoneNumberId = $payload['metadata']['phone_number_id'] ?? $payload['phone_number_id'] ?? null;
            $displayPhone = $payload['metadata']['display_phone_number'] ?? $payload['display_phone_number'] ?? null;

            if ($phoneNumberId !== null) {
                $waNumber = WaNumber::query()
                    ->where('provider', 'meta')
                    ->where('phone_number_id', (string) $phoneNumberId)
                    ->first();
                if ($waNumber) {
                    return ['user_id' => (int) $waNumber->user_id, 'wa_number_id' => (int) $waNumber->id];
                }
            }
            if ($displayPhone !== null) {
                $normalized = $this->normalizePhone($displayPhone);
                $waNumber = WaNumber::query()
                    ->where('provider', 'meta')
                    ->where('phone_number', $normalized)
                    ->first();
                if ($waNumber) {
                    return ['user_id' => (int) $waNumber->user_id, 'wa_number_id' => (int) $waNumber->id];
                }
            }
        }

        if ($provider === 'evolution') {
            $accountId = $payload['provider_account_id'] ?? $payload['instance'] ?? null;
            if ($accountId !== null) {
                $waNumber = WaNumber::query()
                    ->where('provider', 'evolution')
                    ->where('provider_account_id', (string) $accountId)
                    ->first();
                if ($waNumber) {
                    return ['user_id' => (int) $waNumber->user_id, 'wa_number_id' => (int) $waNumber->id];
                }
            }
        }

        Log::warning('communication.whatsapp.webhook.tenant_unresolved', [
            'reason' => 'tenant_unresolved',
            'provider' => $provider,
        ]);

        return null;
    }

    public function verifyMetaSignature(string $payload, string $signature): bool
    {
        $secret = config('communication.whatsapp.app_secret', '');
        if ($secret === '') {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    private function normalizePhone(string $value): string
    {
        $value = preg_replace('/[\s\-]+/', '', $value);
        if (preg_match('/^\+?\d+$/', $value)) {
            $value = ltrim($value, '+');
            if (strlen($value) > 0 && $value[0] !== '0') {
                $value = '+' . $value;
            }
        }

        return $value;
    }
}
