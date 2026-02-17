<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Automation\AutomationEngine;
use App\Domain\Communication\Events\MessageReceived as MessageReceivedEvent;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleMessageReceived implements ShouldQueue
{
    public function viaQueue(): string
    {
        return config('communication.automation.queue', 'communication');
    }

    public function handle(MessageReceivedEvent $event): void
    {
        $message = Message::with('conversation')->find($event->messageId);
        if (! $message) {
            Log::warning('communication.automation.received: message not found', ['message_id' => $event->messageId]);
            return;
        }
        app(AutomationEngine::class)->handleMessageReceived($message);
    }
}
