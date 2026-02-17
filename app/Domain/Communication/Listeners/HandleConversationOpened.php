<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Automation\AutomationEngine;
use App\Domain\Communication\Events\ConversationOpened as ConversationOpenedEvent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleConversationOpened implements ShouldQueue
{
    public function viaQueue(): string
    {
        return config('communication.automation.queue', 'communication');
    }

    public function handle(ConversationOpenedEvent $event): void
    {
        $conversation = Conversation::find($event->conversationId);
        $firstMessage = Message::find($event->firstMessageId);
        if (! $conversation || ! $firstMessage) {
            return;
        }
        app(AutomationEngine::class)->handleConversationOpened($conversation, $firstMessage);
    }
}
