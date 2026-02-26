<?php

namespace App\Http\Controllers\Api\V1\Email;

use App\Domain\Communication\Email\Contracts\EmailGatewayClient;
use App\Domain\Communication\Services\StatusTransitionGuard;
use App\Domain\Communication\Services\WebhookEventJournal;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\EmailMessageLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseApiController
{
    public function __construct(
        private readonly EmailGatewayClient $gatewayClient,
        private readonly ?WebhookEventJournal $webhookEventJournal = null,
        private readonly ?StatusTransitionGuard $statusTransitionGuard = null
    ) {}

    public function delivery(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $secret = (string) config('communication.email.webhook_secret', '');

        if (!$this->gatewayClient->verifyWebhookSignature($rawBody, $request->headers->all(), $secret)) {
            return response()->json(['status' => false, 'code' => 'INVALID_SIGNATURE', 'message' => 'Invalid webhook signature.'], 401);
        }

        $events = $this->gatewayClient->parseDeliveryWebhook($request->all());
        $updated = 0;
        $provider = (string) config('communication.email.provider', 'unknown');

        foreach ($events as $event) {
            $gatewayMessageId = (string) ($event['gateway_message_id'] ?? '');
            if ($gatewayMessageId === '') {
                continue;
            }

            $targetStatus = $this->mapStatus((string) ($event['status'] ?? ''));
            if ($targetStatus === null) {
                continue;
            }

            if ($this->webhookEventJournal && config('communication.reliability.enabled', false)) {
                $eventProvider = (string) ($event['provider'] ?? $provider);
                $journalEvent = $this->webhookEventJournal->journal(
                    'email',
                    $eventProvider,
                    'delivery',
                    null,
                    $gatewayMessageId,
                    $event,
                    true,
                    false,
                    null
                );
                if ($journalEvent === null) {
                    continue;
                }
            }

            $log = $this->resolveLog($event, $gatewayMessageId);
            if (!$log) {
                continue;
            }

            $currentStatus = $log->status ?? 'pending';
            if ($this->statusTransitionGuard && !$this->statusTransitionGuard->canTransition($currentStatus, $targetStatus, 'email')) {
                continue;
            }

            $update = [
                'status' => $targetStatus,
                'provider' => (string) ($event['provider'] ?? $log->provider),
                'error_message' => $targetStatus === 'failed' ? (string) ($event['error_message'] ?? $log->error_message) : null,
            ];

            if ($targetStatus === 'delivered') {
                $update['delivered_at'] = !empty($event['delivered_at'])
                    ? Carbon::parse((string) $event['delivered_at'])
                    : now();
            }

            $log->update($update);
            $updated++;
        }

        return $this->ok(['updated' => $updated]);
    }

    /**
     * Tenant-safe lookup: prefer user_id from payload; fallback by gateway_message_id only (update iff exactly one row).
     */
    private function resolveLog(array $event, string $gatewayMessageId): ?EmailMessageLog
    {
        $userId = isset($event['user_id']) ? (int) $event['user_id'] : null;

        if ($userId !== null && $userId > 0) {
            return EmailMessageLog::query()
                ->where('user_id', $userId)
                ->where('gateway_message_id', $gatewayMessageId)
                ->first();
        }

        $logs = EmailMessageLog::query()
            ->where('gateway_message_id', $gatewayMessageId)
            ->get();

        if ($logs->count() === 1) {
            return $logs->first();
        }

        return null;
    }

    private function mapStatus(string $providerStatus): ?string
    {
        $status = strtolower(trim($providerStatus));

        return match ($status) {
            'delivered' => 'delivered',
            'failed', 'undelivered', 'rejected', 'bounced', 'spam', 'complained' => 'failed',
            default => null,
        };
    }
}
