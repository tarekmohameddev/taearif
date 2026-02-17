<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Automation\AutomationEngine;
use App\Domain\Communication\Events\MessageSent as MessageSentEvent;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleMessageSent implements ShouldQueue
{
    public function viaQueue(): string
    {
        return config('communication.automation.queue', 'communication');
    }

    public function handle(MessageSentEvent $event): void
    {
        $message = Message::with('conversation')->find($event->messageId);
        if (! $message) {
            return;
        }
        app(AutomationEngine::class)->handleMessageSent($message);
    }
}
