<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Domain\Ai\Knowledge\RetrievalService;
use Illuminate\Support\Facades\Log;

/**
 * Agent tool: search the tenant's knowledge base for FAQ-style answers.
 *
 * Backed by the existing EmbeddingService + RetrievalService (no changes needed there).
 */
final class SearchKnowledgeTool implements AgentTool
{
    public function __construct(
        private readonly EmbeddingService  $embedding,
        private readonly RetrievalService  $retrieval,
    ) {}

    public function name(): string
    {
        return 'search_knowledge';
    }

    public function schema(): array
    {
        return [
            'name'        => 'search_knowledge',
            'description' => 'ابحث في قاعدة المعرفة الخاصة بالشركة عن إجابات لأسئلة العميل (ساعات العمل، الموقع، السياسات، إلخ). لا تستخدمها للبحث عن عقارات.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'سؤال العميل بالعربية'],
                ],
                'required'   => ['query'],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        if ($query === '') {
            return ['chunks' => [], 'count' => 0];
        }

        try {
            [$embeddings, $usage] = $this->embedding->embedQuery($query);
            if (empty($embeddings)) {
                return ['chunks' => [], 'count' => 0];
            }

            $chunks = $this->retrieval->retrieve($tenantId, $embeddings, topK: 5);

            $formatted = array_map(fn (array $c) => [
                'chunk_id' => $c['chunk_id'] ?? $c['id'] ?? null,
                'content'  => $c['content'] ?? $c['text'] ?? '',
                'score'    => round((float) ($c['score'] ?? 0), 3),
            ], $chunks);

            return ['chunks' => $formatted, 'count' => count($formatted)];
        } catch (\Throwable $e) {
            Log::error('agent.tool.search_knowledge.error', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
            return ['chunks' => [], 'count' => 0, 'error' => 'search_failed'];
        }
    }
}
