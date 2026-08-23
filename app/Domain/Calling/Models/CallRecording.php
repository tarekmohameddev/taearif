<?php

declare(strict_types=1);

namespace App\Domain\Calling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CallRecording extends Model
{
    protected $table = 'call_recordings';

    protected $fillable = [
        'call_log_id',
        'disk',
        'path',
        'size_bytes',
        'duration_seconds',
        'status',
    ];

    protected $casts = [
        'size_bytes'       => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
