<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Domain\Ai\Knowledge\RetrievalService;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotContext;
use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;
use App\Models\AiCustomerProfile;
use App\Models\Message;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use Illuminate\Support\Facades\Log;

final class ContextBuilder
{
    private const VERBATIM_TURN_LIMIT = 16;    // max messages to include verbatim
    private const TOKEN_CHAR_RATIO    = 4;     // rough chars-per-token for budgeting
    private const MAX_CONTEXT_CHARS   = 12_000; // budget ceiling before trimming

    public function __construct(
        private readonly LlmDriverFactory $driverFactory,
        private readonly RetrievalService $retrieval,
        private readonly PropertySearchTool $propertyTool,
    ) {}

    /**
     * Build the full context for a bot turn.
     * Pass 1 (rewrite) happens here; retrieval and tool dispatch run in parallel (sequentially in PHP).
     */
    public function build(
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $customerPhone,
        WaAiConfig $config,
        Message $triggerMessage,
    ): BotContext {
        $aiState = WaConversationAiState::where('conversation_id', $conversationId)->first();

        // Load recent messages (after summary watermark)
        $recentMessages = $this->loadRecentMessages($conversationId, $tenantId, $aiState);

        // Build conversation snippet for Pass 1
        $conversationSnippet = $this->buildConversationSnippet($recentMessages, $triggerMessage);

        // Pass 1: rewrite query + classify
        [$standaloneQuery, $intent, $difficulty] = $this->runRewritePass(
            $tenantId,
            $config,
            $conversationSnippet,
            $triggerMessage->content ?? ''
        );

        // Load customer profile
        $profile = AiCustomerProfile::where('user_id', $tenantId)
            ->where('phone', $customerPhone)
            ->first()?->toArray();

        // Embed query and retrieve KB chunks
        $kbChunks = [];
        $propertyResult = null;

        if (in_array($intent, ['property_search', 'pricing', 'viewing', 'general'], true)) {
            try {
                $embeddingService = app(\App\Domain\Ai\Knowledge\EmbeddingService::class);
                $queryEmbedding = $embeddingService->embedQuery(
                    ArabicNormalizer::normalizeForSearch($standaloneQuery)
                );
                $kbChunks = $this->retrieval->retrieve($tenantId, $queryEmbedding, 5);
            } catch (\Throwable $e) {
                Log::warning('bot.context.retrieval_failed', ['error' => $e->getMessage()]);
            }
        }

        // Property search tool if inventory intent
        if (in_array($intent, ['property_search', 'pricing'], true)) {
            try {
                $toolParams = $this->extractPropertyParams($standaloneQuery, $aiState);
                $propertyResult = $this->propertyTool->execute($tenantId, $toolParams);
            } catch (\Throwable $e) {
                Log::warning('bot.context.property_tool_failed', ['error' => $e->getMessage()]);
            }
        }

        return new BotContext(
            tenantId: $tenantId,
            conversationId: $conversationId,
            waNumberId: $waNumberId,
            customerPhone: $customerPhone,
            config: $config,
            aiState: $aiState,
            recentMessages: $recentMessages,
            customerProfile: $profile,
            kbChunks: $kbChunks,
            propertySearchResult: $propertyResult,
            standaloneQuery: $standaloneQuery,
            intent: $intent,
            difficulty: $difficulty,
            inboundContent: (string) ($triggerMessage->content ?? ''),
        );
    }

