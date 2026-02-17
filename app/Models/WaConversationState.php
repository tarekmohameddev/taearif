<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaConversationState extends Model
{
    protected $table = 'wa_conversation_states';

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
}
