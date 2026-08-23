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
        $facts = $aiState?->facts ?? [];
        $isGreeting = $this->looksLikeGreeting($inboundRaw)
            && ! MessageFactExtractor::hasSearchSignals(MessageFactExtractor::extract([$inboundRaw]));

        if ($intent === 'general') {
            $quickExtract = MessageFactExtractor::extract([$inboundRaw]);
            if (MessageFactExtractor::hasSearchSignals($quickExtract)) {
                $intent = 'property_search';
            } elseif ($this->detectIntentHeuristic($inboundRaw) !== 'general') {
                $intent = $this->detectIntentHeuristic($inboundRaw);
            }
        }

        // Greetings / small-talk must never become pricing/search (e.g. "كيفكم" contains "كم").
        if ($isGreeting) {
            $intent = 'general';
            $standaloneQuery = $inboundRaw;
        }

        // Sticky search session: once criteria exist, short follow-ups stay in search
        // ("الرياض", "ارسلي التفاصيل") instead of drifting to general + invented inventory.
        if (SearchSession::shouldContinueSearch($facts, $inboundRaw, $isGreeting)) {
            $intent = 'property_search';
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

        // Property search whenever this turn is inventory-related OR an active search session.
        if (
            in_array($intent, ['property_search', 'pricing', 'viewing'], true)
            || SearchSession::shouldContinueSearch($facts, $inboundRaw, $isGreeting)
        ) {
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
            $results = $ctx->propertySearchResult['results'];
            $count   = count($results);
            $locationRelaxed = (bool) ($ctx->propertySearchResult['location_relaxed'] ?? false);
            $requestedLoc = trim((string) (
                $ctx->propertySearchResult['requested_location']
                ?? $ctx->propertySearchResult['requested_city']
                ?? $ctx->propertySearchResult['requested_district']
                ?? ''
            ));

            if ($locationRelaxed) {
                $where = $requestedLoc !== '' ? $requestedLoc : 'الموقع المطلوب';
                $propBlock = "تنبيه: البحث في «{$where}» لم يُرجع نتائج. النتائج أدناه من مواقع أخرى بنفس النوع/الميزانية تقريباً.\n"
                    . "يجب أن تبدأ ردك بـ: «ما لقيت في {$where} الحين، لكن عندي خيارات في مواقع ثانية:» ثم اعرض النتائج بصدق مع ذكر موقع كل عقار. الأسعار هنا رسمية — لا تعدّل أي رقم:\n\n";
            } else {
                $propBlock = "نتائج بحث العقارات: وُجدت {$count} نتيجة مطابقة — يجب عرضها للعميل الآن (الأسعار هنا هي الأسعار الرسمية — لا تعدّل أي رقم):\n\n";
            }
            foreach ($results as $prop) {
                $price = number_format((float) ($prop['price'] ?? 0));
                $beds  = $prop['bedrooms'];
                $bedsLabel = $beds === null || $beds === ''
                    ? '—'
                    : ((int) $beds) . ' غرفة';
                $propBlock .= sprintf(
                    "• [#%d] *%s*\n  السعر: %s ريال | %s | %s | %s م²\n  العنوان: %s\n\n",
                    (int) ($prop['id'] ?? 0),
                    $prop['title'] ?? '',
                    $price,
                    ($prop['purpose'] ?? '') === 'rent' ? 'إيجار' : 'بيع',
                    $bedsLabel,
                    $prop['area_sqm'] ?? '—',
                    $prop['address'] ?? ''
                );
            }
            if ($ctx->propertySearchResult['has_more'] ?? false) {
                $propBlock .= "(يوجد المزيد من العقارات — اعرض على العميل إمكانية الاطلاع على المزيد)\n";
            }
            $propBlock .= "مهم: ممنوع تقول \"ما لقيت نتائج\" طالما القائمة أعلاه غير فارغة.\n";
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
            $results = $ctx->propertySearchResult['results'];
            $wantsDetails = (bool) preg_match('/رابط|تفاصيل|الوحدة|هذي|هذه|نفس/u', $ctx->inboundContent);
            // Single hit always; or top hit when customer asks for the link/details.
            if (count($results) === 1 || $wantsDetails) {
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
                        $messages[] = LlmMessage::system(
                            $enrichment['links_text'] . "\nإذا طلب العميل الرابط، أرسل الرابط حرفياً من النص أعلاه."
                        );
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
            'ملحق', 'روف', 'قصر', 'وحدة', 'وحده',
            // Transaction keywords
            'إيجار', 'ايجار', 'للايجار', 'بيع', 'للبيع', 'شراء', 'تمليك',
            // Feature keywords
            'غرفة', 'غرف',
        ];
        foreach ($propertyKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) { return 'property_search'; }
        }

        // "دور" (floor) as a whole token only — avoid matching inside unrelated words
        if (preg_match('/(?:^|[^\p{Arabic}])دور(?:[^\p{Arabic}]|$)/u', $text)) {
            return 'property_search';
        }

        // Pricing: never match bare "كم" (false-positive inside كيفكم / كيفك)
        if (preg_match('/(?:سعر|تكلفة|ريال|بكم|كم\s+(?:السعر|سعر|تكلف))/u', $text)) {
            return 'pricing';
        }

        return 'general';
    }

    /**
     * Detect casual greetings / small-talk that must not trigger inventory search.
     */
    private function looksLikeGreeting(string $text): bool
    {
        $t = trim($text);
        if ($t === '' || mb_strlen($t) > 80) {
            return false;
        }

        // If the message already has inventory criteria, it is not "just" a greeting
        if (MessageFactExtractor::hasSearchSignals(MessageFactExtractor::extract([$t]))) {
            return false;
        }

        $markers = [
            'حياك', 'حياكم', 'هلا', 'هلّا', 'السلام', 'مرحبا', 'مرحباً', 'اهلا', 'أهلا',
            'صباح الخير', 'مساء الخير', 'كيفك', 'كيفكم', 'كيف الحال', 'وش اخبارك',
            'وش الاخبار', 'وش الأخبار', 'الاخبار', 'الأخبار', 'أخبارك',
        ];
        foreach ($markers as $m) {
            if (mb_strpos($t, $m) !== false) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function extractPropertyParams(string $query, ?WaConversationAiState $aiState): array
    {
        $params = [];
        $facts  = $aiState?->facts ?? [];

        // Read structured facts already persisted (or just merged by BotOrchestrator).
        // Prefer "district + city" together so LocationResolver does not map a bare
        // district (e.g. حي النخيل) to the wrong city.
        $city = trim((string) ($facts['city'] ?? ''));
        $district = trim((string) ($facts['district'] ?? ''));
        if ($district !== '' && $city !== '') {
            $params['location'] = $district . ' ' . $city;
        } elseif ($district !== '') {
            $params['location'] = $district;
        } elseif ($city !== '') {
            $params['location'] = $city;
        }
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

        // Location fallback from query — only "في X" or word-initial بـ prefix (بجدة),
        // never a ب letter in the middle of a word (الاخبار).
        if (empty($params['location'])) {
            $candidate = null;
            if (preg_match('/(?:^|[^\p{Arabic}])في\s+([\p{Arabic}]+)/u', $query, $m)) {
                $candidate = trim($m[1]);
            } elseif (preg_match('/(?:^|[^\p{Arabic}])ب([\p{Arabic}]{2,})/u', $query, $m)) {
                $candidate = trim($m[1]);
            }

            if ($candidate !== null) {
                $nonLocations = [
                    'ميزانية', 'ميزانيت', 'سعر', 'حدود', 'اي', 'أي', 'مكان',
                    'المملكة', 'كل', 'اي مكان', 'أي مكان',
                ];
                // بـ-prefix fallback is only safe for known city aliases — never verbs
                // like "بيستأجر" → "يستأجر" as a fake location.
                $knownCities = [
                    'الرياض', 'رياض', 'جدة', 'جده', 'مكة', 'مكه', 'الدمام', 'دمام',
                    'الخبر', 'المدينة', 'الطائف', 'بريدة', 'عنيزة', 'البكيرية',
                    'القصيم', 'تبوك', 'حائل', 'أبها', 'ابها', 'نجران', 'جازان', 'ينبع',
                ];
                $fromBaPrefix = (bool) preg_match('/(?:^|[^\p{Arabic}])ب' . preg_quote($candidate, '/') . '/u', $query);
                if (
                    ! in_array($candidate, $nonLocations, true)
                    && ! preg_match('/^(ميزانية|ميزانيت|سعر|حدود)/u', $candidate)
                    && (! $fromBaPrefix || in_array($candidate, $knownCities, true))
                ) {
                    $params['location'] = $candidate;
                }
            }
        }
        // Require whitespace after "حي" so "حياك" never becomes a district
        if (preg_match('/(?:^|[^\p{Arabic}])حي\s+([\p{Arabic}][\p{Arabic}\s]{0,40}?)(?:\s*(?:و|في|،|,|\.|$))/u', $query, $m)) {
            $district = trim($m[1]);
            if ($district !== '' && ! preg_match('/^(ميزانية|ميزانيت|سعر)/u', $district)) {
                $params['location'] = 'حي ' . $district;
            }
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