    /**
     * Build message array for generation pass.
     * @return LlmMessage[]
     */
    public function buildGenerationMessages(BotContext $ctx, LlmMessage $systemPrompt): array
    {
        $messages = [$systemPrompt];

        // Inject structured memory if present
        if ($ctx->aiState !== null) {
            $memoryBlock = $this->buildMemoryBlock($ctx->aiState);
            if ($memoryBlock !== '') {
                $messages[] = LlmMessage::system('ذاكرة المحادثة:\n' . $memoryBlock);
            }
        }

        // Inject customer profile
        if (! empty($ctx->customerProfile)) {
            $profileBlock = $this->buildProfileBlock($ctx->customerProfile);
            if ($profileBlock !== '') {
                $messages[] = LlmMessage::system('معلومات العميل المحفوظة:\n' . $profileBlock);
            }
        }

        // Inject KB chunks
        if (! empty($ctx->kbChunks)) {
            $kbBlock = "مقتطفات من قاعدة المعرفة (استخدمها كمصدر وحيد للإجابة):\n\n";
            foreach ($ctx->kbChunks as $i => $chunk) {
                $kbBlock .= '[' . ($i + 1) . '] (مصدر: ' . ($chunk['source'] ?? 'KB') . ")\n" . $chunk['content'] . "\n\n";
            }
            $messages[] = LlmMessage::system($kbBlock);
        }

        // Inject property search results
        if (! empty($ctx->propertySearchResult['results'])) {
            $propBlock = "نتائج بحث العقارات (الأسعار هنا هي الأسعار الرسمية — لا تعدّل أي رقم):\n\n";
            foreach ($ctx->propertySearchResult['results'] as $prop) {
                $price = number_format((float) ($prop['price'] ?? 0));
                $propBlock .= sprintf(
                    "• *%s*\n  السعر: %s ريال | %s | %d غرفة | %s م²\n  العنوان: %s\n\n",
                    $prop['title'] ?? '',
                    $price,
                    $prop['purpose'] === 'rent' ? 'إيجار' : 'بيع',
                    (int) ($prop['bedrooms'] ?? 0),
                    $prop['area_sqm'] ?? '—',
                    $prop['address'] ?? ''
                );
            }
            if ($ctx->propertySearchResult['has_more'] ?? false) {
                $propBlock .= "(يوجد المزيد من العقارات — اعرض على العميل إمكانية الاطلاع على المزيد)\n";
            }
            $messages[] = LlmMessage::system($propBlock);
        } elseif ($ctx->intent === 'property_search' && isset($ctx->propertySearchResult)) {
            $messages[] = LlmMessage::system(
                'بحث العقارات: لم يُعثر على نتائج مطابقة للمعايير المطلوبة. لا تخترع عقارات.'
            );
        }

        // Inject conversation history (oldest to newest, token-budgeted)
        $historyMessages = $this->buildHistoryMessages($ctx->recentMessages, $ctx->inboundContent);
        foreach ($historyMessages as $msg) {
            $messages[] = $msg;
        }

        return $messages;
    }

    /** @return Message[] */
    private function loadRecentMessages(
        int $conversationId,
        int $tenantId,
        ?WaConversationAiState $aiState
    ): array {
        $query = Message::where('conversation_id', $conversationId)
            ->where('user_id', $tenantId)
            ->orderByDesc('id');

        if ($aiState?->summary_through_message_id) {
            $query->where('id', '>', $aiState->summary_through_message_id);
        }

        return $query
            ->limit(self::VERBATIM_TURN_LIMIT)
            ->get()
            ->sortBy('id')
            ->values()
            ->all();
    }

