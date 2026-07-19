<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PipedriveSyncLog extends Model
{
    protected $table = 'pipedrive_sync_logs';

    protected $fillable = [
        'user_id',
        'status',
        'trigger',
        'person_id',
        'org_id',
        'deal_id',
        'request_payload',
        'response_body',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_body' => 'array',
        'synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
