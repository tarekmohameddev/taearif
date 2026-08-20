<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WaConversationAiState extends Model
{
    protected $table = 'wa_conversation_ai_states';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'summary_through_message_id',
        'situation',
        'requirements',
        'commitments',
        'objections',
        'tone',
        'facts',
        'bot_paused_until',
        'handoff_reason',
        'last_bot_reply_at',
        'tokens_in_total',
        'tokens_out_total',
        'opt_out_status',
        'disclosed_as_assistant',
    ];

    protected $casts = [
        'facts'                  => 'array',
        'bot_paused_until'       => 'datetime',
        'last_bot_reply_at'      => 'datetime',
        'disclosed_as_assistant' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isBotPaused(): bool
    {
        return $this->bot_paused_until !== null && $this->bot_paused_until->isFuture();
    }

    public function isOptedOut(): bool
    {
        return $this->opt_out_status === 'opted_out';
    }
}
