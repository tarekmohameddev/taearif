<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Domain\Ai\Knowledge\RetrievalService;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotContext;
use App\Domain\Communication\WhatsApp\Bot\ListingLinkResolver;
use App\Domain\Communication\WhatsApp\Bot\MessageFactExtractor;
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
        private readonly UsageRecorder $usageRecorder,
        private readonly ListingLinkResolver $linkResolver,
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
            $triggerMessage->content ?? '',
            $conversationId,
        );

        // Deterministic intent override: if the LLM returned 'general' but the message
        // contains strong search signals (type/budget/bedrooms), force property_search.
        $inboundRaw = (string) ($triggerMessage->content ?? '');
        if ($intent === 'general') {
            $quickExtract = MessageFactExtractor::extract([$inboundRaw]);
            if (MessageFactExtractor::hasSearchSignals($quickExtract)) {
                $intent = 'property_search';
            } elseif ($this->detectIntentHeuristic($inboundRaw) !== 'general') {
                $intent = $this->detectIntentHeuristic($inboundRaw);
            }
        }

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
                [$queryEmbedding, $embedResponse] = $embeddingService->embedQueryWithUsage(
                    ArabicNormalizer::normalizeForSearch($standaloneQuery)
                );
                if ($embedResponse !== null) {
                    $this->usageRecorder->record($tenantId, 'embed', $embedResponse, $conversationId);
                }
                $kbChunks = $this->retrieval->retrieve($tenantId, $queryEmbedding, 5);
            } catch (\Throwable $e) {
                Log::warning('bot.context.retrieval_failed', ['error' => $e->getMessage()]);
            }
        }

        // Property search tool if inventory intent (viewing also needs property context)
        if (in_array($intent, ['property_search', 'pricing', 'viewing'], true)) {
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

        // Inject focused-property external links and FAQ answer
        $focusedPropertyId = (int) ($ctx->aiState?->facts['focused_property_id'] ?? 0);
        if ($focusedPropertyId > 0) {
            $enrichment = $this->linkResolver->resolve(
                $focusedPropertyId,
                $ctx->standaloneQuery
            );
            if ($enrichment['faq_answer'] !== null) {
                $messages[] = LlmMessage::system(
                    "إجابة FAQ للعقار المحدد:\n" . $enrichment['faq_answer']
                );
            }
            if ($enrichment['links_text'] !== '') {
                $messages[] = LlmMessage::system($enrichment['links_text']);
            }
        } elseif (! empty($ctx->propertySearchResult['results'])) {
            // Auto-focus on the single result when exactly one property returned
            $results = $ctx->propertySearchResult['results'];
            if (count($results) === 1) {
                $pid = (int) ($results[0]['id'] ?? 0);
                if ($pid > 0) {
                    $enrichment = $this->linkResolver->resolve(
                        $pid,
                        $ctx->standaloneQuery,
                        (string) ($results[0]['title'] ?? '')
                    );
                    if ($enrichment['faq_answer'] !== null) {
                        $messages[] = LlmMessage::system(
                            "إجابة FAQ للعقار المحدد:\n" . $enrichment['faq_answer']
                        );
                    }
                    if ($enrichment['links_text'] !== '') {
                        $messages[] = LlmMessage::system($enrichment['links_text']);
                    }
                }
            }
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
        string $lastMessage,
        ?int $conversationId = null,
    ): array {
        try {
            $fastModel = (string) config('openai.fast_model', 'gpt-5-nano');
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

            // Record Pass 1 usage in ai_usage_logs
            $this->usageRecorder->record($tenantId, 'rewrite', $response, $conversationId);

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
        // Budget mention alone is a strong search signal
        if (preg_match('/\d+\s*مليون|\d+\s*(?:ألف|الف)|ميزاني|ميزانيت/u', $text)) {
            return 'property_search';
        }

        $propertyKeywords = [
            // Verbs of seeking/wanting
            'بدور', 'ادور', 'أبغى', 'ابغى', 'أبي', 'ابي', 'تدور', 'ابحث', 'أبحث',
            'مطلوب', 'عندكم', 'عندك',
            // Property types
            'عقار', 'شقة', 'شقه', 'فيلا', 'فله', 'فلة', 'أرض', 'ارض',
            'عمارة', 'دوبلكس', 'استراحة', 'استراحه', 'مزرعة', 'مزرعه',
            'دور', 'ملحق', 'روف', 'قصر', 'وحدة', 'وحده',
            // Transaction keywords
            'إيجار', 'ايجار', 'للايجار', 'بيع', 'للبيع', 'شراء', 'تمليك',
            // Feature keywords
            'غرفة', 'غرف',
        ];
        foreach ($propertyKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) { return 'property_search'; }
        }

        $priceKeywords = ['سعر', 'كم', 'ريال', 'تكلفة'];
        foreach ($priceKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) { return 'pricing'; }
        }

        return 'general';
    }

    /** @return array<string, mixed> */
    private function extractPropertyParams(string $query, ?WaConversationAiState $aiState): array
    {
        $params = [];
        $facts  = $aiState?->facts ?? [];

        // Read structured facts already persisted (or just merged by BotOrchestrator)
        if (! empty($facts['city']))       { $params['location']   = $facts['city']; }
        if (! empty($facts['district']))   { $params['location']   = $facts['district']; }
        if (! empty($facts['intent']))     { $params['purpose']    = $facts['intent'] === 'rent' ? 'rent' : 'sale'; }
        if (! empty($facts['bedrooms']))   { $params['bedrooms']   = (int) $facts['bedrooms']; }
        if (! empty($facts['budget_max'])) { $params['budget_max'] = (float) $facts['budget_max']; }
        if (! empty($facts['budget_min'])) { $params['budget_min'] = (float) $facts['budget_min']; }

        // Resolve property type from facts → pass as property_type token so
        // PropertySearchTool::buildUnifiedRequest() maps it to category IDs
        if (! empty($facts['type'])) {
            $params['property_type'] = $facts['type'];
        }

        // Fallback heuristics from query text (only fill gaps not already in facts)
        if (empty($params['bedrooms']) && preg_match('/(\d+)\s*غرف/u', $query, $m)) {
            $params['bedrooms'] = (int) $m[1];
        }
        if (empty($params['purpose'])) {
            if (str_contains($query, 'إيجار') || str_contains($query, 'ايجار')) { $params['purpose'] = 'rent'; }
            elseif (str_contains($query, 'بيع') || str_contains($query, 'للبيع')) { $params['purpose'] = 'sale'; }
        }

        // Location fallback from query
        if (empty($params['location'])) {
            if (preg_match('/(?:في|بـ|ب)\s*([\p{Arabic}]+)/u', $query, $m)) {
                $params['location'] = trim($m[1]);
            }
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
            $content = (string) ($msg->content ?? '');
            if ($msg->direction === 'inbound') {
                $result[] = LlmMessage::user($content);
            } else {
                // Distinguish bot replies (source=ai) from human agent replies so the
                // model knows a human has already handled part of this conversation.
                $meta   = is_array($msg->meta) ? $msg->meta : [];
                $source = $meta['source'] ?? null;

                if ($source === 'evolution_agent' || $source === 'whatsapp_echo') {
                    // Human agent message — present as system note so the model understands
                    // it cannot claim credit for what the agent said.
                    $result[] = LlmMessage::system("[رد موظف بشري سابق]: {$content}");
                } else {
                    // Bot-generated message or unknown — still show as assistant
                    $result[] = new LlmMessage('assistant', $content);
                }
            }
        }

        // Add current inbound as last user message
        $result[] = LlmMessage::user($currentContent);

        return $result;
    }
}
