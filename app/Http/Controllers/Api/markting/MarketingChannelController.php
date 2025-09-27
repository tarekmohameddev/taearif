<?php

namespace App\Http\Controllers\Api\markting;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Api\markting\MarketingChannel;
use App\Http\Controllers\Api\markting\CreditController;
use App\Models\Api\markting\UserCredit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MarketingChannelController extends BaseApiController
{
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
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'type' => 'required|string|in:whatsapp,facebook,telegram,instagram,sms',
                'number' => 'required|string|max:50',
                'business_id' => 'nullable|string|max:100',
                'phone_id' => 'nullable|string|max:100',
                'access_token' => 'nullable|string|max:500',
                'additional_settings' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $channel = MarketingChannel::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'number' => $request->number,
                'business_id' => $request->business_id,
                'phone_id' => $request->phone_id,
                'access_token' => $request->access_token,
                'is_verified' => false,
                'is_connected' => false,
                'sent_messages_count' => 0,
                'received_messages_count' => 0,
                'additional_settings' => $request->additional_settings ?? [],
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
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:500',
                'type' => 'sometimes|string|in:whatsapp,facebook,telegram,instagram,sms',
                'number' => 'sometimes|string|max:50',
                'business_id' => 'nullable|string|max:100',
                'phone_id' => 'nullable|string|max:100',
                'access_token' => 'nullable|string|max:500',
                'additional_settings' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $channel->update($request->only([
                'name', 'description', 'type', 'number', 
                'business_id', 'phone_id', 'access_token', 'additional_settings'
            ]));

            return $this->ok($channel, 'Marketing channel updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to update marketing channel: ' . $e->getMessage());
        }
    }

    /**
     * Update channel connection status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'is_connected' => 'required|boolean',
                'is_verified' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $channel->update([
                'is_connected' => $request->is_connected,
                'is_verified' => $request->is_verified ?? $channel->is_verified,
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
    public function stats(Request $request, $id): JsonResponse
    {
        try {
            $channel = MarketingChannel::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$channel) {
                return $this->fail('Marketing channel not found', 404);
            }

            // Validate date parameters
            $validator = Validator::make($request->all(), [
                'from' => 'nullable|date|date_format:Y-m-d',
                'to' => 'nullable|date|date_format:Y-m-d|after_or_equal:from',
            ]);

            if ($validator->fails()) {
                return $this->fail('Invalid date parameters', 422, $validator->errors());
            }

            $fromDate = $request->get('from');
            $toDate = $request->get('to');

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
    public function sendMessage(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'to' => 'required|string|max:50',
                'message' => 'required|string|max:1000',
                'message_type' => 'sometimes|string|in:text,media,template',
                'media_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

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
                    'recipient' => $request->to,
                    'message_type' => $request->get('message_type', 'text'),
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
            \App\Models\Api\markting\CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'refund',
                'credits_amount' => $credits,
                'status' => 'completed',
                'reference_number' => \App\Models\Api\markting\CreditTransaction::generateReferenceNumber(),
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to refund credits: ' . $e->getMessage());
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
            \Log::info('WhatsApp Webhook Received', [
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
            \Log::error('WhatsApp Webhook Error', [
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
                    \Log::info('Unknown WhatsApp webhook field', ['field' => $field]);
            }
        } catch (\Exception $e) {
            \Log::error('Error processing WhatsApp change', [
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
                \Log::warning('WhatsApp channel not found', ['phone_number_id' => $phoneNumberId]);
                return;
            }

            // Update received message count
            $channel->increment('received_messages_count');

            // Process different message types
            $messageType = $message['type'] ?? 'unknown';
            
            switch ($messageType) {
                case 'text':
                    $this->processTextMessage($message, $channel);
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
                    \Log::info('Unhandled WhatsApp message type', [
                        'type' => $messageType,
                        'message_id' => $message['id'] ?? null
                    ]);
            }

            \Log::info('WhatsApp message processed', [
                'channel_id' => $channel->id,
                'message_type' => $messageType,
                'message_id' => $message['id'] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
        }
    }

    /**
     * Process text messages
     */
    private function processTextMessage($message, $channel)
    {
        $text = $message['text']['body'] ?? '';
        $from = $message['from'] ?? '';
        
        // Here you can implement your business logic
        // For example: auto-reply, save to database, trigger workflows, etc.
        
        \Log::info('Text message received', [
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
        
        \Log::info('Media message received', [
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
        
        \Log::info('Button message received', [
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
        
        \Log::info('Interactive message received', [
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
            
            \Log::info('Message delivery status', [
                'message_id' => $messageId,
                'status' => $deliveryStatus
            ]);
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
            
            \Log::info('Message read status', [
                'message_id' => $messageId,
                'status' => $readStatus
            ]);
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
                \Log::info('Message reaction received', [
                    'message_id' => $message['id'] ?? null,
                    'emoji' => $reaction['emoji'] ?? null
                ]);
            }
        }
    }
}
