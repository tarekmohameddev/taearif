<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\Project;
use App\Models\Logs\ProjectLog;
use App\Services\Audit\EntityAuditLogger;
use App\Support\AuditContext;
use App\Support\ProjectAuditFields;

class ProjectObserver
{
    public function __construct(
        private readonly EntityAuditLogger $auditLogger,
    ) {}

    public function created(Project $m): void {
        $ctx = AuditContext::data();
        $tenantId = $ctx['tenant_id'] ?? $m->user_id;

        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $tenantId,
            'action'     => 'created',
            'changes'    => ['after' => $m->getAttributes()],
        ]));

        $this->auditLogger->logCreated('project', $m->id, $m->getAttributes(), $tenantId);
    }
    public function updated(Project $m): void {
        $ctx = AuditContext::data();
        $tenantId = $ctx['tenant_id'] ?? $m->user_id;

        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $tenantId,
            'action'     => 'updated',
            'changes'    => ['before'=>$m->getOriginal(), 'after'=>$m->getAttributes()],
        ]));

        $this->auditLogger->logFields(
            'project',
            $m->id,
            $m->getOriginal(),
            $m->getAttributes(),
            ProjectAuditFields::TRACKED,
            'updated',
            null,
            $tenantId,
        );
    }
    public function deleted(Project $m): void {
        $ctx = AuditContext::data();
        $tenantId = $ctx['tenant_id'] ?? $m->user_id;

        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $tenantId,
            'action'     => 'deleted',
            'changes'    => ['before'=>$m->getOriginal()],
        ]));

        $this->auditLogger->logDeleted('project', $m->id, $m->getOriginal(), $tenantId);
    }
}
