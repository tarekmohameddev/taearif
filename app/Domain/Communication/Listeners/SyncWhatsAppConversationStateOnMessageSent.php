<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Events\MessageSent as MessageSentEvent;
use App\Models\Message;
use App\Models\WaConversationState;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncWhatsAppConversationStateOnMessageSent implements ShouldQueue
{
    public function viaQueue(): string
    {
        return config('communication.automation.queue', 'communication');
    }

    public function handle(MessageSentEvent $event): void
    {
        $message = Message::with('conversation')->find($event->messageId);
        if (! $message || ! $message->conversation) {
            return;
        }
        if (strtolower((string) $message->conversation->channel) !== 'whatsapp') {
            return;
        }

        $state = WaConversationState::where('conversation_id', $message->conversation_id)->first();
        if (! $state) {
            $state = WaConversationState::create([
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'wa_number_id' => is_array($message->meta) ? ($message->meta['wa_number_id'] ?? null) : null,
                'status' => 'active',
                'is_starred' => false,
                'unread_count' => 0,
            ]);
        }

        $preview = is_scalar($message->content) ? (string) $message->content : (string) json_encode($message->content ?? '');
        $state->update([
            'last_message_preview' => \Illuminate\Support\Str::limit($preview, 500),
            'last_message_time' => $message->created_at ?? now(),
        ]);
    }
}
