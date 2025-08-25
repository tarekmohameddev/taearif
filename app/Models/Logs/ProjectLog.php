<?php

namespace App\Models\Logs;
use Illuminate\Database\Eloquent\Model;


class ProjectLog extends Model {
    protected $table = 'project_logs';
    protected $fillable = [
        'tenant_id','project_id','action','actor_id','actor_type','ip_address','user_agent','changes','note'
    ];
    protected $casts = ['changes' => 'array'];
}

