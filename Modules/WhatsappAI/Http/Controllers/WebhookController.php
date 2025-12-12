<?php

namespace Modules\WhatsappAI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappUser;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Modules\WhatsappAI\Jobs\ProcessConversation;

class WebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook
     * This is a PASSIVE collector - no auto-reply
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook for debugging
            Log::info('WhatsApp AI Webhook received', ['payload' => $request->all()]);

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
                Log::warning('WhatsApp user not found', ['phone_id' => $phoneNumberId]);
                return response()->json(['status' => 'ignored', 'message' => 'WhatsApp user not found'], 404);
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
            $messageType = $message['type'] ?? 'text';
            $content = $this->extractMessageContent($message, $messageType);

            // Store the message
            WhatsappMessage::create([
                'conversation_id' => $conversation->id,
                'whatsapp_message_id' => $message['id'] ?? null,
                'message_type' => $messageType,
                'content' => $content,
                'media_url' => $message[$messageType]['url'] ?? null,
                'raw_payload' => $message,
            ]);

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

            Log::info('Message stored and job scheduled', [
                'conversation_id' => $conversation->id,
                'message_count' => $conversation->message_count,
                'delay_minutes' => $delayMinutes,
            ]);

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
            
            default:
                return "[Unsupported message type: {$type}]";
        }
    }
}

