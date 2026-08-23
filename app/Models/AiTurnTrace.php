<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTurnTrace extends Model
{
    protected $table = 'ai_turn_traces';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'trigger_message_id',
        'idempotency_key',
        'brief_before',
        'brief_after',
        'steps',
        'tool_call_log',
        'guard_violations',
        'model',
        'tokens_in',
        'tokens_out',
        'latency_ms',
        'decision',
        'rendered_reply',
        'delivery_status',
        'delivery_attempts',
        'cassette_key',
    ];

    protected $casts = [
        'brief_before'     => 'array',
        'brief_after'      => 'array',
        'steps'            => 'array',
        'tool_call_log'    => 'array',
        'guard_violations' => 'array',
        'tokens_in'        => 'integer',
        'tokens_out'       => 'integer',
        'latency_ms'       => 'integer',
        'delivery_attempts'=> 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
