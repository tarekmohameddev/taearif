<?php

namespace Modules\WhatsappAI\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'whatsapp_message_id',
        'message_type',
        'content',
        'media_url',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    /**
     * Relationships
     */
    public function conversation()
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    /**
     * Check if message is text type
     */
    public function isText(): bool
    {
        return $this->message_type === 'text';
    }

    /**
     * Check if message has media
     */
    public function hasMedia(): bool
    {
        return in_array($this->message_type, ['image', 'document', 'audio', 'video']);
    }
}

