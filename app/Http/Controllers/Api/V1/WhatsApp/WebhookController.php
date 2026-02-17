<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\Services\StatusTransitionGuard;
use App\Domain\Communication\Services\WebhookEventJournal;
use App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
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
                }
            }
        }

        return response()->json(['success' => true], 200);
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
