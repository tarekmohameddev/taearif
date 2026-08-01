<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CallEvent extends Model
{
    protected $table = 'call_events';

    public $timestamps = false;

    protected $fillable = [
        'call_log_id',
        'event_name',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }
}
