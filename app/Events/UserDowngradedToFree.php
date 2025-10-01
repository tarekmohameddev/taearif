<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDowngradedToFree
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $previousPackage;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param mixed $previousPackage
     */
    public function __construct(User $user, $previousPackage = null)
    {
        $this->user = $user;
        $this->previousPackage = $previousPackage;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }
}
