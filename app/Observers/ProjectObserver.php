<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\Project;
use App\Models\Logs\ProjectLog;
use App\Support\AuditContext;

class ProjectObserver
{
    public function created(Project $m): void {
        $ctx = AuditContext::data();
        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $ctx['tenant_id'] ?? $m->user_id,
            'action'     => 'created',
            'changes'    => ['after' => $m->getAttributes()],
        ]));
    }
    public function updated(Project $m): void {
        $ctx = AuditContext::data();
        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $ctx['tenant_id'] ?? $m->user_id,
            'action'     => 'updated',
            'changes'    => ['before'=>$m->getOriginal(), 'after'=>$m->getAttributes()],
        ]));
    }
    public function deleted(Project $m): void {
        $ctx = AuditContext::data();
        ProjectLog::create(array_merge($ctx, [
            'project_id' => $m->id,
            'tenant_id'  => $ctx['tenant_id'] ?? $m->user_id,
            'action'     => 'deleted',
            'changes'    => ['before'=>$m->getOriginal()],
        ]));
    }
}
