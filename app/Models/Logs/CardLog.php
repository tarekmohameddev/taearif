<?php

namespace App\Models\Logs;

use Illuminate\Database\Eloquent\Model;

class CardLog extends Model
{
    protected $table = 'card_logs';

    protected $fillable = [
        'tenant_id','card_id','action',
        'actor_id','actor_type','ip_address','user_agent',
        'changes','note',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
