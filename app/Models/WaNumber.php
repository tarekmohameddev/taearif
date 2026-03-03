<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WaNumber extends Model
{
    protected $table = 'wa_numbers';

    protected $fillable = [
        'user_id',
        'provider',
        'phone_number',
        'phone_number_id',
        'provider_account_id',
        'name',
        'status',
        'quota_limit',
        'quota_used',
        'marketing_channel_id',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversationStates(): HasMany
    {
        return $this->hasMany(WaConversationState::class, 'wa_number_id');
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(WaAutomationRule::class, 'wa_number_id');
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(WaAiConfig::class, 'wa_number_id');
    }

    public function aiResponseLogs(): HasMany
    {
        return $this->hasMany(WaAiResponseLog::class, 'wa_number_id');
    }
}
