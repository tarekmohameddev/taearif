<?php

declare(strict_types=1);

namespace App\Domain\Ai\Knowledge;

use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeSource;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class EmbeddingService
{
    // Always use platform embedding model regardless of tenant chat provider
    private const EMBEDDING_MODEL = 'text-embedding-3-small';
    private const EMBEDDING_DIMS  = 1536;
    private const BATCH_SIZE      = 100;

    public function __construct(
        private readonly string $openAiApiKey,
    ) {}

    /**
     * Embed and store chunks for a knowledge source.
     * Skips chunks whose content_hash already exists (incremental).
     */
    public function indexSource(AiKnowledgeSource $source, string $rawText): void
    {
        $chunker = new TextChunker();
        $chunks  = $chunker->chunk(ArabicNormalizer::normalize($rawText));

        if (empty($chunks)) {
            Log::warning('ai.embedding.empty_chunks', ['source_id' => $source->id]);
            return;
        }

        // Remove stale chunks no longer in source
        $newHashes = array_map(fn ($c) => hash('sha256', $c), $chunks);
        AiKnowledgeChunk::query()
            ->where('source_id', $source->id)
            ->whereNotIn('content_hash', $newHashes)
            ->delete();

        // Embed in batches
        $chunkIndex = 0;
        foreach (array_chunk($chunks, self::BATCH_SIZE) as $batch) {
            $embeddings = $this->callEmbeddingApi($batch);
            foreach ($batch as $i => $chunkText) {
                $hash      = hash('sha256', $chunkText);
                $embedding = $embeddings[$i] ?? [];
                if (empty($embedding)) {
                    continue;
                }

                AiKnowledgeChunk::updateOrCreate(
                    ['source_id' => $source->id, 'content_hash' => $hash],
                    [
                        'user_id'         => $source->user_id,
                        'content'         => $chunkText,
                        'chunk_index'     => $chunkIndex++,
                        'embedding_json'  => json_encode($embedding),
                        'embedding_model' => self::EMBEDDING_MODEL,
                        'embedding_dims'  => self::EMBEDDING_DIMS,
                        'metadata'        => ['source_id' => $source->id, 'source_name' => $source->name],
                    ]
                );
            }
        }

        $count = AiKnowledgeChunk::where('source_id', $source->id)->count();
        $source->update([
            'chunk_count'     => $count,
            'embedding_model' => self::EMBEDDING_MODEL,
            'last_indexed_at' => now(),
            'content_hash'    => hash('sha256', $rawText),
        ]);

        // Bust tenant retrieval cache
        Cache::forget('ai.embedding.matrix.' . $source->user_id);
    }

    /**
     * @return float[]
     */
    public function embedQuery(string $text): array
    {
        $normalized = ArabicNormalizer::normalizeForSearch($text);
        $results    = $this->callEmbeddingApi([$normalized]);
        return $results[0] ?? [];
    }

    /**
     * @return float[][]
     */
    private function callEmbeddingApi(array $inputs): array
    {
        try {
            $http = new GuzzleClient(['timeout' => 30]);
            $resp = $http->post('https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => ['model' => self::EMBEDDING_MODEL, 'input' => $inputs],
            ]);
            $body = json_decode((string) $resp->getBody(), true);
            $data = $body['data'] ?? [];
            usort($data, fn ($a, $b) => $a['index'] <=> $b['index']);
            return array_map(fn ($d) => $d['embedding'], $data);
        } catch (\Throwable $e) {
            Log::error('ai.embedding.api_error', ['error' => $e->getMessage()]);
            return array_fill(0, count($inputs), []);
        }
    }
}
