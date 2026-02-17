<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaAiResponseLog extends Model
{
    protected $table = 'wa_ai_response_logs';

    protected $fillable = [
        'user_id',
        'wa_number_id',
        'conversation_id',
        'message_id',
        'scenario',
        'response_time_ms',
        'handed_off',
        'language',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'handed_off' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waNumber(): BelongsTo
    {
        return $this->belongsTo(WaNumber::class, 'wa_number_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
