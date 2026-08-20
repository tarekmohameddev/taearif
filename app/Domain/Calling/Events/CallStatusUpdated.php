<?php

declare(strict_types=1);

namespace App\Domain\Calling\Events;

use App\Domain\Calling\Models\CallLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CallStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $callId;
    public string $status;
    public int    $tenantId;
    public ?int   $customerId;
    public ?int   $agentUserId;
    public ?string $answeredAt;
    public ?string $endedAt;
    public ?int    $durationSeconds;

    public function __construct(CallLog $log)
    {
        $this->callId          = $log->id;
        $this->status          = $log->status;
        $this->tenantId        = $log->tenant_id;
        $this->customerId      = $log->customer_id;
        $this->agentUserId     = $log->user_id;
        $this->answeredAt      = $log->answered_at?->toIso8601String();
        $this->endedAt         = $log->ended_at?->toIso8601String();
        $this->durationSeconds = $log->duration_seconds;
    }

    /**
     * Broadcast on the existing private tenant channel (already defined in routes/channels.php).
     * Auth: tenant owner id must match the tenantId segment.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'call.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'          => $this->callId,
            'status'           => $this->status,
            'tenant_id'        => $this->tenantId,
            'customer_id'      => $this->customerId,
            'agent_user_id'    => $this->agentUserId,
            'answered_at'      => $this->answeredAt,
            'ended_at'         => $this->endedAt,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