    private function buildConversationSnippet(array $messages, Message $trigger): string
    {
        $lines = [];
        foreach ($messages as $msg) {
            if ((int) $msg->id === (int) $trigger->id) { continue; }
            $role = $msg->direction === 'inbound' ? 'عميل' : 'موظف';
            $lines[] = $role . ': ' . ($msg->content ?? '');
        }
        $lines[] = 'عميل: ' . ($trigger->content ?? '');
        return implode("\n", $lines);
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function runRewritePass(
        int $tenantId,
        WaAiConfig $config,
        string $conversationSnippet,
        string $lastMessage
    ): array {
        try {
            $fastModel = env('OPENAI_FAST_MODEL', 'gpt-5-nano');
            $driver = $this->driverFactory->makeForTenant($tenantId);
            $persona = new PersonaBuilder();

            $request = new LlmRequest(
                messages: [
                    $persona->buildRewritePrompt(),
                    LlmMessage::user(
                        "السياق:\n{$conversationSnippet}\n\nالرسالة الأخيرة: {$lastMessage}"
                    ),
                ],
                model: $fastModel,
                maxTokens: 150,
                temperature: 0.1,
                jsonMode: true,
                timeoutSeconds: 10,
            );

            $response = $driver->complete($request);
            $data = json_decode($response->content, true);

            if (is_array($data)) {
                return [
                    (string) ($data['standalone_query'] ?? $lastMessage),
                    (string) ($data['intent'] ?? 'general'),
                    (string) ($data['difficulty'] ?? 'easy'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('bot.context.rewrite_failed', ['error' => $e->getMessage()]);
        }

        // Fallback: use raw message, detect intent heuristically
        $intent = $this->detectIntentHeuristic($lastMessage);
        return [$lastMessage, $intent, 'easy'];
    }

    private function detectIntentHeuristic(string $text): string
    {
        $lower = mb_strtolower($text);
        $propertyKeywords = ['عقار', 'شقة', 'فيلا', 'أرض', 'ابحث', 'ايجار', 'بيع', 'وحدة', 'غرفة'];
        foreach ($propertyKeywords as $kw) {
            if (str_contains($lower, $kw)) { return 'property_search'; }
        }
        $priceKeywords = ['سعر', 'كم', 'ريال', 'تكلفة', 'إيجار'];
        foreach ($priceKeywords as $kw) {
            if (str_contains($lower, $kw)) { return 'pricing'; }
        }
        return 'general';
    }

    /** @return array<string, mixed> */
    private function extractPropertyParams(string $query, ?WaConversationAiState $aiState): array
    {
        $params = [];
        // Extract from existing facts
        $facts = $aiState?->facts ?? [];
        if (! empty($facts['city'])) { $params['location'] = $facts['city']; }
        if (! empty($facts['district'])) { $params['location'] = $facts['district']; }
        if (! empty($facts['intent'])) { $params['purpose'] = $facts['intent'] === 'rent' ? 'rent' : 'sale'; }
        if (! empty($facts['bedrooms'])) { $params['bedrooms'] = (int) $facts['bedrooms']; }
        if (! empty($facts['budget_max'])) { $params['budget_max'] = (float) $facts['budget_max']; }

        // Extract from query text (simple heuristics)
        if (preg_match('/(\d+)\s*غرف/u', $query, $m)) { $params['bedrooms'] = (int) $m[1]; }
        if (str_contains($query, 'إيجار') || str_contains($query, 'ايجار')) { $params['purpose'] = 'rent'; }
        if (str_contains($query, 'بيع') || str_contains($query, 'للبيع')) { $params['purpose'] = 'sale'; }

        // Location: try to extract city/district name from query
        if (preg_match('/(?:في|بـ|ب)\s*([\p{Arabic}]+)/u', $query, $m)) {
            $params['location'] = $params['location'] ?? trim($m[1]);
        }
        if (preg_match('/حي\s*([\p{Arabic}\s]+)/u', $query, $m)) {
            $params['location'] = trim($m[1]);
        }

        return $params;
    }

    private function buildMemoryBlock(WaConversationAiState $state): string
    {
        $parts = [];
        if ($state->situation)   { $parts[] = "الوضع: {$state->situation}"; }
        if ($state->requirements){ $parts[] = "المتطلبات: {$state->requirements}"; }
        if ($state->commitments) { $parts[] = "الالتزامات المُتخذة: {$state->commitments}"; }
        if ($state->objections)  { $parts[] = "ما رفضه العميل: {$state->objections}"; }
        return implode("\n", $parts);
    }

    private function buildProfileBlock(array $profile): string
    {
        $parts = [];
        if (! empty($profile['name'])) { $parts[] = "الاسم: {$profile['name']}"; }
        $facts = $profile['durable_facts'] ?? [];
        if (! empty($facts['preferred_type'])) { $parts[] = "العقار المفضل: {$facts['preferred_type']}"; }
        if (! empty($facts['city'])) { $parts[] = "المدينة المفضلة: {$facts['city']}"; }
        return implode(" | ", $parts);
    }

    /** @return LlmMessage[] */
    private function buildHistoryMessages(array $messages, string $currentContent): array
    {
        $result = [];
        $totalChars = 0;

        // Newest-first for trimming, then reverse
        $reversed = array_reverse($messages);
        $included = [];
        foreach ($reversed as $msg) {
            $content = (string) ($msg->content ?? '');
            if ((string) $content === (string) $currentContent) { continue; } // skip trigger
            $totalChars += mb_strlen($content);
            if ($totalChars > self::MAX_CONTEXT_CHARS) { break; }
            $included[] = $msg;
        }

        foreach (array_reverse($included) as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';
            $result[] = new LlmMessage($role, (string) ($msg->content ?? ''));
        }

        // Add current inbound as last user message
        $result[] = LlmMessage::user($currentContent);

        return $result;
    }
}
