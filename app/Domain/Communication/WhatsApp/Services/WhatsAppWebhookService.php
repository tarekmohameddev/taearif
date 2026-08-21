<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppWebhookService
{
    private const UNRESOLVED_MAPPING_WARNING_TTL_SECONDS = 3600;

    public function __construct(
        private readonly SyncWhatsappUserToWaNumberService $syncWaNumber,
    ) {}

    /**
     * Resolve tenant (user_id) and wa_number_id from webhook payload.
     * Returns ['user_id' => int, 'wa_number_id' => int] or null if unresolved.
     */
    public function resolveTenantFromPayload(array $payload, string $provider = 'meta'): ?array
    {
        $phoneNumberId = $payload['metadata']['phone_number_id'] ?? $payload['phone_number_id'] ?? null;
        $displayPhone = $payload['metadata']['display_phone_number'] ?? $payload['display_phone_number'] ?? null;

        $waNumber = $this->findWaNumberByProviderId($provider, $phoneNumberId, $payload);

        if ($waNumber === null) {
            $healed = $this->healFromWhatsappUser($provider, $phoneNumberId !== null ? (string) $phoneNumberId : null);
            if ($healed !== null) {
                $this->logResolved($provider, $healed, 'whatsapp_user_backfill');

                return ['user_id' => (int) $healed->user_id, 'wa_number_id' => (int) $healed->id];
            }

            $waNumber = $this->findWaNumberByDisplayPhone($provider, $displayPhone);
        }

        if ($waNumber !== null) {
            $matchedBy = $this->describeMatch($waNumber, $phoneNumberId, $displayPhone);
            $this->logResolved($provider, $waNumber, $matchedBy);

            return ['user_id' => (int) $waNumber->user_id, 'wa_number_id' => (int) $waNumber->id];
        }

        $this->logUnresolved($provider, $phoneNumberId, $displayPhone, $payload);

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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findWaNumberByProviderId(string $provider, mixed $phoneNumberId, array $payload): ?WaNumber
    {
        if ($provider === 'meta' && $phoneNumberId !== null) {
            return WaNumber::query()
                ->where('provider', 'meta')
                ->where('phone_number_id', (string) $phoneNumberId)
                ->first();
        }

        if ($provider === 'evolution') {
            $accountId = $payload['provider_account_id'] ?? $payload['instance'] ?? null;
            if ($accountId !== null) {
                return WaNumber::query()
                    ->where('provider', 'evolution')
                    ->where('provider_account_id', (string) $accountId)
                    ->first();
            }
        }

        return null;
    }

    private function findWaNumberByDisplayPhone(string $provider, mixed $displayPhone): ?WaNumber
    {
        if ($provider !== 'meta' || $displayPhone === null) {
            return null;
        }

        $normalized = $this->normalizePhone((string) $displayPhone);
        $waNumber = WaNumber::query()
            ->where('provider', 'meta')
            ->where('phone_number', $normalized)
            ->first();
        if ($waNumber) {
            return $waNumber;
        }

        return $this->findMetaByPhoneDigits((string) $displayPhone);
    }

    private function healFromWhatsappUser(string $provider, ?string $phoneNumberId): ?WaNumber
    {
        if ($phoneNumberId === null || $phoneNumberId === '') {
            return null;
        }

        $whatsappUser = WhatsappUser::query()
            ->where('phone_id', $phoneNumberId)
            ->where('status', 'active')
            ->first();

        if ($whatsappUser === null) {
            return null;
        }

        $providerOverride = in_array($provider, ['meta', 'evolution'], true) ? $provider : null;

        return $this->syncWaNumber->syncQuietly($whatsappUser, $providerOverride);
    }

    private function findMetaByPhoneDigits(string $displayPhone): ?WaNumber
    {
        $digits = $this->syncWaNumber->phoneDigits($displayPhone);
        if ($digits === '') {
            return null;
        }

        $candidates = WaNumber::query()
            ->where('provider', 'meta')
            ->whereNotNull('phone_number')
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->syncWaNumber->phoneDigits((string) $candidate->phone_number) === $digits) {
                return $candidate;
            }
        }

        return null;
    }

    private function describeMatch(WaNumber $waNumber, mixed $phoneNumberId, mixed $displayPhone): string
    {
        if ($phoneNumberId !== null && (string) $waNumber->phone_number_id === (string) $phoneNumberId) {
            return 'phone_number_id';
        }
        if ($displayPhone !== null) {
            $normalized = $this->normalizePhone((string) $displayPhone);
            if ((string) $waNumber->phone_number === $normalized) {
                return 'display_phone_number';
            }
            if ($this->syncWaNumber->phoneDigits((string) $waNumber->phone_number) === $this->syncWaNumber->phoneDigits((string) $displayPhone)) {
                return 'display_phone_number_digits';
            }
        }

        return 'whatsapp_user_backfill';
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

    private function logResolved(string $provider, WaNumber $waNumber, string $matchedBy): void
    {
        Log::debug('communication.whatsapp.wa_number_mapping', [
            'outcome' => 'resolved',
            'provider' => $provider,
            'matched_by' => $matchedBy,
            'user_id' => (int) $waNumber->user_id,
            'wa_number_id' => (int) $waNumber->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logUnresolved(string $provider, mixed $phoneNumberId, mixed $displayPhone, array $payload): void
    {
        $context = [
            'outcome' => 'unresolved',
            'provider' => $provider,
            'phone_number_id' => $phoneNumberId !== null ? (string) $phoneNumberId : null,
            'display_phone_number' => $displayPhone !== null ? (string) $displayPhone : null,
            'provider_account_id' => isset($payload['provider_account_id']) ? (string) $payload['provider_account_id'] : null,
            'instance' => isset($payload['instance']) ? (string) $payload['instance'] : null,
        ];

        $fingerprint = sha1(implode('|', [
            (string) ($context['provider'] ?? ''),
            (string) ($context['phone_number_id'] ?? ''),
            (string) ($context['display_phone_number'] ?? ''),
            (string) ($context['provider_account_id'] ?? ''),
            (string) ($context['instance'] ?? ''),
        ]));
        $cacheKey = 'communication.whatsapp.wa_number_mapping.unresolved.'.$fingerprint;

        $shouldWarn = true;
        try {
            $shouldWarn = Cache::add($cacheKey, 1, self::UNRESOLVED_MAPPING_WARNING_TTL_SECONDS);
        } catch (Throwable $e) {
            $shouldWarn = true;
        }

        if ($shouldWarn) {
            Log::warning('communication.whatsapp.wa_number_mapping', $context);

            return;
        }

        Log::debug('communication.whatsapp.wa_number_mapping', $context);
    }
}
