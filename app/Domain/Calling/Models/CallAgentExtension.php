<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class CallAgentExtension extends Model
{
    protected $table = 'call_agent_extensions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'sip_username',
        'sip_password_encrypted',
        'extension',
        'asterisk_context',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['sip_password_encrypted'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
