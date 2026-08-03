<?php

declare(strict_types=1);

namespace App\Domain\Ai\Knowledge;

use App\Domain\Ai\DTOs\LlmResponse;
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
    public function indexSource(AiKnowledgeSource $source, string $rawText): int
    {
        $chunker = new TextChunker();
        $chunks  = $chunker->chunk(ArabicNormalizer::normalize($rawText));

        if (empty($chunks)) {
            Log::warning('ai.embedding.empty_chunks', ['source_id' => $source->id]);
            return 0;
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

        return $count;
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
     * Embed a single query and return both the embedding vector and an LlmResponse
     * carrying token usage so callers can record it via UsageRecorder.
     *
     * @return array{0: float[], 1: ?LlmResponse}
     */
    public function embedQueryWithUsage(string $text): array
    {
        $normalized = ArabicNormalizer::normalizeForSearch($text);
        [$embeddings, $usageResponse] = $this->callEmbeddingApiWithUsage([$normalized]);
        return [$embeddings[0] ?? [], $usageResponse];
    }

    /**
     * @return float[][]
     */
    private function callEmbeddingApi(array $inputs): array
    {
        [$embeddings] = $this->callEmbeddingApiWithUsage($inputs);
        return $embeddings;
    }

    /**
     * @return array{0: float[][], 1: ?LlmResponse}
     */
    private function callEmbeddingApiWithUsage(array $inputs): array
    {
        $startMs = (int) round(microtime(true) * 1000);
        try {
            $http = new GuzzleClient(['timeout' => 30]);
            $resp = $http->post('https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => ['model' => self::EMBEDDING_MODEL, 'input' => $inputs],
            ]);
            $latencyMs = (int) round(microtime(true) * 1000) - $startMs;
            $body = json_decode((string) $resp->getBody(), true);
            $data = $body['data'] ?? [];
            usort($data, fn ($a, $b) => $a['index'] <=> $b['index']);
            $embeddings = array_map(fn ($d) => $d['embedding'], $data);

            $tokensIn = (int) ($body['usage']['prompt_tokens'] ?? 0);
            $usageResponse = new LlmResponse(
                content: '',
                tokensIn: $tokensIn,
                tokensOut: 0,
                latencyMs: $latencyMs,
                model: self::EMBEDDING_MODEL,
                provider: 'openai',
                success: true,
            );

            return [$embeddings, $usageResponse];
        } catch (\Throwable $e) {
            Log::error('ai.embedding.api_error', ['error' => $e->getMessage()]);
            return [array_fill(0, count($inputs), []), null];
        }
    }
}
