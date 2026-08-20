<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiKnowledgeChunk extends Model
{
    protected $table = 'ai_knowledge_chunks';

    protected $fillable = [
        'source_id',
        'user_id',
        'content',
        'content_hash',
        'chunk_index',
        'embedding_json',
        'embedding_model',
        'embedding_dims',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeSource::class, 'source_id');
    }

    public function getEmbeddingArray(): array
    {
        return json_decode($this->embedding_json, true) ?? [];
    }
}
