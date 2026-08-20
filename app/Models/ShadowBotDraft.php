<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShadowBotDraft extends Model
{
    use HasFactory;
    protected $table = 'shadow_bot_drafts';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'trigger_message_id',
        'draft_reply',
        'used_sources',
        'confidence',
        'status',
        'agent_reply',
        'agent_id',
        'acted_at',
        'tokens_in',
        'tokens_out',
    ];

    protected $casts = [
        'used_sources' => 'array',
        'acted_at'     => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
