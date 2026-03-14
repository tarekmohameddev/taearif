<?php

namespace App\Console\Commands;

use App\Models\Api\marketing\MarketingChannelMessage;
use Illuminate\Console\Command;

class TestMarketingWhatsAppWebhookCommand extends Command
{
    protected $signature = 'marketing:test-webhook
                            {provider_message_id : The provider_message_id to update (e.g. wamid.xxx)}
                            {status=delivered : delivered or read}';

    protected $description = 'Simulate a Meta WhatsApp delivery/read webhook by updating a marketing_channel_message by provider_message_id.';

    public function handle(): int
    {
        $messageId = $this->argument('provider_message_id');
        $status = $this->argument('status');

        if (! in_array($status, ['delivered', 'read'], true)) {
            $this->error('Status must be "delivered" or "read".');
            return self::FAILURE;
        }

        $message = MarketingChannelMessage::where('provider_message_id', $messageId)->first();

        if (! $message) {
            $this->error("No marketing_channel_message found with provider_message_id: {$messageId}");
            $this->line('Create one by sending a WhatsApp message via the API, or in tinker.');
            return self::FAILURE;
        }

        $before = $message->status;
        if ($status === 'delivered') {
            $message->update(['status' => 'delivered', 'delivered_at' => $message->delivered_at ?? now()]);
        } else {
            $message->update(['status' => 'read', 'read_at' => $message->read_at ?? now()]);
        }
        $message->refresh();

        $this->info("Updated record id={$message->id}: {$before} -> {$message->status}");
        $this->table(
            ['id', 'provider_message_id', 'status', 'sent_at', 'delivered_at', 'read_at'],
            [[
                $message->id,
                $message->provider_message_id,
                $message->status,
                $message->sent_at?->toDateTimeString(),
                $message->delivered_at?->toDateTimeString(),
                $message->read_at?->toDateTimeString(),
            ]]
        );

        return self::SUCCESS;
    }
}
