<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantWebsiteSavePagesLog extends Model
{
    public $timestamps = false;

    protected $table = 'tenant_website_save_pages_logs';

    protected $fillable = [
        'tenant_id',
        'username',
        'tenant_id_value',
        'login_session_meta',
        'server_ip',
        'server_user_agent',
        'before',
        'after',
        'created_at',
    ];

    protected $casts = [
        'login_session_meta' => 'array',
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
