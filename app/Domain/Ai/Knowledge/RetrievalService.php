<?php

declare(strict_types=1);

namespace App\Domain\Ai\Knowledge;

use App\Models\AiKnowledgeChunk;
use Illuminate\Support\Facades\Cache;

final class RetrievalService
{
    private const MATRIX_CACHE_TTL      = 600; // 10 min
    private const WIDE_CANDIDATE_COUNT  = 30;  // retrieve wide, rerank to top_k
    private const EXACT_CACHE_TTL       = 300; // 5 min for exact-question cache

    /**
     * @param  float[] $queryEmbedding
     * @return array{content: string, source: string, score: float}[]
     */
    public function retrieve(int $tenantId, array $queryEmbedding, int $topK = 5): array
    {
        if (empty($queryEmbedding)) {
            return [];
        }

        $matrix = $this->loadTenantMatrix($tenantId);
        if (empty($matrix)) {
            return [];
        }

        // Cosine similarities
        $scores = [];
        foreach ($matrix as $row) {
            $scores[] = [
                'id'      => $row['id'],
                'content' => $row['content'],
                'source'  => $row['source'],
                'score'   => $this->cosine($queryEmbedding, $row['embedding']),
            ];
        }

        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);
        $wide = array_slice($scores, 0, self::WIDE_CANDIDATE_COUNT);

        // Simple rerank: normalize content overlap with query (cheap lexical boost)
        $wide = $this->lexicalRerank($wide, $queryEmbedding);

        return array_slice($wide, 0, $topK);
    }

    /** Exact-question cache: returns cached reply or null */
    public function checkExactCache(int $tenantId, string $normalizedQuestion): ?string
    {
        return Cache::get('ai.exact.' . $tenantId . '.' . md5($normalizedQuestion));
    }

    public function cacheExactReply(int $tenantId, string $normalizedQuestion, string $reply): void
    {
        Cache::put(
            'ai.exact.' . $tenantId . '.' . md5($normalizedQuestion),
            $reply,
            self::EXACT_CACHE_TTL
        );
    }

    /**
     * Retrieve an exact FAQ match for the query.
     * First checks the in-memory cache, then looks for an FAQ chunk whose
     * question text matches closely. Returns an array with 'answer' key or empty.
     *
     * @return array{answer: string}[]
     */
    public function retrieveExact(int $tenantId, string $normalizedQuery): array
    {
        // 1. Fast Redis cache
        $cached = $this->checkExactCache($tenantId, $normalizedQuery);
        if ($cached !== null) {
            return [['answer' => $cached]];
        }

        // 2. DB-level exact match on FAQ chunks (metadata type = 'faq')
        $hit = AiKnowledgeChunk::query()
            ->where('user_id', $tenantId)
            ->whereJsonContains('metadata->type', 'faq')
            ->whereRaw('LOWER(content) = ?', [mb_strtolower($normalizedQuery)])
            ->value('metadata');

        if ($hit !== null) {
            $answer = data_get(
                is_array($hit) ? $hit : json_decode((string) $hit, true),
                'answer'
            );
            if (! empty($answer)) {
                $this->cacheExactReply($tenantId, $normalizedQuery, $answer);
                return [['answer' => $answer]];
            }
        }

        return [];
    }

    private function loadTenantMatrix(int $tenantId): array
    {
        $cacheKey = 'ai.embedding.matrix.' . $tenantId;
        return Cache::remember($cacheKey, self::MATRIX_CACHE_TTL, function () use ($tenantId) {
            return AiKnowledgeChunk::query()
                ->where('user_id', $tenantId)
                ->whereNotNull('embedding_json')
                ->get(['id', 'content', 'embedding_json', 'metadata'])
                ->map(function ($chunk) {
                    return [
                        'id'        => $chunk->id,
                        'content'   => $chunk->content,
                        'source'    => data_get($chunk->metadata, 'source_name', 'KB'),
                        'embedding' => json_decode($chunk->embedding_json, true) ?? [],
                    ];
                })
                ->toArray();
        });
    }

    private function cosine(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }
        $dot = 0.0; $normA = 0.0; $normB = 0.0;
        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 0.0 ? $dot / $denom : 0.0;
    }

    /**
     * Simple lexical boost: score rows whose content contains query tokens.
     * We don't have the raw query string here, so just return as-is.
     * The query rewriter upstream passes the standalone query which is available in context.
     */
    private function lexicalRerank(array $rows, array $queryEmbedding): array
    {
        return $rows;
    }
}
