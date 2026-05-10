<?php

namespace Modules\WhatsappAI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappAI\Jobs\ForwardWebhook;
use App\Models\WhatsappUser;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Modules\WhatsappAI\Jobs\ProcessConversation;
use Modules\WhatsappAI\Jobs\TranscribeAudio;

class WebhookController extends Controller
{
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

            // Extract Meta webhook data
            $entry = $request->input('entry.0.changes.0.value');

            if (!$entry || empty($entry['messages'])) {
                return response()->json(['status' => 'ok', 'message' => 'No messages in payload']);
            }

            $phoneNumberId = $entry['metadata']['phone_number_id'] ?? null;
            $displayPhoneNumber = $entry['metadata']['display_phone_number'] ?? null;
            $wabaId = $request->input('entry.0.id');
            $message = $entry['messages'][0] ?? null;
            $customerPhone = $message['from'] ?? null;

            if (!$phoneNumberId || !$message || !$customerPhone) {
                return response()->json(['status' => 'ignored', 'message' => 'Missing required fields'], 400);
            }

            // Find WhatsApp user by phone_number_id
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

                return response()->json(['status' => 'ignored', 'message' => 'WhatsApp user not found']);
            }

            // Get or create conversation
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

            // Extract message content based on type
            $incomingMessageType = $message['type'] ?? 'text';
            $storedMessageType = $this->normalizeMessageTypeForStorage($incomingMessageType);
            $content = $this->extractMessageContent($message, $incomingMessageType);

            // Extract media URL based on message type
            $mediaUrl = null;
            if (in_array($incomingMessageType, ['image', 'document', 'audio', 'video']) && isset($message[$incomingMessageType]['url'])) {
                $mediaUrl = $message[$incomingMessageType]['url'];
            }

            // Store the message
            $storedMessage = WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'whatsapp_message_id' => $message['id'] ?? null,
                // Ensure we only store values supported by the DB enum.
                // The original WhatsApp type is preserved in raw_payload (and in content for unsupported types).
                'message_type' => $storedMessageType,
                'content' => $content,
                'media_url' => $mediaUrl,
                'raw_payload' => $message,
            ]);

            // Dispatch audio transcription immediately so the transcript is ready
            // before ProcessConversation runs (which is delayed by session_timeout).
            if ($storedMessageType === 'audio') {
                TranscribeAudio::dispatch($storedMessage->id)
                    ->onQueue(config('whatsappai.queue', 'default'));
            }

            // Update conversation
            $conversation->increment('message_count');
            $conversation->update([
                'last_message_at' => now(),
                'status' => 'collecting', // Reset to collecting if it was processed
            ]);

            // Dispatch delayed job to process conversation (5 minutes)
            $delayMinutes = config('whatsappai.session_timeout', 5);
            ProcessConversation::dispatch($conversation->id)
                ->delay(now()->addMinutes($delayMinutes))
                ->onQueue(config('whatsappai.queue', 'default'));

            // Log::info('Message stored and job scheduled', [
            //     'conversation_id' => $conversation->id,
            //     'message_count' => $conversation->message_count,
            //     'delay_minutes' => $delayMinutes,
            // ]);

            return response()->json([
                'status' => 'stored',
                'conversation_id' => $conversation->id,
                'message_count' => $conversation->message_count,
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

