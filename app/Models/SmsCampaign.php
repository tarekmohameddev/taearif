<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'message',
        'template_id',
        'status',
        'scheduled_at',
        'sent_at',
        'dispatch_reference',
        'recipient_count',
        'sent_count',
        'delivered_count',
        'failed_count',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsMessageLog::class, 'campaign_id');
    }
}

