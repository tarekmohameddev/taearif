<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationWebhookEvent extends Model
{
    protected $table = 'communication_webhook_events';

    protected $fillable = [
        'user_id',
        'channel',
        'provider',
        'event_type',
        'provider_event_id',
        'provider_message_id',
        'event_hash',
        'signature_valid',
        'tenant_resolved',
        'processing_result',
        'error_code',
        'error_message',
        'payload',
        'received_at',
        'processed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'signature_valid' => 'boolean',
        'tenant_resolved' => 'boolean',
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
