<?php

namespace App\Events;

use App\Models\ContactMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ContactMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->message->tenant_id)];
    }

    public function broadcastAs(): string
    {
        return 'contact-message.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'source' => $this->message->source,
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }
}
