<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Domain\Communication\Services\StatusTransitionGuard;
use App\Domain\Communication\Services\WebhookEventJournal;
use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\SmsMessageLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseApiController
{
    public function __construct(
        private readonly SmsGatewayClient $gatewayClient,
        private readonly ?WebhookEventJournal $webhookEventJournal = null,
        private readonly ?StatusTransitionGuard $statusTransitionGuard = null
    ) {}

    public function delivery(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $secret = (string) config('communication.sms.webhook_secret', '');

        if (!$this->gatewayClient->verifyWebhookSignature($rawBody, $request->headers->all(), $secret)) {
            return response()->json(['status' => false, 'code' => 'INVALID_SIGNATURE', 'message' => 'Invalid webhook signature.'], 401);
        }

        $events = $this->gatewayClient->parseDeliveryWebhook($request->all());
        $updated = 0;
        $provider = (string) config('communication.sms.provider', 'unknown');

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
                    'sms',
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

            $log = SmsMessageLog::query()
                ->where('gateway_message_id', $gatewayMessageId)
                ->first();

            if (!$log) {
                continue;
            }

            $currentStatus = $log->status ?? 'pending';
            if ($this->statusTransitionGuard && !$this->statusTransitionGuard->canTransition($currentStatus, $targetStatus, 'sms')) {
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

    private function mapStatus(string $providerStatus): ?string
    {
        $status = strtolower(trim($providerStatus));

        return match ($status) {
            'delivered' => 'delivered',
            'failed', 'undelivered', 'rejected' => 'failed',
            default => null,
        };
    }
}

