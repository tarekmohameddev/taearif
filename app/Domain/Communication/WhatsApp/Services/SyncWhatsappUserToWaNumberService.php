<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Support\Facades\Log;

/**
 * Keeps Communication/AI `wa_numbers` in sync with legacy `whatsapp_users` rows.
 *
 * Meta Embedded Signup and Evolution linking historically wrote only to
 * whatsapp_users; the AI bot and /api/v1/whatsapp/* APIs key off wa_numbers.
 */
final class SyncWhatsappUserToWaNumberService
{
    /**
     * Upsert (or deactivate) the matching WaNumber for a WhatsappUser.
     *
     * Returns null when there is no provider phone id to key on, or when the
     * number is not active and no WaNumber row exists yet.
     *
     * @param  'meta'|'evolution'|null  $provider  Override inferred provider (use 'meta' from Meta webhooks).
     */
    public function sync(WhatsappUser $whatsappUser, ?string $provider = null): ?WaNumber
    {
        $phoneId = trim((string) ($whatsappUser->phone_id ?? ''));
        if ($phoneId === '') {
            return null;
        }

        $userId = (int) $whatsappUser->user_id;
        $isMeta = $this->resolveIsMeta($whatsappUser, $provider);
        $normalized = $this->normalizePhone((string) ($whatsappUser->number ?? ''));
        $desiredStatus = $this->mapStatus((string) ($whatsappUser->status ?? ''));

        $waNumber = $this->findExisting($userId, $phoneId, $normalized, $isMeta);

        $payload = [
            'provider'     => $isMeta ? 'meta' : 'evolution',
            'phone_number' => $normalized !== '' ? $normalized : (string) ($whatsappUser->number ?? ''),
            'name'         => $whatsappUser->name,
            'status'       => $desiredStatus,
        ];

        if ($isMeta) {
            $payload['phone_number_id'] = $phoneId;
            $payload['meta'] = [
                'access_token'    => $whatsappUser->access_token ?? $whatsappUser->token,
                'phone_number_id' => $phoneId,
                'waba_id'         => $whatsappUser->waba_id ?? $whatsappUser->business_id,
                'whatsapp_user_id'=> $whatsappUser->id,
            ];
        } else {
            $payload['provider_account_id'] = $phoneId;
            $payload['meta'] = [
                'instance'         => $phoneId,
                'whatsapp_user_id' => $whatsappUser->id,
            ];
        }

        if ($waNumber !== null) {
            $waNumber->update($payload);

            return $waNumber->refresh();
        }

        if ($desiredStatus !== 'active') {
            return null;
        }

        $payload['user_id'] = $userId;

        return WaNumber::create($payload);
    }

    /**
     * Best-effort sync that never throws — use from HTTP controllers.
     *
     * @param  'meta'|'evolution'|null  $provider
     */
    public function syncQuietly(WhatsappUser $whatsappUser, ?string $provider = null): ?WaNumber
    {
        try {
            return $this->sync($whatsappUser, $provider);
        } catch (\Throwable $e) {
            Log::warning('whatsapp.sync_wa_number.failed', [
                'whatsapp_user_id' => $whatsappUser->id,
                'user_id'          => $whatsappUser->user_id,
                'phone_id'         => $whatsappUser->phone_id,
                'error'            => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function normalizePhone(string $number): string
    {
        $cleaned = preg_replace('/[\s\-]/', '', $number) ?? $number;

        if (preg_match('/^\d+$/', $cleaned) && ! str_starts_with($cleaned, '0')) {
            return '+' . $cleaned;
        }

        return $cleaned;
    }

    public function phoneDigits(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }

    /**
     * @param  'meta'|'evolution'|null  $provider
     */
    private function resolveIsMeta(WhatsappUser $whatsappUser, ?string $provider): bool
    {
        if ($provider === 'meta') {
            return true;
        }
        if ($provider === 'evolution') {
            return false;
        }

        return $this->isMetaProvider($whatsappUser);
    }

    private function isMetaProvider(WhatsappUser $whatsappUser): bool
    {
        if (! empty($whatsappUser->token) || ! empty($whatsappUser->access_token)) {
            return true;
        }
        if (! empty($whatsappUser->waba_id) || ! empty($whatsappUser->business_id)) {
            return true;
        }

        $phoneId = trim((string) ($whatsappUser->phone_id ?? ''));

        return $phoneId !== '' && ctype_digit($phoneId);
    }

    private function mapStatus(string $status): string
    {
        return $status === 'active' ? 'active' : 'inactive';
    }

    private function findExisting(int $userId, string $phoneId, string $normalized, bool $isMeta): ?WaNumber
    {
        $base = WaNumber::query()->where('user_id', $userId);

        if ($isMeta) {
            $byPhoneId = (clone $base)->where('phone_number_id', $phoneId)->first();
            if ($byPhoneId !== null) {
                return $byPhoneId;
            }
        } else {
            $byAccountId = (clone $base)->where('provider_account_id', $phoneId)->first();
            if ($byAccountId !== null) {
                return $byAccountId;
            }
        }

        if ($normalized !== '') {
            $exact = (clone $base)->where('phone_number', $normalized)->first();
            if ($exact !== null) {
                return $exact;
            }
        }

        $digits = $this->phoneDigits($normalized !== '' ? $normalized : $phoneId);
        if ($digits === '') {
            return null;
        }

        $candidates = (clone $base)->whereNotNull('phone_number')->get();
        foreach ($candidates as $candidate) {
            if ($this->phoneDigits((string) $candidate->phone_number) === $digits) {
                return $candidate;
            }
        }

        return null;
    }
}
