<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessageLog extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_id',
        'customer_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_html',
        'body_text',
        'status',
        'gateway_message_id',
        'provider',
        'error_message',
        'sent_at',
        'delivered_at',
        'refund_processed_at',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'refund_processed_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
}

