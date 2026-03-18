<?php

namespace App\Models\Api\marketing;

use App\Models\ApiCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingChannelMessage extends Model
{
    protected $table = 'marketing_channel_messages';

    protected $fillable = [
        'user_id',
        'channel_id',
        'customer_id',
        'recipient_phone',
        'recipient_name',
        'message_content',
        'message_type',
        'status',
        'provider_message_id',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'credits_used',
        'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MarketingChannel::class, 'channel_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }

    public function markAsSent(string $messageId): bool
    {
        return $this->update([
            'status' => 'sent',
            'provider_message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(?Carbon $timestamp = null): bool
    {
        return $this->update([
            'status' => 'delivered',
            'delivered_at' => $timestamp ?? now(),
        ]);
    }

    public function markAsRead(?Carbon $timestamp = null): bool
    {
        return $this->update([
            'status' => 'read',
            'read_at' => $timestamp ?? now(),
        ]);
    }

    public function markAsFailed(?string $errorCode = null, ?string $errorMessage = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_code' => $errorCode,
            'error_message' => $errorMessage ?? 'Delivery failed',
        ]);
    }
}
