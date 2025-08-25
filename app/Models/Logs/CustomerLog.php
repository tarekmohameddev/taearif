<?php

namespace App\Models\Logs;
use Illuminate\Database\Eloquent\Model;

class CustomerLog extends Model {
    protected $table = 'customer_logs';
    protected $fillable = [
        'tenant_id','customer_id','action','actor_id','actor_type','ip_address','user_agent','changes','note'
    ];
    protected $casts = ['changes' => 'array'];
}
