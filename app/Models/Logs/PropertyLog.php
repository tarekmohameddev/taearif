<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Model;

class PropertyLog extends Model
{
    protected $table = 'property_logs';
    protected $fillable = [
        'tenant_id', 'property_id', 'action', 'actor_id', 'actor_type', 'ip_address', 'user_agent', 'changes', 'note', 'reason',
    ];
    protected $casts = ['changes' => 'array'];
}
