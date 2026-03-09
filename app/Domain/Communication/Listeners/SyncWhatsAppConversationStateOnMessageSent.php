<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Events\MessageSent as MessageSentEvent;
use App\Models\Message;
use App\Models\WaConversationState;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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

        $incomingWaNumberId = is_array($message->meta) && isset($message->meta['wa_number_id'])
            ? (int) $message->meta['wa_number_id']
            : null;
        if ($incomingWaNumberId !== null && $incomingWaNumberId <= 0) {
            $incomingWaNumberId = null;
        }

        $state = WaConversationState::where('conversation_id', $message->conversation_id)->first();
        if (! $state) {
            $state = WaConversationState::create([
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'wa_number_id' => $incomingWaNumberId,
                'status' => 'active',
                'is_starred' => false,
                'unread_count' => 0,
            ]);
            Log::info('communication.whatsapp.wa_number_mapping', [
                'outcome' => $incomingWaNumberId === null ? 'unresolved' : 'resolved',
                'conversation_id' => (int) $message->conversation_id,
                'user_id' => (int) $message->user_id,
                'wa_number_id' => $incomingWaNumberId,
                'source' => 'message_sent_listener',
            ]);
        } else {
            $existingWaNumberId = $state->wa_number_id !== null ? (int) $state->wa_number_id : null;
            if ($incomingWaNumberId === null) {
                Log::info('communication.whatsapp.wa_number_mapping', [
                    'outcome' => 'unresolved',
                    'conversation_id' => (int) $message->conversation_id,
                    'user_id' => (int) $message->user_id,
                    'existing_wa_number_id' => $existingWaNumberId,
                    'source' => 'message_sent_listener',
                ]);
            } elseif ($existingWaNumberId === null) {
                $state->update(['wa_number_id' => $incomingWaNumberId]);
                Log::info('communication.whatsapp.wa_number_mapping', [
                    'outcome' => 'backfilled',
                    'conversation_id' => (int) $message->conversation_id,
                    'user_id' => (int) $message->user_id,
                    'wa_number_id' => $incomingWaNumberId,
                    'source' => 'message_sent_listener',
                ]);
            } elseif ($existingWaNumberId !== $incomingWaNumberId) {
                Log::warning('communication.whatsapp.wa_number_mapping', [
                    'outcome' => 'mismatch_kept_existing',
                    'conversation_id' => (int) $message->conversation_id,
                    'user_id' => (int) $message->user_id,
                    'existing_wa_number_id' => $existingWaNumberId,
                    'incoming_wa_number_id' => $incomingWaNumberId,
                    'source' => 'message_sent_listener',
                ]);
            } else {
                Log::info('communication.whatsapp.wa_number_mapping', [
                    'outcome' => 'resolved',
                    'conversation_id' => (int) $message->conversation_id,
                    'user_id' => (int) $message->user_id,
                    'wa_number_id' => $incomingWaNumberId,
                    'source' => 'message_sent_listener',
                ]);
            }
        }

        $preview = is_scalar($message->content) ? (string) $message->content : (string) json_encode($message->content ?? '');
        $state->update([
            'last_message_preview' => \Illuminate\Support\Str::limit($preview, 500),
            'last_message_time' => $message->created_at ?? now(),
        ]);
    }
}
