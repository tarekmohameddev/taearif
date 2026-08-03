<?php

namespace Modules\WhatsappAI\Http\Controllers;

use App\Domain\Communication\WhatsApp\Bot\HandoffService;
use App\Domain\Communication\WhatsApp\Services\SyncWhatsappAiConversationToCommunicationService;
use App\Models\ShadowBotDraft;
use App\Models\WaConversationAiState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Modules\WhatsappAI\Jobs\ForwardWebhook;
use Modules\WhatsappAI\Jobs\ProcessConversation;
use Modules\WhatsappAI\Jobs\TranscribeAudio;
use App\Models\WhatsappUser;

class WebhookController extends Controller
{
    public function __construct(
        private readonly SyncWhatsappAiConversationToCommunicationService $communicationSyncService,
        private readonly HandoffService $handoffService,
    ) {}
    /**
     * Handle incoming WhatsApp webhook
     * This is a PASSIVE collector - no auto-reply
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $this->mirrorWebhookToForwardUrl($request);

            // Log incoming webhook for debugging (payload + raw body from Meta)
            // Log::info('WhatsApp AI Webhook received', [
            //     'payload' => $request->all(),
            //     'raw_body' => $request->getContent(),
            // ]);

            // Handle GET request for webhook verification
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }

            $entries = $request->input('entry', []);
            if (! is_array($entries) || $entries === []) {
                return response()->json(['status' => 'ok', 'message' => 'No messages in payload']);
            }

            $processed = 0;
            $storedConversationId = null;
            $storedMessageCount = null;

            foreach ($entries as $webhookEntry) {
                $changes = $webhookEntry['changes'] ?? [];
                if (! is_array($changes)) {
                    continue;
                }

                foreach ($changes as $change) {
                    $entry = $change['value'] ?? null;
                    if (! is_array($entry)) {
                        continue;
                    }

                    // Handle inbound messages (customer -> business)
                    if (! empty($entry['messages']) && is_array($entry['messages'])) {
                        foreach ($entry['messages'] as $message) {
                            if (! is_array($message)) {
                                continue;
                            }

                            $result = $this->storeIncomingMessage($entry, $message, $webhookEntry['id'] ?? null);
                            if ($result !== null) {
                                $processed++;
                                $storedConversationId = $result['conversation_id'];
                                $storedMessageCount = $result['message_count'];
                            }
                        }
                    }

                    // Handle outbound echoes (business -> customer from app/linked device)
                    // Both message_echoes and smb_message_echoes use the same structure
                    $echoes = array_merge(
                        $entry['message_echoes'] ?? [],
                        $entry['smb_message_echoes'] ?? []
                    );
                    foreach ($echoes as $echo) {
                        if (! is_array($echo)) {
                            continue;
                        }

                        $result = $this->storeOutboundEcho($entry, $echo, $webhookEntry['id'] ?? null);
                        if ($result !== null) {
                            $processed++;
                            $storedConversationId = $result['conversation_id'];
                            $storedMessageCount = $result['message_count'];
                        }
                    }
                }
            }

            if ($processed === 0) {
                return response()->json(['status' => 'ok', 'message' => 'No messages in payload']);
            }

            // Log::info('Message stored and job scheduled', [
            //     'conversation_id' => $conversation->id,
            //     'message_count' => $conversation->message_count,
            //     'delay_minutes' => $delayMinutes,
            // ]);

            return response()->json([
                'status' => 'stored',
                'conversation_id' => $storedConversationId,
                'message_count' => $storedMessageCount,
                'processed' => $processed,
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp AI Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * @return array{conversation_id: int, message_count: int}|null
     */
    private function storeIncomingMessage(array $entry, array $message, mixed $wabaId): ?array
    {
        $phoneNumberId = $entry['metadata']['phone_number_id'] ?? null;
        $displayPhoneNumber = $entry['metadata']['display_phone_number'] ?? null;
        $customerPhone = $message['from'] ?? null;

        if (!$phoneNumberId || !$customerPhone) {
            Log::warning('WhatsApp AI webhook missing required fields', [
                'phone_id' => $phoneNumberId,
                'display_phone_number' => $displayPhoneNumber,
                'waba_id' => $wabaId,
                'customer_phone' => $customerPhone,
                'message_id' => $message['id'] ?? null,
                'message_type' => $message['type'] ?? null,
            ]);

            return null;
        }

        $whatsappUser = WhatsappUser::where('phone_id', $phoneNumberId)
            ->where('status', 'active')
            ->first();

        if (!$whatsappUser) {
            $knownWhatsappUser = WhatsappUser::where('phone_id', $phoneNumberId)->first();

            Log::warning('WhatsApp user not found', [
                'phone_id' => $phoneNumberId,
                'display_phone_number' => $displayPhoneNumber,
                'waba_id' => $wabaId,
                'customer_phone' => $customerPhone,
                'message_id' => $message['id'] ?? null,
                'message_type' => $message['type'] ?? null,
                'known_whatsapp_user_id' => $knownWhatsappUser?->id,
                'known_user_id' => $knownWhatsappUser?->user_id,
                'known_status' => $knownWhatsappUser?->status,
            ]);

            return null;
        }

        $conversation = WhatsappConversation::firstOrCreate(
            [
                'whatsapp_user_id' => $whatsappUser->id,
                'customer_phone' => $customerPhone,
            ],
            [
                'user_id' => $whatsappUser->user_id,
                'status' => 'collecting',
                'customer_name' => $entry['contacts'][0]['profile']['name'] ?? null,
            ]
        );

        $incomingMessageType = $message['type'] ?? 'text';
        $storedMessageType = $this->normalizeMessageTypeForStorage($incomingMessageType);
        $content = $this->extractMessageContent($message, $incomingMessageType);

        $mediaUrl = null;
        if (in_array($incomingMessageType, ['image', 'document', 'audio', 'video'], true) && isset($message[$incomingMessageType]['url'])) {
            $mediaUrl = $message[$incomingMessageType]['url'];
        }

        $providerMessageId = $message['id'] ?? null;
        $messageWasNew = true;

        if ($providerMessageId !== null && $providerMessageId !== '') {
            $storedMessage = WhatsappMessage::firstOrCreate(
                ['whatsapp_message_id' => (string) $providerMessageId],
                [
                    'conversation_id' => $conversation->id,
                    'direction' => 'inbound',
                    'message_type' => $storedMessageType,
                    'content' => $content,
                    'media_url' => $mediaUrl,
                    'raw_payload' => $message,
                ]
            );
            $messageWasNew = $storedMessage->wasRecentlyCreated;
        } else {
            $storedMessage = WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'whatsapp_message_id' => null,
                'message_type' => $storedMessageType,
                'content' => $content,
                'media_url' => $mediaUrl,
                'raw_payload' => $message,
            ]);
        }

        try {
            $this->communicationSyncService->sync(
                $conversation,
                $storedMessage,
                is_array($entry['metadata'] ?? null) ? $entry['metadata'] : null,
                incrementUnread: $messageWasNew,
            );
        } catch (\Throwable $syncError) {
            Log::error('whatsapp_ai.communication_sync.exception', [
                'error' => $syncError->getMessage(),
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_message_id' => $storedMessage->id,
            ]);
        }

        if ($messageWasNew) {
            if ($storedMessageType === 'audio') {
                TranscribeAudio::dispatch($storedMessage->id)
                    ->onQueue(config('whatsappai.queue', 'default'));
            }

            $conversation->increment('message_count');
            $conversation->update([
                'last_message_at' => now(),
                'status' => 'collecting',
            ]);

            $delayMinutes = config('whatsappai.session_timeout', 5);
            ProcessConversation::dispatch($conversation->id)
                ->delay(now()->addMinutes($delayMinutes))
                ->onQueue(config('whatsappai.queue', 'default'));
        }

        return [
            'conversation_id' => (int) $conversation->id,
            'message_count' => (int) $conversation->message_count,
        ];
    }

    /**
     * Store an outbound echo (message sent from WhatsApp Business App or linked device).
     *
     * @return array{conversation_id: int, message_count: int}|null
     */
    private function storeOutboundEcho(array $entry, array $echo, mixed $wabaId): ?array
    {
        $phoneNumberId = $entry['metadata']['phone_number_id'] ?? null;
        // For echoes, 'from' is the business phone, 'to' is the customer
        $customerPhone = $echo['to'] ?? null;

        if (!$phoneNumberId || !$customerPhone) {
            Log::warning('WhatsApp AI webhook echo missing required fields', [
                'phone_id' => $phoneNumberId,
                'waba_id' => $wabaId,
                'customer_phone' => $customerPhone,
                'message_id' => $echo['id'] ?? null,
                'message_type' => $echo['type'] ?? null,
            ]);

            return null;
        }

        $whatsappUser = WhatsappUser::where('phone_id', $phoneNumberId)
            ->where('status', 'active')
            ->first();

        if (!$whatsappUser) {
            Log::warning('WhatsApp user not found for echo', [
                'phone_id' => $phoneNumberId,
                'waba_id' => $wabaId,
                'customer_phone' => $customerPhone,
                'message_id' => $echo['id'] ?? null,
            ]);

            return null;
        }

        $conversation = WhatsappConversation::firstOrCreate(
            [
                'whatsapp_user_id' => $whatsappUser->id,
                'customer_phone' => $customerPhone,
            ],
            [
                'user_id' => $whatsappUser->user_id,
                'status' => 'collecting',
                'customer_name' => null,
            ]
        );

        $echoMessageType = $echo['type'] ?? 'text';
        $storedMessageType = $this->normalizeMessageTypeForStorage($echoMessageType);
        $content = $this->extractMessageContent($echo, $echoMessageType);

        $mediaUrl = null;
        if (in_array($echoMessageType, ['image', 'document', 'audio', 'video'], true) && isset($echo[$echoMessageType]['url'])) {
            $mediaUrl = $echo[$echoMessageType]['url'];
        }

        $providerMessageId = $echo['id'] ?? null;
        $messageWasNew = true;

        // Check if this message was already stored (e.g., when sent via API)
        if ($providerMessageId !== null && $providerMessageId !== '') {
            $existing = WhatsappMessage::where('whatsapp_message_id', (string) $providerMessageId)->first();
            if ($existing) {
                // Already stored at send time, skip duplicate
                return null;
            }

            $storedMessage = WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'whatsapp_message_id' => (string) $providerMessageId,
                'message_type' => $storedMessageType,
                'content' => $content,
                'media_url' => $mediaUrl,
                'raw_payload' => $echo,
            ]);
        } else {
            // No provider message ID — deduplicate by content hash within a short window
            // to protect against webhook retries creating duplicate records.
            $contentHash = md5((string) $content . $storedMessageType);
            $recentDuplicate = WhatsappMessage::where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->where('message_type', $storedMessageType)
                ->where('content', $content)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recentDuplicate) {
                return null;
            }

            $storedMessage = WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'whatsapp_message_id' => null,
                'message_type' => $storedMessageType,
                'content' => $content,
                'media_url' => $mediaUrl,
                'raw_payload' => $echo,
            ]);
        }

        // Sync to Communication layer as outbound
        $syncedMessage = null;
        try {
            $syncedMessage = $this->communicationSyncService->syncOutbound(
                $conversation,
                $storedMessage,
                is_array($entry['metadata'] ?? null) ? $entry['metadata'] : null,
            );
        } catch (\Throwable $syncError) {
            Log::error('whatsapp_ai.communication_sync_outbound.exception', [
                'error' => $syncError->getMessage(),
                'whatsapp_conversation_id' => $conversation->id,
                'whatsapp_message_id' => $storedMessage->id,
            ]);
        }

        // Agent takeover detection — only when the message is NOT from the bot itself.
        // Bot messages are tagged meta.source = 'ai' at delivery time; echoes from the
        // human agent have source = 'whatsapp_echo'. Skip if already in our system with
        // source = 'ai' to avoid pausing right after the bot speaks.
        if ($syncedMessage !== null) {
            $syncedMeta   = is_array($syncedMessage->meta) ? $syncedMessage->meta : [];
            $messageSource = $syncedMeta['source'] ?? 'whatsapp_echo';

            if ($messageSource !== 'ai') {
                // Human agent replied — pause the bot for this conversation
                $conversationId = $syncedMessage->conversation_id;
                if ($conversationId !== null) {
                    $aiState = WaConversationAiState::where('conversation_id', $conversationId)->first();
                    if ($aiState !== null) {
                        $this->handoffService->handleAgentReply($aiState);

                        // Pair with any pending shadow draft for the same conversation
                        $this->pairShadowDraft((int) $conversationId, (string) ($content ?? ''));
                    }
                }
            }
        }

        if ($messageWasNew) {
            $conversation->increment('message_count');
            $conversation->update([
                'last_message_at' => now(),
            ]);
        }

        return [
            'conversation_id' => (int) $conversation->id,
            'message_count' => (int) $conversation->message_count,
        ];
    }

    /**
     * If a ShadowBotDraft is pending for this conversation, record the agent's actual
     * reply in the `agent_reply` field so it can feed into the golden corpus later.
     */
    private function pairShadowDraft(int $conversationId, string $agentReplyText): void
    {
        try {
            $draft = ShadowBotDraft::where('conversation_id', $conversationId)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($draft !== null) {
                $draft->update([
                    'agent_reply' => $agentReplyText,
                    'status'      => 'agent_replied',
                    'acted_at'    => now(),
                ]);

                Log::info('whatsapp_ai.shadow_draft.agent_paired', [
                    'draft_id'        => $draft->id,
                    'conversation_id' => $conversationId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('whatsapp_ai.shadow_draft.pair_failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function mirrorWebhookToForwardUrl(Request $request): void
    {
        $forwardUrl = trim((string) config('whatsappai.webhook_forward_url', ''));
        if ($forwardUrl === '') {
            return;
        }

        // Loop prevention (in case the test env forwards back, or multiple hops exist)
        if ($request->headers->has('X-Taearif-Forwarded')) {
            return;
        }

        $headersToForward = [
            'Content-Type',
            'X-Hub-Signature',
            'X-Hub-Signature-256',
        ];

        $headers = [];
        foreach ($headersToForward as $h) {
            $v = $request->headers->get($h);
            if ($v !== null && $v !== '') {
                $headers[$h] = $v;
            }
        }

        ForwardWebhook::dispatch(
            url: $forwardUrl,
            method: $request->method(),
            headers: $headers,
            query: $request->query(),
            body: $request->getContent(),
            timeoutSeconds: (int) config('whatsappai.webhook_forward_timeout', 5),
        )->onQueue(config('whatsappai.queue', 'default'));
    }

    /**
     * Verify webhook (Meta requirement)
     */
    private function verifyWebhook(Request $request): JsonResponse
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('whatsappai.verify_token', env('WHATSAPP_VERIFY_TOKEN'));

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response()->json((int) $challenge);
        }

        return response()->json(['status' => 'error', 'message' => 'Verification failed'], 403);
    }

    /**
     * Extract message content based on type
     */
    private function extractMessageContent(array $message, string $type): ?string
    {
        switch ($type) {
            case 'text':
                return $message['text']['body'] ?? null;

            case 'image':
                return $message['image']['caption'] ?? '[Image]';

            case 'document':
                return $message['document']['filename'] ?? '[Document]';

            case 'audio':
                return '[Audio message]';

            case 'video':
                return $message['video']['caption'] ?? '[Video]';

            case 'location':
                $lat = $message['location']['latitude'] ?? '';
                $lng = $message['location']['longitude'] ?? '';
                return "[Location: {$lat}, {$lng}]";

            case 'reaction':
                $emoji = $message['reaction']['emoji'] ?? '';
                $messageId = $message['reaction']['message_id'] ?? '';
                return $emoji ? "[Reaction: {$emoji}]" : '[Reaction]';

            case 'edit':
                // Edit messages contain a nested message structure
                // Extract content from edit.message based on its type
                if (isset($message['edit']['message'])) {
                    $nestedMessage = $message['edit']['message'];
                    $nestedType = $nestedMessage['type'] ?? 'text';
                    return $this->extractMessageContent($nestedMessage, $nestedType);
                }
                return '[Edited message]';

            default:
                return "[Unsupported message type: {$type}]";
        }
    }

    /**
     * Normalize incoming WhatsApp types to a DB-safe message_type.
     *
     * whatsapp_messages.message_type is an ENUM (see module migrations), so storing
     * unknown values like "sticker" or "revoke" would fail inserts.
     */
    private function normalizeMessageTypeForStorage(string $incomingType): string
    {
        $allowed = [
            'text',
            'image',
            'document',
            'audio',
            'video',
            'location',
            'reaction',
            'edit',
            'contacts',
        ];

        return in_array($incomingType, $allowed, true) ? $incomingType : 'text';
    }
}

