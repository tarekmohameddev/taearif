<?php

//Sometimes you perform non‑CRUD actions (reorder featured, duplicate, toggle…). Call this anywhere:


namespace App\Support;

use App\Models\Logs\CustomerLog;
use App\Models\Logs\ProjectLog;
use App\Models\Logs\PropertyLog;
use App\Services\Audit\EntityAuditLogger;

class Audit
{
    public static function customer(int $tenantId, int $customerId, string $action, ?string $note = null, array $changes = null): void {
        CustomerLog::create(array_merge(AuditContext::data(), [
            'tenant_id'   => $tenantId,
            'customer_id' => $customerId,
            'action'      => $action,
            'note'        => $note,
            'changes'     => $changes,
        ]));
    }

    public static function project(int $tenantId, int $projectId, string $action, ?string $note = null, array $changes = null): void {
        ProjectLog::create(array_merge(AuditContext::data(), [
            'tenant_id'  => $tenantId,
            'project_id' => $projectId,
            'action'     => $action,
            'note'       => $note,
            'changes'    => $changes,
        ]));

        app(EntityAuditLogger::class)->logAction('project', $projectId, $action, $note ?? null, $tenantId);
    }

    public static function property(int $tenantId, int $propertyId, string $action, ?string $note = null, array $changes = null): void {
        PropertyLog::create(array_merge(AuditContext::data(), [
            'tenant_id'   => $tenantId,
            'property_id' => $propertyId,
            'action'      => $action,
            'note'        => $note,
            'changes'     => $changes,
        ]));

        app(EntityAuditLogger::class)->logAction('property', $propertyId, $action, $note ?? null, $tenantId);
    }

    public static function card(int $tenantId, int $cardId, string $action, ?string $note = null, array $changes = null): void
    {
        CardLog::create(array_merge(AuditContext::data(), [
            'tenant_id' => $tenantId,
            'card_id'   => $cardId,
            'action'    => $action,
            'note'      => $note,
            'changes'   => $changes,
        ]));
    }
}
