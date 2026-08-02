<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AiKnowledgeSource extends Model
{
    protected $table = 'ai_knowledge_sources';

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'file_path',
        'mime_type',
        'chunk_count',
        'embedding_model',
        'active',
        'last_indexed_at',
        'content_hash',
    ];

    protected $casts = [
        'active'          => 'boolean',
        'last_indexed_at' => 'datetime',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'source_id');
    }
}
