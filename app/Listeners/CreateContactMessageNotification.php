<?php

namespace App\Listeners;

use App\Domain\Notifications\NotificationOrchestrator;
use App\Events\ContactMessageReceived;

class CreateContactMessageNotification
{
    public function __construct(private NotificationOrchestrator $orchestrator)
    {
    }

    public function handle(ContactMessageReceived $event): void
    {
        $this->orchestrator->contactMessageCreated(
            (int) $event->message->tenant_id,
            $event->message
        );
    }
}
