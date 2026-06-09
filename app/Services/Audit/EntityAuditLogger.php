<?php

namespace App\Services\Audit;

use App\Models\Audit\EntityAuditLog;
use App\Support\AuditContext;

class EntityAuditLogger
{
    public function logField(
        string $entityType,
        int $entityId,
        string $fieldName,
        mixed $oldValue,
        mixed $newValue,
        string $action = 'updated',
        ?string $reason = null,
        ?int $tenantId = null,
    ): void {
        if ($this->valuesEqual($oldValue, $newValue)) {
            return;
        }

        $this->insertRow($entityType, $entityId, $action, $fieldName, $oldValue, $newValue, $reason, $tenantId);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $trackedFields
     */
    public function logFields(
        string $entityType,
        int $entityId,
        array $before,
        array $after,
        array $trackedFields,
        string $action = 'updated',
        ?string $reason = null,
        ?int $tenantId = null,
    ): void {
        foreach ($trackedFields as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($this->valuesEqual($old, $new)) {
                continue;
            }

            $this->insertRow($entityType, $entityId, $action, $field, $old, $new, $reason, $tenantId);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logCreated(string $entityType, int $entityId, array $attributes, ?int $tenantId = null): void
    {
        $this->insertRow(
            $entityType,
            $entityId,
            'created',
            null,
            null,
            null,
            null,
            $tenantId,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logDeleted(string $entityType, int $entityId, array $attributes, ?int $tenantId = null): void
    {
        $this->insertRow(
            $entityType,
            $entityId,
            'deleted',
            null,
            null,
            null,
            null,
            $tenantId,
        );
    }

    public function logAction(
        string $entityType,
        int $entityId,
        string $action,
        ?string $reason = null,
        ?int $tenantId = null,
    ): void {
        $this->insertRow($entityType, $entityId, $action, null, null, null, $reason, $tenantId);
    }

    private function insertRow(
        string $entityType,
        int $entityId,
        string $action,
        ?string $fieldName,
        mixed $oldValue,
        mixed $newValue,
        ?string $reason,
        ?int $tenantId,
    ): void {
        $ctx = AuditContext::data();

        EntityAuditLog::create([
            'tenant_id' => $tenantId ?? $ctx['tenant_id'],
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $this->serializeValue($oldValue),
            'new_value' => $this->serializeValue($newValue),
            'changed_by' => $ctx['actor_id'],
            'changed_by_type' => $ctx['actor_type'] ?? 'tenant',
            'reason' => $reason,
            'ip_address' => $ctx['ip_address'],
            'user_agent' => $ctx['user_agent'],
            'changed_at' => now(),
        ]);
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function valuesEqual(mixed $old, mixed $new): bool
    {
        return $this->serializeValue($old) === $this->serializeValue($new);
    }
}
