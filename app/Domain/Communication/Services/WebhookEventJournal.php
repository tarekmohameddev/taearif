<?php

namespace App\Domain\Communication\Services;

use App\Models\CommunicationWebhookEvent;
use Illuminate\Support\Facades\DB;

class WebhookEventJournal
{
    public function __construct(
        private readonly WebhookEventNormalizer $normalizer
    ) {}

    /**
     * Journal a webhook event with dual dedupe. Returns the created event row, or null if duplicate.
     * Duplicate = same (provider, provider_event_id) when provider_event_id not null, or same (provider, event_hash).
     */
    public function journal(
        string $channel,
        string $provider,
        string $eventType,
        ?string $providerEventId,
        ?string $providerMessageId,
        array $payload,
        bool $signatureValid,
        bool $tenantResolved,
        ?int $userId = null
    ): ?CommunicationWebhookEvent {
        $eventHash = $this->normalizer->computeEventHash($payload);

        try {
            return CommunicationWebhookEvent::create([
                'user_id' => $userId,
                'channel' => $channel,
                'provider' => $provider,
                'event_type' => $eventType,
                'provider_event_id' => $providerEventId,
                'provider_message_id' => $providerMessageId,
                'event_hash' => $eventHash,
                'signature_valid' => $signatureValid,
                'tenant_resolved' => $tenantResolved,
                'processing_result' => 'processed',
                'payload' => $payload,
                'received_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Mark event as processed (or ignored/failed) after business logic runs.
     */
    public function markProcessed(int $eventId, string $result = 'processed', ?string $errorCode = null, ?string $errorMessage = null): void
    {
        CommunicationWebhookEvent::where('id', $eventId)->update([
            'processing_result' => $result,
            'processed_at' => now(),
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    private function isDuplicateKeyException(\Illuminate\Database\QueryException $e): bool
    {
        $message = $e->getMessage();
        if (str_contains($message, 'Duplicate entry') || str_contains($message, 'UNIQUE')) {
            return true;
        }
        $code = $e->getCode();
        if ($code === '23000' || $code === 23000) {
            return true;
        }
        $errorInfo = $e->errorInfo ?? null;
        if (is_array($errorInfo) && isset($errorInfo[1]) && (int) $errorInfo[1] === 1062) {
            return true;
        }
        return false;
    }
}
