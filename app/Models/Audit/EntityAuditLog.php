<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class EntityAuditLog extends Model
{
    protected $table = 'entity_audit_logs';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'changed_by',
        'changed_by_type',
        'reason',
        'ip_address',
        'user_agent',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
