<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\Services\StatusTransitionGuard;
use App\Domain\Communication\Services\WebhookEventJournal;
use App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WaCampaign;
use App\Models\WaMessageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppWebhookService $webhookService,
        private readonly CommunicationService $communicationService,
        private readonly ?WebhookEventJournal $webhookEventJournal = null,
        private readonly ?StatusTransitionGuard $statusTransitionGuard = null
    ) {}

    /**
     * Meta webhook verification (GET).
     */
    public function verify(Request $request): mixed
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('communication.whatsapp.webhook_verify_token', '');
        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['status' => 'error', 'message' => 'Verification failed.'], 403);
    }

    /**
     * Incoming messages (POST). Signature validation, tenant resolution, persist via CommunicationService.
     */
    public function incoming(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256') ?? $request->header('x-hub-signature-256') ?? '';

        if ($signature !== '' && ! $this->webhookService->verifyMetaSignature($rawBody, $signature)) {
            Log::warning('communication.whatsapp.webhook.incoming.signature_invalid');
            return response()->json(['success' => false, 'code' => 'WEBHOOK_SIGNATURE_INVALID'], 401);
        }

        $payload = $request->all();
        if (empty($payload['entry']) || ! is_array($payload['entry'])) {
            return response()->json(['success' => true], 200);
        }

        $provider = config('communication.whatsapp.provider', 'meta');

        foreach ($payload['entry'] as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];
                $tenant = $this->webhookService->resolveTenantFromPayload([
                    'metadata' => $metadata,
                    'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    'display_phone_number' => $metadata['display_phone_number'] ?? null,
                ], $provider);

                if ($tenant === null) {
                    Log::warning('communication.whatsapp.webhook.incoming.tenant_unresolved', [
                        'reason' => 'tenant_unresolved',
                        'entry_id' => $entry['id'] ?? null,
                    ]);
                    continue;
                }

                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $from = $msg['from'] ?? '';
                    $msgId = $msg['id'] ?? null;
                    $content = '';
                    if (isset($msg['text']['body'])) {
                        $content = (string) $msg['text']['body'];
                    }

                    if ($this->webhookEventJournal && config('communication.reliability.enabled', false)) {
                        $event = $this->webhookEventJournal->journal(
                            'whatsapp',
                            $provider,
                            'incoming',
                            $msgId,
                            $msgId,
                            $msg,
                            true,
                            true,
                            $tenant['user_id']
                        );
                        if ($event === null) {
                            continue;
                        }
                    }

                    $externalParty = $this->normalizeExternalParty('+' . ltrim((string) $from, '+'));
                    $this->communicationService->recordInboundMessage(
                        $tenant['user_id'],
                        $externalParty,
                        $content,
                        'whatsapp',
                        $msgId,
                        ['wa_number_id' => $tenant['wa_number_id']]
                    );
                    Log::info('communication.whatsapp.webhook.incoming.persisted', [
                        'message_id' => $msgId,
                        'user_id' => $tenant['user_id'],
                    ]);
                }
            }
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Status updates (POST). Signature validation, tenant resolution, update messages.status.
     */
    public function status(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256') ?? $request->header('x-hub-signature-256') ?? '';

        if ($signature !== '' && ! $this->webhookService->verifyMetaSignature($rawBody, $signature)) {
            Log::warning('communication.whatsapp.webhook.status.signature_invalid');
            return response()->json(['success' => false, 'code' => 'WEBHOOK_SIGNATURE_INVALID'], 401);
        }

        $payload = $request->all();
        if (empty($payload['entry']) || ! is_array($payload['entry'])) {
            return response()->json(['success' => true], 200);
        }

        $provider = config('communication.whatsapp.provider', 'meta');
        $statusMap = ['sent' => 'sent', 'delivered' => 'delivered', 'read' => 'read', 'failed' => 'failed'];

        foreach ($payload['entry'] as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];
                $tenant = $this->webhookService->resolveTenantFromPayload([
                    'metadata' => $metadata,
                    'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    'display_phone_number' => $metadata['display_phone_number'] ?? null,
                ], $provider);

                if ($tenant === null) {
                    Log::warning('communication.whatsapp.webhook.status.tenant_unresolved', [
                        'reason' => 'tenant_unresolved',
                        'entry_id' => $entry['id'] ?? null,
                    ]);
                    continue;
                }

                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $st) {
                    $providerMsgId = $st['id'] ?? null;
                    $status = $st['status'] ?? null;
                    if ($providerMsgId === null || $status === null) {
                        continue;
                    }
                    $internalStatus = $statusMap[$status] ?? $status;

                    if ($this->webhookEventJournal && config('communication.reliability.enabled', false)) {
                        $event = $this->webhookEventJournal->journal(
                            'whatsapp',
                            $provider,
                            'status',
                            null,
                            $providerMsgId,
                            $st,
                            true,
                            true,
                            $tenant['user_id']
                        );
                        if ($event === null) {
                            continue;
                        }
                    }

                    $message = Message::where('provider_message_id', $providerMsgId)
                        ->where('user_id', $tenant['user_id'])
                        ->first();
                    if ($message && $this->statusTransitionGuard && $this->statusTransitionGuard->canTransition($message->status ?? 'queued', $internalStatus, 'whatsapp')) {
                        $message->update(['status' => $internalStatus]);
                        Log::info('communication.whatsapp.webhook.status.updated', [
                            'provider_message_id' => $providerMsgId,
                            'user_id' => $tenant['user_id'],
                            'status' => $internalStatus,
                        ]);
                    } elseif (! $this->statusTransitionGuard) {
                        Message::where('provider_message_id', $providerMsgId)
                            ->where('user_id', $tenant['user_id'])
                            ->update(['status' => $internalStatus]);
                        Log::info('communication.whatsapp.webhook.status.updated', [
                            'provider_message_id' => $providerMsgId,
                            'user_id' => $tenant['user_id'],
                            'status' => $internalStatus,
                        ]);
                    }

                    $this->applyStatusToWaMessageLog($tenant['user_id'], $providerMsgId, $internalStatus, $st);
                }
            }
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * DS-1 / Fix #3: Update wa_message_logs for campaign records atomically in one transaction.
     * Match by user_id + gateway_message_id. Allowed transitions: pending -> sent,
     * pending|sent -> delivered, pending|sent -> failed. Provider "read" maps to "delivered".
     * Lock log and campaign in same transaction; increment only the target counter for the accepted edge.
     */
    private function applyStatusToWaMessageLog(int $userId, string $gatewayMessageId, string $internalStatus, array $rawStatus): void
    {
        if ($internalStatus === 'read') {
            $internalStatus = 'delivered';
        }
        $allowedFrom = [
            'sent' => ['pending'],
            'delivered' => ['pending', 'sent'],
            'failed' => ['pending', 'sent'],
        ];
        $targetStates = $allowedFrom[$internalStatus] ?? null;
        if ($targetStates === null) {
            return;
        }

        $didUpdate = false;
        $campaignId = null;

        DB::transaction(function () use ($userId, $gatewayMessageId, $internalStatus, $rawStatus, $targetStates, &$didUpdate, &$campaignId): void {
            $log = WaMessageLog::query()
                ->where('user_id', $userId)
                ->where('gateway_message_id', $gatewayMessageId)
                ->lockForUpdate()
                ->first();
            if (! $log) {
                return;
            }

            $current = (string) $log->status;
            if (! in_array($current, $targetStates, true)) {
                return;
            }

            $meta = is_array($log->meta) ? $log->meta : [];
            $meta['last_provider_status'] = $rawStatus;
            $update = [
                'status' => $internalStatus,
                'meta' => $meta,
            ];
            if ($internalStatus === 'sent' && $log->sent_at === null) {
                $update['sent_at'] = now();
            }
            if ($internalStatus === 'delivered' && $log->delivered_at === null) {
                $update['delivered_at'] = now();
            }

            $log->update($update);
            $didUpdate = true;
            $campaignId = $log->campaign_id;

            if ($campaignId !== null) {
                $campaign = WaCampaign::query()->where('id', $campaignId)->lockForUpdate()->first();
                if ($campaign) {
                    if ($internalStatus === 'sent') {
                        $campaign->increment('sent_count');
                    }
                    if ($internalStatus === 'delivered') {
                        $campaign->increment('delivered_count');
                    }
                    if ($internalStatus === 'failed') {
                        $campaign->increment('failed_count');
                    }
                }
            }
        });

        if ($didUpdate) {
            Log::info('communication.whatsapp.webhook.status.wa_message_log_updated', [
                'gateway_message_id' => $gatewayMessageId,
                'user_id' => $userId,
                'campaign_id' => $campaignId,
                'status' => $internalStatus,
            ]);
        }
    }

    /**
     * Provider-specific verification fallback (POST) e.g. for Evolution.
     */
    public function verifyPost(Request $request): JsonResponse
    {
        return response()->json(['success' => true], 200);
    }

    private function normalizeExternalParty(string $value): string
    {
        $value = trim($value);
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
