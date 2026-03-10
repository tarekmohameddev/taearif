<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaMessageLog extends Model
{
    protected $table = 'wa_message_logs';

    protected $fillable = [
        'user_id',
        'campaign_id',
        'customer_id',
        'wa_number_id',
        'message_id',
        'recipient_phone',
        'recipient_name',
        'message',
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
        return $this->belongsTo(WaCampaign::class, 'campaign_id');
    }

    public function waNumber(): BelongsTo
    {
        return $this->belongsTo(WaNumber::class, 'wa_number_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
}
