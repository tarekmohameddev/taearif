<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BotUnansweredQuestion extends Model
{
    protected $table = 'bot_unanswered_questions';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'question',
        'cluster_key',
        'occurrence_count',
        'added_to_faq',
    ];

    protected $casts = [
        'added_to_faq' => 'boolean',
    ];
}
