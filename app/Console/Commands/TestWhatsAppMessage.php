<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\Api\marketing\UserCredit;
use App\Http\Controllers\Api\marketing\CreditController;
use App\Services\WhatsAppService;

class TestWhatsAppMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {user_id} {phone} {message?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp messaging for a specific user and phone number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $phone = $this->argument('phone');
        $message = $this->argument('message') ?? 'Test message from Taearif CRM system';

        $this->info("Testing WhatsApp message for User ID: {$userId}");
        $this->info("Phone: {$phone}");
        $this->info("Message: {$message}");

        try {
            // Check if user has WhatsApp channel
            $channel = MarketingChannel::where('user_id', $userId)
                ->where('type', 'whatsapp')
                ->where('is_connected', true)
                ->where('is_verified', true)
                ->first();

            if (!$channel) {
                $this->error("No active WhatsApp channel found for user {$userId}");
                $this->info("Available channels for user {$userId}:");
                $channels = MarketingChannel::where('user_id', $userId)->get();
                foreach ($channels as $ch) {
                    $this->line("- ID: {$ch->id}, Type: {$ch->type}, Connected: " . ($ch->is_connected ? 'Yes' : 'No') . ", Verified: " . ($ch->is_verified ? 'Yes' : 'No'));
                }
                return 1;
            }

            $this->info("Found WhatsApp channel: {$channel->name} (ID: {$channel->id})");

            // Check user credits
            $userCredit = UserCredit::where('user_id', $userId)->first();
            if (!$userCredit) {
                $this->error("No credit record found for user {$userId}");
                return 1;
            }

            $availableCredits = $userCredit->total_credits - $userCredit->used_credits;
            $this->info("User credits: {$availableCredits} (Total: {$userCredit->total_credits}, Used: {$userCredit->used_credits})");

            // Format phone number (add +966 if needed)
            $formattedPhone = $this->formatPhoneNumber($phone);
            $this->info("Formatted phone: {$formattedPhone}");

            // Calculate credits needed
            $creditsNeeded = UserCredit::getCostForMessageType('whatsapp');
            $this->info("Credits needed: {$creditsNeeded}");

            if ($availableCredits < $creditsNeeded) {
                $this->error("Insufficient credits. Available: {$availableCredits}, Required: {$creditsNeeded}");
                return 1;
            }

            // Use WhatsAppService to send message
            $whatsappService = new WhatsAppService();

            // Get Meta API credentials
            $accessToken = $channel->access_token;
            $phoneNumberId = $channel->phone_id;

            if (!$accessToken || !$phoneNumberId) {
                $this->error("WhatsApp channel missing API credentials");
                $this->info("Access Token: " . ($accessToken ? 'Set' : 'Missing'));
                $this->info("Phone ID: " . ($phoneNumberId ? 'Set' : 'Missing'));
                return 1;
            }

            $this->info("Sending message via Meta API...");

            // Send message via Meta API
            $apiVersion = config('services.meta.api_version', 'v20.0');
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $formattedPhone,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? null;

                $this->info("✅ Message sent successfully!");
                $this->info("Message ID: {$messageId}");

                // Update channel message count
                $channel->increment('sent_messages_count');

                // Deduct credits
                $creditResult = CreditController::useCredits(
                    $userId,
                    $creditsNeeded,
                    "Test WhatsApp message sent to {$formattedPhone}",
                    [
                        'channel_id' => $channel->id,
                        'channel_type' => 'whatsapp',
                        'recipient' => $formattedPhone,
                        'message_type' => 'text',
                    ]
                );

                if ($creditResult['success']) {
                    $this->info("Credits deducted: {$creditsNeeded}");
                    $this->info("Remaining credits: {$creditResult['remaining_credits']}");
                } else {
                    $this->warn("Failed to deduct credits: {$creditResult['error']}");
                }

                return 0;
            } else {
                $errorData = $response->json();
                $this->error("❌ Failed to send message");
                $this->error("Status: " . $response->status());
                $this->error("Error: " . ($errorData['error']['message'] ?? 'Unknown error'));
                $this->error("Response: " . json_encode($errorData, JSON_PRETTY_PRINT));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Exception occurred: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
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
}
