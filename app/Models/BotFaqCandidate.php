<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BotFaqCandidate extends Model
{
    protected $table = 'bot_faq_candidates';

    protected $fillable = [
        'user_id',
        'cluster_key',
        'question',
        'drafted_answer',
        'occurrence_count',
        'approval_status',
        'knowledge_source_id',
        'mine_batch',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
    ];

    public function knowledgeSource(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeSource::class, 'knowledge_source_id');
    }
}
