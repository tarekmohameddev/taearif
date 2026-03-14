<?php

namespace App\Http\Controllers\Api\marketing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\Api\marketing\MarketingChannelMessage;
use App\Http\Controllers\Api\marketing\CreditController;
use App\Models\Api\marketing\UserCredit;
use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\Marketing\StoreMarketingChannelRequest;
use App\Http\Requests\Api\Marketing\UpdateMarketingChannelRequest;
use App\Http\Requests\Api\Marketing\UpdateChannelStatusRequest;
use App\Http\Requests\Api\Marketing\SendMessageRequest;
use App\Http\Requests\Api\Marketing\SendWhatsAppToCustomerRequest;
use App\Http\Requests\Api\Marketing\GetChannelStatsRequest;
use App\Http\Requests\Api\Marketing\GetMessagesRequest;
use App\Http\Requests\Api\Marketing\UpdateMarketingSettingsRequest;
use App\Http\Requests\Api\Marketing\UpdateSystemIntegrationSettingsRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MarketingChannelController extends BaseApiController
{
    protected CommunicationService $communicationService;
    protected WhatsAppWebhookService $whatsAppWebhookService;

    public function __construct(
        CommunicationService $communicationService,
        WhatsAppWebhookService $whatsAppWebhookService
    )
    {
        $this->communicationService = $communicationService;
        $this->whatsAppWebhookService = $whatsAppWebhookService;
    }

    /**
     * Get all marketing channels for the authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            $channels = MarketingChannel::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->ok($channels, 'Marketing channels retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve marketing channels: ' . $e->getMessage());
        }
    }

    /**
     * Create a new marketing channel
     */
    public function store(StoreMarketingChannelRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $channel = MarketingChannel::create([
                'user_id' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'number' => $validated['number'],
                'business_id' => $validated['business_id'] ?? null,
                'phone_id' => $validated['phone_id'] ?? null,
                'access_token' => $validated['access_token'] ?? null,
                'is_verified' => false,
                'is_connected' => false,
                'sent_messages_count' => 0,
                'received_messages_count' => 0,
                'additional_settings' => $validated['additional_settings'] ?? [],
            ]);

            return $this->ok($channel, 'Marketing channel created successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Failed to create marketing channel: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific marketing channel
     */
    public function show($id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            return $this->ok($channel, 'Marketing channel retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve marketing channel: ' . $e->getMessage());
        }
    }

    /**
     * Update a marketing channel
     */
    public function update(UpdateMarketingChannelRequest $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $channel->update($request->validated());

            return $this->ok($channel, 'Marketing channel updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to update marketing channel: ' . $e->getMessage());
        }
    }

    /**
     * Update channel connection status
     */
    public function updateStatus(UpdateChannelStatusRequest $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $validated = $request->validated();

            $channel->update([
                'is_connected' => $validated['is_connected'],
                'is_verified' => $validated['is_verified'] ?? $channel->is_verified,
            ]);

            return $this->ok($channel, 'Channel status updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to update channel status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a marketing channel
     */
    public function destroy($id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $channel->delete();

            return $this->ok(null, 'Marketing channel deleted successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to delete marketing channel: ' . $e->getMessage());
        }
    }

    /**
     * Get channel statistics
     */
    public function statistics($id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $statistics = [
                'channel' => $channel,
                'total_messages' => $channel->sent_messages_count + $channel->received_messages_count,
                'sent_messages' => $channel->sent_messages_count,
                'received_messages' => $channel->received_messages_count,
                'created_at' => $channel->created_at,
                'updated_at' => $channel->updated_at,
            ];

            return $this->ok($statistics, 'Channel statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve channel statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get channel statistics with date filters
     */
    public function stats(GetChannelStatsRequest $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $validated = $request->validated();
            $fromDate = $validated['from'] ?? null;
            $toDate = $validated['to'] ?? null;

            // For now, we'll return the current message counts
            // In a real implementation, you would query message logs with date filters
            $statistics = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
                'total_messages' => $channel->sent_messages_count + $channel->received_messages_count,
                'sent_messages' => $channel->sent_messages_count,
                'received_messages' => $channel->received_messages_count,
                'connection_status' => [
                    'is_connected' => $channel->is_connected,
                    'is_verified' => $channel->is_verified,
                ],
                'generated_at' => now()->toISOString(),
            ];

            // Add date filter info if provided
            if ($fromDate || $toDate) {
                $statistics['date_filter_applied'] = true;
                $statistics['note'] = 'Date filtering is available. In production, this would filter actual message logs.';
            }

            return $this->ok($statistics, 'Channel statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve channel statistics: ' . $e->getMessage());
        }
    }

    /**
     * Sync channel verification status
     */
    public function syncVerified($id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            // In a real implementation, you would:
            // 1. Call the external API (WhatsApp, Facebook, etc.) to check verification status
            // 2. Update the local database with the current status
            // 3. Return the updated status

            // For now, we'll simulate the sync process
            $previousStatus = [
                'is_verified' => $channel->is_verified,
                'is_connected' => $channel->is_connected,
            ];

            // Simulate API call to external service
            // In production, you would make actual API calls here
            $externalStatus = $this->checkExternalVerificationStatus($channel);

            // Update the channel with the external status
            $channel->update([
                'is_verified' => $externalStatus['is_verified'],
                'is_connected' => $externalStatus['is_connected'],
            ]);

            $response = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'previous_status' => $previousStatus,
                'current_status' => [
                    'is_verified' => $channel->is_verified,
                    'is_connected' => $channel->is_connected,
                ],
                'sync_performed' => true,
                'synced_at' => now()->toISOString(),
            ];

            return $this->ok($response, 'Channel verification status synced successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to sync channel verification status: ' . $e->getMessage());
        }
    }

    /**
     * Simulate checking external verification status
     * In production, this would make actual API calls to WhatsApp/Facebook/etc.
     */
    private function checkExternalVerificationStatus($channel)
    {
        // This is a simulation - in production you would:
        // 1. Make API call to WhatsApp Business API
        // 2. Make API call to Facebook Graph API
        // 3. Make API call to Telegram Bot API
        // etc.

        switch ($channel->type) {
            case 'whatsapp':
                // Simulate WhatsApp Business API call
                return [
                    'is_verified' => true, // Would come from WhatsApp API
                    'is_connected' => true, // Would come from WhatsApp API
                ];

            case 'facebook':
                // Simulate Facebook Graph API call
                return [
                    'is_verified' => true, // Would come from Facebook API
                    'is_connected' => true, // Would come from Facebook API
                ];

            case 'telegram':
                // Simulate Telegram Bot API call
                return [
                    'is_verified' => true, // Would come from Telegram API
                    'is_connected' => true, // Would come from Telegram API
                ];

            default:
                return [
                    'is_verified' => false,
                    'is_connected' => false,
                ];
        }
    }

    /**
     * Send message through marketing channel
     */
    public function sendMessage(SendMessageRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            if (!$channel->is_connected || !$channel->is_verified) {
                return $this->fail('Channel is not connected or verified', 400);
            }

            // Calculate credits needed
            $creditsNeeded = UserCredit::getCostForMessageType($channel->type);

            // Check and deduct credits
            $creditResult = CreditController::useCredits(
                Auth::id(),
                $creditsNeeded,
                "Message sent via {$channel->name} ({$channel->type})",
                [
                'channel_id' => $channel->id,
                'channel_type' => $channel->type,
                'recipient' => $validated['to'],
                'message_type' => $validated['message_type'] ?? 'text',
                ]
            );

            if (!$creditResult['success']) {
                return $this->fail($creditResult['error'], 400, [
                    'credits_available' => $creditResult['available_credits'] ?? 0,
                    'credits_required' => $creditsNeeded,
                ]);
            }

            // Send message through external API (simulated)
            $messageResult = $this->sendMessageToExternalService($channel, $request);

            if ($messageResult['success']) {
                // Update sent message count
                $channel->increment('sent_messages_count');

                return $this->ok([
                    'message_id' => $messageResult['message_id'],
                    'credits_used' => $creditsNeeded,
                    'remaining_credits' => $creditResult['remaining_credits'],
                    'channel_name' => $channel->name,
                    'sent_to' => $request->to,
                    'status' => 'sent',
                ], 'Message sent successfully');
            } else {
                // Refund credits if message sending failed
                $this->refundCredits(Auth::id(), $creditsNeeded, "Failed to send message via {$channel->name}");

                return $this->fail('Failed to send message: ' . $messageResult['error'], 500);
            }

        } catch (\Exception $e) {
            return $this->fail('Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Send message to external service (WhatsApp, SMS, etc.)
     */
    private function sendMessageToExternalService($channel, Request $request)
    {
        try {
            // This is a simulation - in production, you would integrate with actual APIs
            // like WhatsApp Business API, SMS gateways, etc.

            switch ($channel->type) {
                case 'whatsapp':
                    return $this->sendWhatsAppMessage($channel, $request);
                case 'sms':
                    return $this->sendSMSMessage($channel, $request);
                case 'facebook':
                    return $this->sendFacebookMessage($channel, $request);
                case 'telegram':
                    return $this->sendTelegramMessage($channel, $request);
                case 'instagram':
                    return $this->sendInstagramMessage($channel, $request);
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported channel type',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp message (simulated)
     */
    private function sendWhatsAppMessage($channel, Request $request)
    {
        // In production, integrate with WhatsApp Business API
        return [
            'success' => true,
            'message_id' => 'WHATSAPP_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Send SMS message (simulated)
     */
    private function sendSMSMessage($channel, Request $request)
    {
        // In production, integrate with SMS gateway
        return [
            'success' => true,
            'message_id' => 'SMS_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Send Facebook message (simulated)
     */
    private function sendFacebookMessage($channel, Request $request)
    {
        // In production, integrate with Facebook Messenger API
        return [
            'success' => true,
            'message_id' => 'FB_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Send Telegram message (simulated)
     */
    private function sendTelegramMessage($channel, Request $request)
    {
        // In production, integrate with Telegram Bot API
        return [
            'success' => true,
            'message_id' => 'TG_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Send Instagram message (simulated)
     */
    private function sendInstagramMessage($channel, Request $request)
    {
        // In production, integrate with Instagram API
        return [
            'success' => true,
            'message_id' => 'IG_' . time() . '_' . rand(1000, 9999),
        ];
    }

    /**
     * Refund credits when message sending fails
     */
    private function refundCredits($userId, $credits, $description)
    {
        try {
            $userCredit = UserCredit::getOrCreateForUser($userId);
            $userCredit->addCredits($credits, null, $description);

            // Create refund transaction
            \App\Models\Api\marketing\CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'refund',
                'credits_amount' => $credits,
                'status' => 'completed',
                'reference_number' => \App\Models\Api\marketing\CreditTransaction::generateReferenceNumber(),
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refund credits: ' . $e->getMessage());
        }
    }

    /**
     * Get available channel types
     */
    public function getChannelTypes(): JsonResponse
    {
        try {
            $types = MarketingChannel::getChannelTypes();
            return $this->ok($types, 'Channel types retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve channel types: ' . $e->getMessage());
        }
    }


    /**
     * Handle WhatsApp webhook
     */
    public function whatsappWebhook(Request $request): JsonResponse
    {
        try {
            // Log the incoming webhook for debugging
            Log::info('WhatsApp Webhook Received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'timestamp' => now()->toISOString()
            ]);

            // Verify webhook signature (in production, you should verify the signature)
            $signature = $request->header('X-Hub-Signature-256');
            $body = $request->getContent();

            // In production, verify the signature with your webhook secret
            // $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, config('whatsapp.webhook_secret'));
            // if (!hash_equals($expectedSignature, $signature)) {
            //     return $this->fail('Invalid signature', 401);
            // }

            $data = $request->all();

            // Handle different types of WhatsApp webhook events
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $this->processWhatsAppChange($change);
                        }
                    }
                }
            }

            // WhatsApp expects a 200 response to acknowledge receipt
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->fail('Webhook processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Process WhatsApp webhook changes
     */
    private function processWhatsAppChange($change)
    {
        try {
            $value = $change['value'] ?? [];
            $field = $change['field'] ?? '';

            switch ($field) {
                case 'messages':
                    $this->processWhatsAppMessages($value);
                    break;
                case 'message_deliveries':
                    $this->processWhatsAppMessageDeliveries($value);
                    break;
                case 'message_reads':
                    $this->processWhatsAppMessageReads($value);
                    break;
                case 'message_reactions':
                    $this->processWhatsAppMessageReactions($value);
                    break;
                default:
                    Log::info('Unknown WhatsApp webhook field', ['field' => $field]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp change', [
                'error' => $e->getMessage(),
                'change' => $change
            ]);
        }
    }

    /**
     * Process incoming WhatsApp messages
     */
    private function processWhatsAppMessages($value)
    {
        $messages = $value['messages'] ?? [];

        foreach ($messages as $message) {
            $this->processIncomingWhatsAppMessage($message, $value);
        }
    }

    /**
     * Process individual incoming WhatsApp message
     */
    private function processIncomingWhatsAppMessage($message, $context)
    {
        try {
            $phoneNumberId = $context['metadata']['phone_number_id'] ?? null;
            $businessAccountId = $context['metadata']['business_account_id'] ?? null;

            // Find the marketing channel by phone_number_id
            $channel = MarketingChannel::where('phone_id', $phoneNumberId)
                ->where('type', 'whatsapp')
                ->first();

            if (!$channel) {
                Log::warning('WhatsApp channel not found', ['phone_number_id' => $phoneNumberId]);
                return;
            }

            // Update received message count
            $channel->increment('received_messages_count');

            // Process different message types
            $messageType = $message['type'] ?? 'unknown';

            switch ($messageType) {
                case 'text':
                    $this->processTextMessage($message, $channel, $context);
                    break;
                case 'image':
                case 'document':
                case 'audio':
                case 'video':
                    $this->processMediaMessage($message, $channel);
                    break;
                case 'button':
                    $this->processButtonMessage($message, $channel);
                    break;
                case 'interactive':
                    $this->processInteractiveMessage($message, $channel);
                    break;
                default:
                    Log::info('Unhandled WhatsApp message type', [
                        'type' => $messageType,
                        'message_id' => $message['id'] ?? null
                    ]);
            }

            Log::info('WhatsApp message processed', [
                'channel_id' => $channel->id,
                'message_type' => $messageType,
                'message_id' => $message['id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
        }
    }

    /**
     * Process text messages
     */
    private function processTextMessage($message, $channel, array $context = [])
    {
        $text = $message['text']['body'] ?? '';
        $from = $message['from'] ?? '';

        $resolvedTenant = $this->whatsAppWebhookService->resolveTenantFromPayload([
            'metadata' => $context['metadata'] ?? [],
            'phone_number_id' => $context['metadata']['phone_number_id'] ?? null,
            'display_phone_number' => $context['metadata']['display_phone_number'] ?? null,
        ], 'meta');

        $resolvedUserId = $resolvedTenant['user_id'] ?? null;
        $resolvedWaNumberId = $resolvedTenant['wa_number_id'] ?? null;

        $owner = User::find($resolvedUserId !== null ? (int) $resolvedUserId : (int) $channel->user_id);
        $tenantOwnerId = $owner && method_exists($owner, 'tenantOwnerId') ? $owner->tenantOwnerId() : null;
        if ($tenantOwnerId !== null && $text !== '') {
            try {
                $this->communicationService->recordInboundMessage(
                    userId: (int) $tenantOwnerId,
                    externalPartyIdentifier: (string) $from,
                    content: $text,
                    channel: 'whatsapp',
                    providerMessageId: $message['id'] ?? null,
                    meta: array_filter([
                        'source' => 'marketing_webhook',
                        'channel_id' => $channel->id,
                        'wa_number_id' => $resolvedWaNumberId !== null ? (int) $resolvedWaNumberId : null,
                    ], static fn ($value) => $value !== null)
                );
            } catch (\Throwable $e) {
                Log::warning('Marketing webhook: recordInboundMessage failed', ['message' => $e->getMessage()]);
            }
        }

        Log::info('Text message received', [
            'channel_id' => $channel->id,
            'from' => $from,
            'text' => $text
        ]);
    }

    /**
     * Process media messages
     */
    private function processMediaMessage($message, $channel)
    {
        $mediaType = $message['type'];
        $mediaId = $message[$mediaType]['id'] ?? null;

        Log::info('Media message received', [
            'channel_id' => $channel->id,
            'media_type' => $mediaType,
            'media_id' => $mediaId
        ]);
    }

    /**
     * Process button messages
     */
    private function processButtonMessage($message, $channel)
    {
        $buttonText = $message['button']['text'] ?? '';
        $buttonPayload = $message['button']['payload'] ?? '';

        Log::info('Button message received', [
            'channel_id' => $channel->id,
            'button_text' => $buttonText,
            'button_payload' => $buttonPayload
        ]);
    }

    /**
     * Process interactive messages
     */
    private function processInteractiveMessage($message, $channel)
    {
        $interactiveType = $message['interactive']['type'] ?? '';

        Log::info('Interactive message received', [
            'channel_id' => $channel->id,
            'interactive_type' => $interactiveType
        ]);
    }

    /**
     * Process message delivery status
     */
    private function processWhatsAppMessageDeliveries($value)
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $deliveryStatus = $status['status'] ?? 'unknown';
            $timestamp = $status['timestamp'] ?? null;

            if (!$messageId) {
                continue;
            }

            $message = MarketingChannelMessage::where('provider_message_id', $messageId)->first();

            if ($message) {
                if ($deliveryStatus === 'delivered') {
                    $message->update([
                        'status' => 'delivered',
                        'delivered_at' => $timestamp ? Carbon::createFromTimestamp($timestamp) : now(),
                    ]);
                } elseif ($deliveryStatus === 'failed') {
                    $message->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'error_code' => $status['errors'][0]['code'] ?? null,
                        'error_message' => $status['errors'][0]['title'] ?? 'Delivery failed',
                    ]);
                }

                Log::info('Message delivery status updated', [
                    'message_id' => $messageId,
                    'status' => $deliveryStatus,
                    'record_id' => $message->id,
                ]);
            } else {
                Log::warning('Message not found for delivery update', [
                    'message_id' => $messageId,
                ]);
            }
        }
    }

    /**
     * Process message read status
     */
    private function processWhatsAppMessageReads($value)
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $readStatus = $status['status'] ?? 'unknown';
            $timestamp = $status['timestamp'] ?? null;

            if (!$messageId || $readStatus !== 'read') {
                continue;
            }

            $message = MarketingChannelMessage::where('provider_message_id', $messageId)->first();

            if ($message) {
                $message->update([
                    'status' => 'read',
                    'read_at' => $timestamp ? Carbon::createFromTimestamp($timestamp) : now(),
                ]);

                Log::info('Message read status updated', [
                    'message_id' => $messageId,
                    'record_id' => $message->id,
                ]);
            }
        }
    }

    /**
     * Process message reactions
     */
    private function processWhatsAppMessageReactions($value)
    {
        $messages = $value['messages'] ?? [];

        foreach ($messages as $message) {
            $reaction = $message['reaction'] ?? null;

            if ($reaction) {
                Log::info('Message reaction received', [
                    'message_id' => $message['id'] ?? null,
                    'emoji' => $reaction['emoji'] ?? null
                ]);
            }
        }
    }

    /**
     * Get marketing settings for a specific channel
     */
    public function getMarketingSettings($id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $settings = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'system_integrations' => $channel->getSystemIntegrationSettings(),
                'marketing_settings' => $channel->additional_settings ?? [],
                'updated_at' => $channel->updated_at,
            ];

            return $this->ok($settings, 'Marketing settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve marketing settings: ' . $e->getMessage());
        }
    }

    /**
     * Get message history for a channel or customer
     */
    public function getMessages(GetMessagesRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = MarketingChannelMessage::where('user_id', $userId)
                ->with(['channel:id,name,type', 'customer:id,name,phone_number']);

            if ($request->filled('channel_id')) {
                $query->where('channel_id', $request->channel_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            $messages = $query->orderBy('created_at', 'desc')
                ->paginate((int) ($request->per_page ?? 50));

            return $this->ok($messages, 'Messages retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve messages: ' . $e->getMessage());
        }
    }

    /**
     * Get message statistics
     */
    public function getMessageStats(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = MarketingChannelMessage::where('user_id', $userId);

            if ($request->filled('channel_id')) {
                $query->where('channel_id', $request->channel_id);
            }

            $baseQuery = clone $query;
            $stats = [
                'total' => $baseQuery->count(),
                'sent' => (clone $query)->where('status', 'sent')->count(),
                'delivered' => (clone $query)->where('status', 'delivered')->count(),
                'read' => (clone $query)->where('status', 'read')->count(),
                'failed' => (clone $query)->where('status', 'failed')->count(),
                'delivery_rate' => 0,
                'read_rate' => 0,
            ];

            if ($stats['total'] > 0) {
                $stats['delivery_rate'] = round(($stats['delivered'] + $stats['read']) / $stats['total'] * 100, 2);
                $stats['read_rate'] = round($stats['read'] / $stats['total'] * 100, 2);
            }

            return $this->ok($stats, 'Message statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }

    /**
     * Update marketing settings for a specific channel
     */
    public function updateMarketingSettings(UpdateMarketingSettingsRequest $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $validated = $request->validated();

            $updateData = [];

            // Update system integration settings
            if ($request->has('crm_integration_enabled') ||
                $request->has('appointment_system_integration_enabled') ||
                $request->has('customers_page_integration_enabled') ||
                $request->has('rental_page_integration_enabled') ||
                $request->has('integration_settings')) {

                $systemSettings = [];
                if ($request->has('crm_integration_enabled')) {
                    $systemSettings['crm_integration_enabled'] = $request->crm_integration_enabled;
                }
                if ($request->has('appointment_system_integration_enabled')) {
                    $systemSettings['appointment_system_integration_enabled'] = $request->appointment_system_integration_enabled;
                }
                if ($request->has('customers_page_integration_enabled')) {
                    $systemSettings['customers_page_integration_enabled'] = $request->customers_page_integration_enabled;
                }
                if ($request->has('rental_page_integration_enabled')) {
                    $systemSettings['rental_page_integration_enabled'] = $request->rental_page_integration_enabled;
                }
                if ($request->has('integration_settings')) {
                    $systemSettings['integration_settings'] = $request->integration_settings;
                }

                $channel->updateSystemIntegrationSettings($systemSettings);
            }

            // Update marketing settings
            if ($request->has('marketing_settings')) {
                $updateData['additional_settings'] = $request->marketing_settings;
            }

            if (!empty($updateData)) {
                $channel->update($updateData);
            }

            $response = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'system_integrations' => $channel->getSystemIntegrationSettings(),
                'marketing_settings' => $channel->additional_settings ?? [],
                'updated_at' => $channel->updated_at,
            ];

            return $this->ok($response, 'Marketing settings updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to update marketing settings: ' . $e->getMessage());
        }
    }

    /**
     * Get all marketing settings for the authenticated user
     */
    public function getAllMarketingSettings(): JsonResponse
    {
        try {
            $channels = MarketingChannel::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            $settings = $channels->map(function ($channel) {
                return [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'channel_type' => $channel->type,
                    'is_connected' => $channel->is_connected,
                    'is_verified' => $channel->is_verified,
                    'system_integrations' => $channel->getSystemIntegrationSettings(),
                    'marketing_settings' => $channel->additional_settings ?? [],
                    'updated_at' => $channel->updated_at,
                ];
            });

            return $this->ok($settings, 'All marketing settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve marketing settings: ' . $e->getMessage());
        }
    }

    /**
     * Update system integration settings for a specific channel
     */
    public function updateSystemIntegrationSettings(UpdateSystemIntegrationSettingsRequest $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $channel->updateSystemIntegrationSettings($request->validated());

            $settings = [
                'crm_integration_enabled' => $request->crm_integration_enabled,
                'appointment_system_integration_enabled' => $request->appointment_system_integration_enabled,
                'customers_page_integration_enabled' => $request->customers_page_integration_enabled,
                'rental_page_integration_enabled' => $request->rental_page_integration_enabled,
                'integration_settings' => $request->integration_settings ?? [],
            ];

            $channel->updateSystemIntegrationSettings($settings);

            $response = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'system_integrations' => $channel->getSystemIntegrationSettings(),
                'updated_at' => $channel->updated_at,
            ];

            return $this->ok($response, 'System integration settings updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to update system integration settings: ' . $e->getMessage());
        }
    }


    public function getUsage(): JsonResponse
    {
        try {
            $usage = CreditController::getUsage(Auth::id());

            // Format the usage data for each channel
            $formattedUsage = $usage->map(function ($usage) {
                return [
                    'channel_id' => $usage->channel_id,
                    'channel_name' => $usage->channel_name,
                    'channel_type' => $usage->channel_type,
                    'credits_used' => $usage->credits_used,
                    'messages_sent' => $usage->messages_sent,
                    'messages_received' => $usage->messages_received,
                    'cost_per_message_credits' => $usage->cost_per_message_credits,
                    'cost_per_message_currency' => $usage->cost_per_message_currency,
                    'total_cost_credits' => $usage->total_cost_credits,
                    'total_cost_currency' => $usage->total_cost_currency,
                ];
            });

            // Return all channels usage as an object/array
            return $this->ok($formattedUsage->all(), 'Usage retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve usage: ' . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp message to CRM customer
     */
    public function sendWhatsAppToCustomer(SendWhatsAppToCustomerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $userId = Auth::id();
            $customerId = $validated['customer_id'];

            // Get customer and verify ownership
            $customer = \App\Models\ApiCustomer::where('user_id', $userId)
                ->where('id', $customerId)
                ->first();

            if (!$customer) {
                return $this->fail('Customer not found or does not belong to you', 404);
            }

            // Check if customer has phone number
            if (empty($customer->phone_number)) {
                return $this->fail('Customer does not have a phone number', 400, [
                    'error_code' => 'NO_PHONE_NUMBER',
                    'customer_name' => $customer->name,
                ]);
            }

            // Get or find WhatsApp marketing channel
            $channel = null;
            if ($request->has('channel_id')) {
                $channel = MarketingChannel::where('user_id', $userId)
                    ->where('id', $request->channel_id)
                    ->where('type', 'whatsapp')
                    ->where('is_connected', true)
                    ->where('is_verified', true)
                    ->first();
            } else {
                // Auto-find user's WhatsApp channel
                $channel = MarketingChannel::where('user_id', $userId)
                    ->where('type', 'whatsapp')
                    ->where('is_connected', true)
                    ->where('is_verified', true)
                    ->first();
            }

            if (!$channel) {
                return $this->fail('No active WhatsApp channel found. Please configure and verify your WhatsApp marketing channel first.', 400, [
                    'error_code' => 'NO_WHATSAPP_CHANNEL',
                ]);
            }

            // Format phone number (add country code if missing)
            $formattedPhone = $this->formatPhoneNumber($customer->phone_number);

            // Calculate credits needed
            $creditsNeeded = UserCredit::getCostForMessageType('whatsapp');

            // Check and deduct credits
            $creditResult = CreditController::useCredits(
                $userId,
                $creditsNeeded,
                "WhatsApp message sent to customer: {$customer->name}",
                [
                    'channel_id' => $channel->id,
                    'channel_type' => 'whatsapp',
                    'recipient' => $formattedPhone,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'message_type' => 'text',
                ]
            );

            if (!$creditResult['success']) {
                return $this->fail($creditResult['error'], 400, [
                    'credits_available' => $creditResult['available_credits'] ?? 0,
                    'credits_required' => $creditsNeeded,
                ]);
            }

            // Send message via Meta WhatsApp Business API
            $messageResult = $this->sendWhatsAppViaMeta($channel, $formattedPhone, $request->message);

            if ($messageResult['success']) {
                // Create message record for delivery/read tracking
                $messageRecord = MarketingChannelMessage::create([
                    'user_id' => $userId,
                    'channel_id' => $channel->id,
                    'customer_id' => $customer->id,
                    'recipient_phone' => $formattedPhone,
                    'recipient_name' => $customer->name,
                    'message_content' => $request->message,
                    'message_type' => 'text',
                    'status' => 'sent',
                    'provider_message_id' => $messageResult['message_id'],
                    'sent_at' => now(),
                    'credits_used' => $creditsNeeded,
                    'meta' => [
                        'meta_response' => $messageResult['meta_response'] ?? null,
                    ],
                ]);

                // Update sent message count
                $channel->increment('sent_messages_count');

                // Log the message sent to customer
                Log::info('WhatsApp message sent to CRM customer', [
                    'user_id' => $userId,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'phone' => $formattedPhone,
                    'channel_id' => $channel->id,
                    'message_id' => $messageResult['message_id'],
                    'credits_used' => $creditsNeeded,
                ]);

                return $this->ok([
                    'message_id' => $messageResult['message_id'],
                    'record_id' => $messageRecord->id,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone_number' => $customer->phone_number,
                        'formatted_phone' => $formattedPhone,
                    ],
                    'channel' => [
                        'id' => $channel->id,
                        'name' => $channel->name,
                    ],
                    'credits_used' => $creditsNeeded,
                    'remaining_credits' => $creditResult['remaining_credits'],
                    'status' => 'sent',
                ], 'WhatsApp message sent to customer successfully');
            } else {
                // Refund credits if message sending failed
                $this->refundCredits($userId, $creditsNeeded, "Failed to send WhatsApp message to customer: {$customer->name}");

                return $this->fail('Failed to send WhatsApp message: ' . $messageResult['error'], 500, [
                    'customer_name' => $customer->name,
                    'phone' => $formattedPhone,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message to customer', [
                'user_id' => Auth::id(),
                'customer_id' => $request->customer_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->fail('Failed to send WhatsApp message: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number - keep as provided without adding country code
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters and return as is
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        return $phone;
    }

    /**
     * Send WhatsApp message via Meta Business API
     */
    private function sendWhatsAppViaMeta($channel, $phoneNumber, $message)
    {
        try {
            // Get Meta API credentials from channel or basic settings
            $accessToken = $channel->access_token;
            $phoneNumberId = $channel->phone_id;

            // If channel doesn't have credentials, try to get from basic settings
            if (!$accessToken || !$phoneNumberId) {
                $basicSettings = \App\Models\BasicSetting::first();
                if ($basicSettings && $basicSettings->whatsapp_service === 'meta_cloud') {
                    $accessToken = $basicSettings->meta_access_token;
                    $phoneNumberId = $basicSettings->meta_phone_number_id;
                }
            }

            if (!$accessToken || !$phoneNumberId) {
                throw new \Exception('Meta WhatsApp API credentials not configured');
            }

            // Prepare message payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            // Send message via Meta API
            $apiVersion = config('services.meta.api_version', 'v20.0');
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? null;

                Log::info('Meta WhatsApp message sent successfully', [
                    'phone' => $phoneNumber,
                    'message_id' => $messageId,
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'meta_response' => $responseData,
                ];
            } else {
                $errorData = $response->json();
                Log::error('Meta WhatsApp API error', [
                    'phone' => $phoneNumber,
                    'response' => $errorData,
                    'status' => $response->status()
                ]);

                throw new \Exception('Meta API error: ' . ($errorData['error']['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Meta WhatsApp message exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
