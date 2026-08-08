<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WaConversationState extends Model
{
    protected $table = 'wa_conversation_states';

    /**
     * Reasons that pause the bot because a human already took over —
     * these should not appear in the "needs attention" inbox queue.
     */
    public const HANDOFF_REASONS_EXCLUDED_FROM_ATTENTION = [
        'agent_takeover',
    ];

    /** @var list<string> */
    protected $appends = [
        'state_id',
        'needs_attention',
        'handoff_reason',
        'bot_paused_until',
    ];

    protected $fillable = [
        'conversation_id',
        'user_id',
        'wa_number_id',
        'status',
        'is_starred',
        'unread_count',
        'assigned_agent_id',
        'last_message_preview',
        'last_message_time',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_starred' => 'boolean',
        'last_message_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waNumber(): BelongsTo
    {
        return $this->belongsTo(WaNumber::class, 'wa_number_id');
    }

    /**
     * Bot AI state for the same conversation (joined on conversation_id).
     */
    public function aiState(): HasOne
    {
        return $this->hasOne(WaConversationAiState::class, 'conversation_id', 'conversation_id');
    }

    public function getStateIdAttribute(): int
    {
        return (int) $this->getKey();
    }

    public function getNeedsAttentionAttribute(): bool
    {
        $ai = $this->resolveAiState();
        if ($ai === null || ! $ai->isBotPaused()) {
            return false;
        }

        $reason = (string) ($ai->handoff_reason ?? '');

        return $reason === '' || ! in_array($reason, self::HANDOFF_REASONS_EXCLUDED_FROM_ATTENTION, true);
    }

    public function getHandoffReasonAttribute(): ?string
    {
        $ai = $this->resolveAiState();

        return $ai?->handoff_reason;
    }

    public function getBotPausedUntilAttribute(): ?string
    {
        $ai = $this->resolveAiState();

        return $ai?->bot_paused_until?->toIso8601String();
    }

    private function resolveAiState(): ?WaConversationAiState
    {
        if ($this->relationLoaded('aiState')) {
            return $this->aiState;
        }

        return $this->aiState()->first();
    }
}
