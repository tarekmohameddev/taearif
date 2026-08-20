<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use App\Models\ApiCustomer;

final class CallLog extends Model
{
    protected $table = 'call_logs';

    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'customer_id',
        'user_id',
        'trunk_id',
        'sim_line_id',
        'asterisk_channel',
        'direction',
        'to_e164',
        'from_e164',
        'status',
        'fail_reason',
        'answered_at',
        'ended_at',
        'duration_seconds',
    ];

    protected $casts = [
        'answered_at'      => 'datetime',
        'ended_at'         => 'datetime',
        'duration_seconds' => 'integer',
    ];

    // Terminal statuses — AMI listener will not change these
    public const TERMINAL_STATUSES = ['completed', 'failed', 'busy', 'no_answer', 'canceled'];

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(CallTrunk::class, 'trunk_id');
    }

    public function simLine(): BelongsTo
    {
        return $this->belongsTo(CallSimLine::class, 'sim_line_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CallEvent::class, 'call_log_id');
    }

    public function recording(): HasOne
    {
        return $this->hasOne(CallRecording::class, 'call_log_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
