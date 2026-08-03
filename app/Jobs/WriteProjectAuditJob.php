<?php

namespace App\Jobs;

use App\Models\Logs\ProjectLog;
use App\Services\Audit\EntityAuditLogger;
use App\Support\AuditContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteProjectAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{
     *     tenant_id: int,
     *     project_id: int,
     *     actor_id: ?int,
     *     actor_type: string,
     *     ip_address: ?string,
     *     user_agent: ?string,
     *     changes: ?array,
     *     note?: ?string,
     *     attributes?: array
     * }  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(EntityAuditLogger $auditLogger): void
    {
        $payload = $this->payload;
        $previous = AuditContext::data();

        try {
            AuditContext::set(
                $payload['actor_id'] ?? null,
                $payload['actor_type'] ?? 'tenant',
                $payload['tenant_id'] ?? null,
                $payload['ip_address'] ?? null,
                $payload['user_agent'] ?? null,
            );

            $tenantId = $payload['tenant_id'];
            $projectId = (int) $payload['project_id'];
            $attributes = $payload['attributes'] ?? ($payload['changes']['after'] ?? []);

            ProjectLog::create([
                'tenant_id' => $tenantId,
                'project_id' => $projectId,
                'action' => 'created',
                'actor_id' => $payload['actor_id'] ?? null,
                'actor_type' => $payload['actor_type'] ?? 'tenant',
                'ip_address' => $payload['ip_address'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'changes' => $payload['changes'] ?? ['after' => $attributes],
                'note' => $payload['note'] ?? null,
            ]);

            $auditLogger->logCreated('project', $projectId, is_array($attributes) ? $attributes : [], $tenantId);
        } finally {
            // Prevent leaking request actor context into later jobs on the same worker.
            AuditContext::set(
                $previous['actor_id'] ?? null,
                $previous['actor_type'] ?? 'tenant',
                $previous['tenant_id'] ?? null,
                $previous['ip_address'] ?? null,
                $previous['user_agent'] ?? null,
            );
        }
    }
}
