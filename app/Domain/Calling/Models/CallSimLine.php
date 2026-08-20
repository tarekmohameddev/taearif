<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class CallSimLine extends Model
{
    protected $table = 'call_sim_lines';

    protected $fillable = [
        'tenant_id',
        'trunk_id',
        'label',
        'msisdn',
        'asterisk_endpoint',
        'port_index',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'port_index' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(CallTrunk::class, 'trunk_id');
    }

    public function dedicatedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
