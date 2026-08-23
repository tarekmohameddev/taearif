<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Knowledge\RetrievalService;
use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotContext;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotReply;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotTurnResult;
use App\Domain\Communication\WhatsApp\Services\WaPricingResolver;
use App\Domain\Communication\WhatsApp\Bot\Jobs\SummarizeConversationJob;
use App\Domain\Communication\WhatsApp\Bot\MessageFactExtractor;
use App\Models\AiUsageLog;
use App\Models\BotUnansweredQuestion;
use App\Models\Message;
use App\Models\ShadowBotDraft;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use App\Models\WaNumber;
use Illuminate\Support\Facades\Log;

/**
 * Central bot orchestrator — Entry point for all bot turns.
 *
 * Pipeline:
 * 1. Load & validate configuration + guards
 * 2. Compliance check
 * 3. Build context (Pass 1: rewrite + classify)
 * 4. Generate reply (Pass 2: main generation)
 * 5. Grounding verification (Pass 3: deterministic)
 * 6. Decide: deliver | shadow draft | handoff
 * 7. Post-turn: update state, dispatch summarize job
 *
 * Sandbox mode (handleSandbox):
 * - Skips loop-guard, business-hours gate, delivery (no real send / no credits).
 * - Skips shadow_bot_drafts insert and bot_unanswered_questions to avoid polluting metrics.
 * - Everything else (compliance, grounding, handoff, facts, disclosure, summarize job) runs normally.
 */
final class BotOrchestrator
{
    private const MIN_TURNS_BEFORE_SUMMARY = 8;
    private const LOOP_GUARD_KEY = 'bot.loop.conv.';
    private const LOOP_GUARD_MAX = 3;  // max bot replies per minute per conversation

    public function __construct(
        private readonly LlmDriverFactory $driverFactory,
        private readonly UsageRecorder $usageRecorder,
        private readonly ContextBuilder $contextBuilder,
        private readonly PersonaBuilder $personaBuilder,
        private readonly GroundingVerifier $groundingVerifier,
        private readonly ComplianceService $complianceService,
        private readonly HandoffService $handoffService,
        private readonly DeliveryService $deliveryService,
        private readonly RelevanceGate $relevanceGate,
        private readonly SlotFillingPolicy $slotFillingPolicy,
        private readonly RetrievalService $retrievalService,
        private readonly CreditService $creditService,
        private readonly WaPricingResolver $pricingResolver,
    ) {}

    /**
     * Handle one inbound message for a bot-enabled number (production path).
     * Callers in AutomationEngine and TranscribeAudio remain unchanged.
     */
    public function handle(
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $customerPhone,
        Message $triggerMessage,
    ): void {
        $this->run(
            tenantId: $tenantId,
            conversationId: $conversationId,
            waNumberId: $waNumberId,
            customerPhone: $customerPhone,
            triggerMessage: $triggerMessage,
            sandbox: false,
        );
    }

    /**
     * Handle one turn in sandbox (dry-run) mode.
     *
     * Differences from production:
     * - Loop-guard and business-hours checks are skipped (noted in trace).
     * - autonomy_level = 'off' is treated as 'autonomous' so untoggled configs can still be tested.
     * - No DeliveryService call — the reply is returned in the result for the caller to persist.
     * - No shadow_bot_drafts row created.
     * - No bot_unanswered_questions row created.
     * - UsageRecorder uses pass_type='simulate'.
     */
    public function handleSandbox(
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $customerPhone,
        Message $triggerMessage,
    ): BotTurnResult {
        return $this->run(
            tenantId: $tenantId,
            conversationId: $conversationId,
            waNumberId: $waNumberId,
            customerPhone: $customerPhone,
            triggerMessage: $triggerMessage,
            sandbox: true,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private implementation
    // ─────────────────────────────────────────────────────────────────────────

    private function run(
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $customerPhone,
        Message $triggerMessage,
        bool $sandbox,
    ): BotTurnResult {
        $startMs = (int) round(microtime(true) * 1000);
        $trace = [];

        if (! $sandbox) {
            Log::info('bot.orchestrator.start', [
                'conversation_id' => $conversationId,
                'tenant' => $tenantId,
                'wa_number_id' => $waNumberId,
                'message_id' => $triggerMessage->id,
            ]);
        }

        // ─── Guard: loop detection ──────────────────────────────────────────
        if (! $sandbox && $this->isLooping($conversationId)) {
            Log::warning('bot.orchestrator.loop_detected', [
                'conversation_id' => $conversationId,
                'tenant' => $tenantId,
            ]);
            return BotTurnResult::skipped('loop_detected');
        }
        if ($sandbox) {
            $trace[] = 'loop_guard: skipped (sandbox)';
        }

        // ─── Load config ────────────────────────────────────────────────────
        $config = WaAiConfig::with('excludedPhones')
            ->where('user_id', $tenantId)
            ->where('wa_number_id', $waNumberId)
            ->first();

        if ($config === null || ! $config->enabled) {
            return BotTurnResult::skipped('no_config_or_disabled', $trace);
        }

        // ─── Guard: excluded customer number ────────────────────────────────
        $excludedPhones = $config->excludedPhones->pluck('phone')->all();
        if ($excludedPhones !== [] && in_array($customerPhone, $excludedPhones, true)) {
            return BotTurnResult::skipped('excluded_number', $trace);
        }

        // ─── Business hours check ───────────────────────────────────────────
        if (! $sandbox && ! $this->isWithinBusinessHours($config)) {
            Log::debug('bot.orchestrator.outside_hours', ['conversation_id' => $conversationId]);
            return BotTurnResult::skipped('outside_business_hours', $trace);
        }
        if ($sandbox) {
            $outsideHours = ! $this->isWithinBusinessHours($config);
            $trace[] = 'business_hours: ' . ($outsideHours ? 'outside (sandbox bypass)' : 'open');
        }

        // ─── Load/create AI state ───────────────────────────────────────────
        $aiState = WaConversationAiState::firstOrCreate(
            ['conversation_id' => $conversationId],
            ['user_id' => $tenantId, 'facts' => [], 'opt_out_status' => 'active', 'disclosed_as_assistant' => false]
        );

        // ─── Guard: paused / opted-out ──────────────────────────────────────
        if ($aiState->isOptedOut()) {
            return BotTurnResult::skipped('opted_out', $trace);
        }
        if ($aiState->isBotPaused()) {
            return BotTurnResult::skipped('bot_paused', $trace);
        }

        // ─── Guard: pending transcription ───────────────────────────────────
        if ($this->isPendingTranscription($triggerMessage)) {
            Log::debug('bot.orchestrator.pending_transcription', ['message_id' => $triggerMessage->id]);
            return BotTurnResult::skipped('pending_transcription', $trace);
        }

        $inboundText = (string) ($triggerMessage->content ?? '');

        // ─── Image / media message handling ─────────────────────────────────
        $messageMeta = is_array($triggerMessage->meta) ? $triggerMessage->meta : [];
        $messageType = $messageMeta['type'] ?? 'text';
        if (in_array($messageType, ['image', 'video', 'document'], true) && trim($inboundText) === '') {
            $ackText = 'وصلت الصورة/الملف. موظفنا سيراجعها قريباً.';
            $trace[] = 'media_message: acknowledged';
            $this->handoffService->pauseBot($aiState, 'media_message_needs_review');
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(reply: $ackText, usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: 'media_message_needs_review', factsUpdate: [], nextQuestion: null),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'handoff', replyText: $ackText, botReply: null, groundingResult: null,
                styleResult: null, intent: 'general', difficulty: 'easy', kbChunksUsed: 0,
                propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($ackText) : [],
                trace: $trace, factsUpdated: [],
            );
        }

        if (trim($inboundText) === '') {
            return BotTurnResult::skipped('empty_message', $trace);
        }

        // ─── Relevance gate ──────────────────────────────────────────────────
        if (! $sandbox) {
            $relevance = $this->relevanceGate->check($inboundText);
            $trace[] = 'relevance: ' . ($relevance['relevant'] ? 'ok' : 'DROPPED (' . $relevance['reason'] . ')');
            if (! $relevance['relevant']) {
                return BotTurnResult::skipped('off_topic:' . $relevance['reason'], $trace);
            }
        } else {
            $trace[] = 'relevance: skipped (sandbox)';
        }

        // ─── Compliance check ────────────────────────────────────────────────
        // First contact = no prior bot reply. Token counters stay 0 across slot-fill
        // turns, so using them wrongly injected disclosure mid-conversation.
        $isFirstContact  = $aiState->last_bot_reply_at === null;
        $discloseEnabled = (bool) ($config->disclose_as_assistant ?? false);
        $compliance = $this->complianceService->check(
            $inboundText,
            $aiState,
            $isFirstContact,
            $discloseEnabled,
        );
        $trace[] = 'compliance: ' . $compliance['action']
            . ($discloseEnabled ? '' : ' (disclosure disabled)');

        if ($compliance['action'] === 'opt_out') {
            $aiState->update(['opt_out_status' => 'opted_out']);
            $replyText = $compliance['message'] ?? 'تم تسجيل طلبك.';
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(
                        reply: $replyText,
                        usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: null, factsUpdate: [], nextQuestion: null
                    ),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'opt_out',
                replyText: $replyText,
                botReply: null,
                groundingResult: null,
                styleResult: null,
                intent: 'general',
                difficulty: 'easy',
                kbChunksUsed: 0,
                propertiesFound: 0,
                tokensIn: 0,
                tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($replyText) : [],
                trace: $trace,
                factsUpdated: [],
            );
        }

        if ($compliance['action'] === 'handoff') {
            $this->handoffService->pauseBot($aiState, $compliance['reason'] ?? 'compliance');
            $replyText = $compliance['message'] ?? '';
            if (! $sandbox && $replyText !== '') {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(
                        reply: $replyText,
                        usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: $compliance['reason'] ?? null, factsUpdate: [], nextQuestion: null
                    ),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'handoff',
                replyText: $replyText ?: null,
                botReply: null,
                groundingResult: null,
                styleResult: null,
                intent: 'general',
                difficulty: 'easy',
                kbChunksUsed: 0,
                propertiesFound: 0,
                tokensIn: 0,
                tokensOut: 0,
                segments: ($sandbox && $replyText !== '') ? $this->deliveryService->prepareSegments($replyText) : [],
                trace: $trace,
                factsUpdated: [],
            );
        }

        // ─── Human request keyword ───────────────────────────────────────────
        if ($this->complianceService->isHumanRequestKeyword($inboundText)) {
            $trace[] = 'human_request: true';
            $this->handoffService->pauseBot($aiState, 'customer_requested_human');
            $replyText = 'تمام! سيتواصل معك أحد موظفينا قريباً.';
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(
                        reply: $replyText,
                        usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: 'customer_requested_human', factsUpdate: [], nextQuestion: null
                    ),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'handoff',
                replyText: $replyText,
                botReply: null,
                groundingResult: null,
                styleResult: null,
                intent: 'general',
                difficulty: 'easy',
                kbChunksUsed: 0,
                propertiesFound: 0,
                tokensIn: 0,
                tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($replyText) : [],
                trace: $trace,
                factsUpdated: [],
            );
        }

        // ─── Frustration detection ────────────────────────────────────────────
        if ($this->handoffService->detectFrustration($inboundText)) {
            $trace[] = 'frustration: detected';
            $this->handoffService->pauseBot($aiState, 'customer_frustration');
            $replyText = 'نأسف على هذه التجربة. سيتواصل معك أحد موظفينا مباشرة.';
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(reply: $replyText, usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: 'customer_frustration', factsUpdate: [], nextQuestion: null),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'handoff', replyText: $replyText, botReply: null, groundingResult: null,
                styleResult: null, intent: 'general', difficulty: 'easy', kbChunksUsed: 0,
                propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($replyText) : [],
                trace: $trace, factsUpdated: [],
            );
        }

        // ─── Monthly budget guard ─────────────────────────────────────────────
        $monthlyTokenLimit = (int) ($config->monthly_token_budget ?? 0);
        if ($monthlyTokenLimit > 0 && $this->usageRecorder->exceedsBudget($tenantId, $monthlyTokenLimit)) {
            $trace[] = 'budget: EXCEEDED (limit=' . $monthlyTokenLimit . ')';
            $replyText = 'نعتذر، تم استنفاد حصة الردود الآلية لهذا الشهر. سيتواصل معك فريقنا قريباً.';
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(reply: $replyText, usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: 'monthly_budget_exceeded', factsUpdate: [], nextQuestion: null),
                    ['to' => $customerPhone]
                );
            }
            return $this->makeResult(
                outcome: 'handoff', replyText: $replyText, botReply: null, groundingResult: null,
                styleResult: null, intent: 'general', difficulty: 'easy', kbChunksUsed: 0,
                propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($replyText) : [],
                trace: $trace, factsUpdated: [],
            );
        }

        // ─── Credit guard ─────────────────────────────────────────────────────
        // Check before generation so we don't waste LLM tokens when the tenant has run out.
        if (! $sandbox && $this->pricingResolver->isAiBotBillable()) {
            $creditsNeeded = $this->pricingResolver->creditsForAiReply();
            if ($creditsNeeded > 0 && ! $this->creditService->hasSufficientCredits($tenantId, $creditsNeeded)) {
                $trace[] = 'credits: INSUFFICIENT';
                $replyText = 'نعتذر، نفد رصيدك المخصص للردود الآلية. يرجى التواصل مع إدارة الحساب.';
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(reply: $replyText, usedSources: [], confidence: 100, needsHuman: false,
                        handoffReason: 'insufficient_credits', factsUpdate: [], nextQuestion: null),
                    ['to' => $customerPhone]
                );
                return $this->makeResult(
                    outcome: 'handoff', replyText: $replyText, botReply: null, groundingResult: null,
                    styleResult: null, intent: 'general', difficulty: 'easy', kbChunksUsed: 0,
                    propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                    segments: [],
                    trace: $trace, factsUpdated: [],
                );
            }
        }

        // ─── Deterministic fact extraction ───────────────────────────────────
        // Extract budget / type / location / bedrooms from the inbound text BEFORE
        // context building so that slot-fill and search see them on this very turn.
        $extractedFacts = MessageFactExtractor::extract([$inboundText]);
        $currentFacts   = $aiState->facts ?? [];
        // Merge with sticky type/intent: follow-up questions must not overwrite an
        // active search session unless the customer clearly revises criteria.
        $mergedFacts = $this->mergeExtractedFacts($currentFacts, $extractedFacts, $inboundText);

        // Clear inherited false-positive type=دور (from older "بدور" substring bugs)
        if (
            ($mergedFacts['type'] ?? null) === 'دور'
            && ($extractedFacts['type'] ?? null) !== 'دور'
            && ! preg_match('/(?:^|[^\p{Arabic}])دور(?:[^\p{Arabic}]|$)/u', $inboundText)
        ) {
            unset($mergedFacts['type']);
        }

        if (MessageFactExtractor::hasSearchSignals($mergedFacts) || SearchSession::isActive($mergedFacts)) {
            $mergedFacts = SearchSession::markActive($mergedFacts);
        }

        if ($mergedFacts !== $currentFacts) {
            $aiState->update(['facts' => $mergedFacts]);
            $aiState->refresh();
        }
        $trace[] = empty($extractedFacts)
            ? 'fact_extract: none'
            : 'fact_extract: ' . implode(', ', array_keys($extractedFacts));
        if (SearchSession::isActive($aiState->facts ?? [])) {
            $trace[] = 'search_session: active';
        }

        // ─── Build context (Pass 1 inside) ───────────────────────────────────
        $context = $this->contextBuilder->build(
            $tenantId, $conversationId, $waNumberId,
            $customerPhone, $config, $triggerMessage
        );
        $trace[] = 'intent: ' . $context->intent . ' | difficulty: ' . $context->difficulty;
        $trace[] = 'kb_chunks: ' . count($context->kbChunks) . ' | properties: ' . count($context->propertySearchResult['results'] ?? []);

        // ─── Focused-property tracking ────────────────────────────────────────
        // When exactly one property is returned, persist it as the focused property
        // so subsequent turns can enrich replies with external links and per-property FAQ.
        // Reload facts from DB after BotOrchestrator may have merged extracted facts above.
        $facts = $aiState->facts ?? [];
        $propertyResults = $context->propertySearchResult['results'] ?? [];
        if (count($propertyResults) === 1) {
            $focusId = (int) ($propertyResults[0]['id'] ?? 0);
            if ($focusId > 0 && ($facts['focused_property_id'] ?? 0) !== $focusId) {
                $facts['focused_property_id'] = $focusId;
                $aiState->update(['facts' => $facts]);
                $trace[] = 'focused_property: ' . $focusId;
            }
        } elseif (count($propertyResults) > 1) {
            // Multiple results → clear focus
            if (isset($facts['focused_property_id'])) {
                unset($facts['focused_property_id']);
                $aiState->update(['facts' => $facts]);
                $trace[] = 'focused_property: cleared (multiple results)';
            }
        }

        // ─── Track failed turns without resolution ───────────────────────────
        $failedTurns = (int) ($facts['_failed_turns'] ?? 0);

        // ─── Exact-reply cache (deterministic shortcut) ──────────────────────
        // Check if the KB has an exact match for the normalized query before burning tokens.
        $exactHit = $this->checkExactReplyCache($tenantId, $context->standaloneQuery ?: $inboundText);
        if ($exactHit !== null) {
            $trace[] = 'exact_cache: HIT';
            $exactReply = new BotReply(
                reply: $exactHit,
                usedSources: ['exact_cache'],
                confidence: 95,
                needsHuman: false,
                handoffReason: null,
                factsUpdate: [],
                nextQuestion: null,
            );
            $this->deliverAndClose(
                $config, $aiState, $context, $exactReply, $triggerMessage,
                $tenantId, $conversationId, $waNumberId, $customerPhone,
                $sandbox, $facts, 0, 0, $trace,
            );
            return $this->makeResult(
                outcome: 'replied', replyText: $exactHit, botReply: $exactReply, groundingResult: null,
                styleResult: null, intent: $context->intent, difficulty: $context->difficulty,
                kbChunksUsed: 0, propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($exactHit) : [],
                trace: $trace, factsUpdated: [],
            );
        }
        $trace[] = 'exact_cache: miss';

        // ─── Property search clarification passthrough ────────────────────────
        // When the location could not be resolved and the search tool returned a
        // clarification question, deliver it directly without spending tokens on
        // full generation — but ONLY when the customer actually asked a search-like
        // question. Greetings must never become "في أي مدينة تبحث؟".
        $searchClarification = $context->propertySearchResult['clarification_needed'] ?? false;
        if ($searchClarification && !empty($context->propertySearchResult['clarification_question'])) {
            $extractedNow = MessageFactExtractor::extract([$inboundText]);
            $hasSearchSignals = MessageFactExtractor::hasSearchSignals($extractedNow)
                || ! empty($facts['type'])
                || ! empty($facts['budget_max'])
                || ! empty($facts['city'])
                || ! empty($facts['district']);

            if (! $hasSearchSignals) {
                $trace[] = 'search_clarification: skipped (no search signals)';
            } else {
                $clarifyQ = (string) $context->propertySearchResult['clarification_question'];
                $trace[] = 'search_clarification: ' . $clarifyQ;
                $clarifyReply = new BotReply(
                    reply: $clarifyQ,
                    usedSources: [],
                    confidence: 100,
                    needsHuman: false,
                    handoffReason: null,
                    factsUpdate: [],
                    nextQuestion: null,
                );
                if (! $sandbox) {
                    $this->deliveryService->deliver(
                        $tenantId, $conversationId, $waNumberId, $customerPhone,
                        $clarifyReply, ['to' => $customerPhone]
                    );
                }
                $aiState->update(['last_bot_reply_at' => now()]);
                return $this->makeResult(
                    outcome: 'replied', replyText: $clarifyQ, botReply: $clarifyReply, groundingResult: null,
                    styleResult: null, intent: $context->intent, difficulty: $context->difficulty,
                    kbChunksUsed: 0, propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                    segments: $sandbox ? $this->deliveryService->prepareSegments($clarifyQ) : [],
                    trace: $trace, factsUpdated: [],
                );
            }
        }

        // ─── Slot-filling policy ─────────────────────────────────────────────
        // Ask critical slots (city / budget) BEFORE presenting inventory — even if a
        // broad unscoped search already returned rows (otherwise Jeddah leaks in
        // before the customer said "الرياض").
        $nextSlot     = $this->slotFillingPolicy->nextSlot($facts, $context->intent);
        $nextQuestion = $nextSlot['question'] ?? null;
        $hasResults   = ! empty($context->propertySearchResult['results'] ?? []);
        $askingCriticalSlot = $nextQuestion !== null && (
            str_contains($nextQuestion, 'مدينة')
            || str_contains($nextQuestion, 'ميزاني')
        );
        if ($nextQuestion !== null && ($askingCriticalSlot || ! $hasResults)) {
            $trace[] = 'slot_fill: asking question' . ($nextSlot !== null ? ' (' . $nextSlot['slot'] . ')' : '');
            $questionsAsked = (int) ($facts['_questions_asked'] ?? 0) + 1;
            $askedSlots = array_values(array_unique(array_merge(
                (array) ($facts['_asked_slots'] ?? []),
                $nextSlot !== null ? [$nextSlot['slot']] : []
            )));
            $aiState->update(['facts' => array_merge($facts, [
                '_questions_asked' => $questionsAsked,
                '_asked_slots'     => $askedSlots,
            ])]);

            // If disclosure is enabled, attach it to the FIRST bot reply only
            // (including slot-fill) — never defer it to a later generated turn.
            [$nextQuestion, $disclosureInjected] = $this->maybePrefixDisclosure(
                $nextQuestion,
                $config,
                $aiState,
                $discloseEnabled,
            );
            if ($disclosureInjected) {
                $trace[] = 'disclosure: injected (slot_fill)';
            }

            $slotReply = new BotReply(
                reply: $nextQuestion,
                usedSources: [],
                confidence: 100,
                needsHuman: false,
                handoffReason: null,
                factsUpdate: [],
                nextQuestion: null,
            );
            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    $slotReply, ['to' => $customerPhone]
                );
            }
            $aiState->update(['last_bot_reply_at' => now()]);
            return $this->makeResult(
                outcome: 'replied', replyText: $nextQuestion, botReply: $slotReply, groundingResult: null,
                styleResult: null, intent: $context->intent, difficulty: $context->difficulty,
                kbChunksUsed: 0, propertiesFound: 0, tokensIn: 0, tokensOut: 0,
                segments: $sandbox ? $this->deliveryService->prepareSegments($nextQuestion) : [],
                trace: $trace, factsUpdated: [],
            );
        }

        // ─── Generate reply (Pass 2) ─────────────────────────────────────────
        [$draft, $tokensIn, $tokensOut] = $this->generateReply($context, $sandbox);

        if ($draft === null) {
            $this->handoffService->pauseBot($aiState, 'generation_failed');
            $trace[] = 'generation: failed';
            return $this->makeResult(
                outcome: 'handoff',
                replyText: null,
                botReply: null,
                groundingResult: null,
                styleResult: null,
                intent: $context->intent,
                difficulty: $context->difficulty,
                kbChunksUsed: count($context->kbChunks),
                propertiesFound: count($context->propertySearchResult['results'] ?? []),
                tokensIn: 0,
                tokensOut: 0,
                segments: [],
                trace: $trace,
                factsUpdated: [],
            );
        }

        $trace[] = 'generation: ok | confidence: ' . $draft->confidence;

        // ─── Empty-results reply guard ────────────────────────────────────────
        // Reliable rule: during an active search, if the tool returned 0 hits and
        // there is no KB grounding, never deliver invented inventory. Replace any
        // non-admission reply with an honest no-results message.
        $propertiesFoundCount = count($context->propertySearchResult['results'] ?? []);
        $kbChunksCount        = count($context->kbChunks);
        $searchLikeIntent     = in_array($context->intent, ['property_search', 'pricing', 'viewing'], true);
        if (
            $searchLikeIntent
            && $propertiesFoundCount === 0
            && $kbChunksCount === 0
            && SearchSession::isActive($facts)
            && ! $this->replyDeniesPropertyResults($draft->reply)
        ) {
            $trace[] = 'empty_results_guard: triggered (search session, 0 results)';
            Log::info('bot.empty_results_guard.triggered', [
                'conversation_id' => $conversationId,
                'reason'          => 'active_search_empty',
            ]);
            $draft = new BotReply(
                reply: 'ما لقيت شي مطابق لمعاييرك الحين. نقدر نوسّع البحث، أو أحولك لأحد من الفريق يساعدك.',
                usedSources: [],
                confidence: 90,
                needsHuman: false,
                handoffReason: null,
                factsUpdate: $draft->factsUpdate,
                nextQuestion: 'تقدر توضح نوع العقار أو الحي اللي تبيه؟',
            );
        }

        // ─── Has-results denial guard ─────────────────────────────────────────
        // Inverse of empty-results: when search returned inventory, never allow
        // the LLM to claim "ما لقيت نتائج" (common when titles fail to load).
        if (
            in_array($context->intent, ['property_search', 'pricing', 'viewing'], true) &&
            $propertiesFoundCount > 0 &&
            $this->replyDeniesPropertyResults($draft->reply)
        ) {
            $trace[] = 'has_results_guard: triggered (denied despite ' . $propertiesFoundCount . ' results)';
            Log::info('bot.has_results_guard.triggered', [
                'conversation_id' => $conversationId,
                'properties'      => $propertiesFoundCount,
            ]);
            $draft = new BotReply(
                reply: $this->buildFoundPropertiesReply(
                    $context->propertySearchResult['results'] ?? [],
                    $context->propertySearchResult,
                ),
                usedSources: array_map(
                    static fn ($p) => 'property:' . ($p['id'] ?? ''),
                    $context->propertySearchResult['results'] ?? []
                ),
                confidence: max(80, (int) $draft->confidence),
                needsHuman: false,
                handoffReason: null,
                factsUpdate: $draft->factsUpdate,
                nextQuestion: $draft->nextQuestion,
            );
        }

        // ─── Location-relax disclosure guard ─────────────────────────────────
        // When search dropped city/district and returned elsewhere, force a clear
        // "ما لقيت في X لكن…" prefix so out-of-city hits (e.g. جدة for الرياض) are honest.
        if (
            $propertiesFoundCount > 0
            && ($context->propertySearchResult['location_relaxed'] ?? false)
            && ! $this->replyDisclosesLocationRelax($draft->reply)
        ) {
            $trace[] = 'location_relax_guard: prefixed disclosure';
            $labeled = $this->prefixLocationRelaxDisclosure(
                $draft->reply,
                $context->propertySearchResult,
            );
            $draft = new BotReply(
                reply: $labeled,
                usedSources: $draft->usedSources,
                confidence: $draft->confidence,
                needsHuman: $draft->needsHuman,
                handoffReason: $draft->handoffReason,
                factsUpdate: $draft->factsUpdate,
                nextQuestion: $draft->nextQuestion,
            );
        }

        // ─── Grounding verification (Pass 3) ─────────────────────────────────
        $contextText = $this->buildContextTextForVerification($context);
        $groundingResult = $this->groundingVerifier->verify($draft, $contextText);
        $styleResult = $this->groundingVerifier->applyStyleLint($draft->reply);
        $trace[] = 'grounding: ' . ($groundingResult->passed ? 'passed' : 'FAILED (' . implode(', ', $groundingResult->failedClaims) . ')');
        $trace[] = 'style_lint: ' . ($styleResult->passed ? 'passed' : implode(', ', $styleResult->issues));

        // Log style violations — they do not block delivery but are tracked
        if (! $styleResult->passed) {
            Log::info('bot.style_lint.issues', [
                'conversation_id' => $conversationId,
                'issues' => $styleResult->issues,
            ]);
        }

        $groundedReply = $draft;
        $inboundCriteria = MessageFactExtractor::extract([$inboundText]);
        $addedSearchCriteria = MessageFactExtractor::hasSearchSignals($inboundCriteria)
            || isset($inboundCriteria['city'])
            || isset($inboundCriteria['district']);

        if (! $groundingResult->passed) {
            Log::warning('bot.grounding.failed', [
                'conversation_id' => $conversationId,
                'claims' => $groundingResult->failedClaims,
            ]);
            if (! $sandbox) {
                $this->recordUnanswered($tenantId, $conversationId, $inboundText, 'grounding_failure');
            }
            $groundedReply = BotReply::handoff('grounding_verification_failed');
            // Count this as a failed turn — unless the customer just refined criteria
            // (empty inventory + new budget/type should not burn toward hard pause).
            if ($addedSearchCriteria) {
                $failedTurns = 0;
            } else {
                $failedTurns++;
            }
        } elseif (
            in_array($context->intent, ['property_search', 'pricing'], true) &&
            $kbChunksCount === 0 &&
            $propertiesFoundCount === 0 &&
            ! $this->isInventoryFollowUp($inboundText, $context->intent)
        ) {
            // Bot tried a fresh search but came back empty — weak turn.
            // Viewing / detail follow-ups on empty inventory do NOT count.
            // Newly supplied criteria (budget/type/city/…) reset the counter so a
            // long refine thread does not hard-pause at 3 empty searches.
            if ($addedSearchCriteria) {
                $failedTurns = 0;
            } else {
                $failedTurns++;
            }
        } else {
            // Successful grounded turn — reset counter
            $failedTurns = 0;
        }

        // Persist updated counter in facts (non-customer-facing, prefixed with _)
        $aiState->update(['facts' => array_merge($facts, ['_failed_turns' => $failedTurns])]);

        // ─── Handoff logic ────────────────────────────────────────────────────
        $confidenceThreshold = (int) ($config->confidence_threshold ?? 40);
        $escalationRules     = $config->escalation_rules ?? null;

        if (
            $groundedReply->needsHuman ||
            $this->handoffService->shouldHandoff(
                $groundedReply->confidence,
                ! $groundingResult->passed,
                $context->intent,
                $failedTurns,
                $confidenceThreshold,
                $escalationRules,
            )
        ) {
            $handoffReason = $groundedReply->handoffReason
                ?? (! $groundingResult->passed ? 'grounding_verification_failed' : 'low_confidence');

            // Soft missing-info / grounding: keep the bot alive mid-search.
            // Hard pause only for complaints, 3 weak turns, or explicit customer/regulated handoff.
            // LLM needs_human + grounding failures stay soft so follow-ups are not killed.
            $isGroundingHandoff = $handoffReason === 'grounding_verification_failed'
                || ! $groundingResult->passed;

            $hardPause = $context->intent === 'complaint'
                || $failedTurns >= 3
                || in_array($handoffReason, [
                    'customer_requested_human',
                    'customer_frustration',
                    'regulated_topic',
                ], true);

            if (! $hardPause && SearchSession::isActive($facts)) {
                $softMsg = trim((string) $groundedReply->reply);
                // If grounding failed or LLM requested human without content, use a safe fallback.
                if ($isGroundingHandoff || $softMsg === '' || $groundedReply->needsHuman) {
                    $softMsg = 'ما قدرت أتأكد من هالتفاصيل من البيانات اللي عندي. وضّح سؤالك شوي، أو اكتب "تحدث مع موظف" وأحولك.';
                }
                $trace[] = $isGroundingHandoff
                    ? 'handoff_soft: grounding_failed_no_pause'
                    : 'handoff_soft: missing_info_no_pause';
                [$softMsg, $disclosureInjected] = $this->maybePrefixDisclosure(
                    $softMsg, $config, $aiState, $discloseEnabled
                );
                if ($disclosureInjected) {
                    $trace[] = 'disclosure: injected';
                }
                $this->postTurn($aiState, $groundedReply, $context, $tokensIn, $tokensOut);
                if (! $sandbox) {
                    $this->deliveryService->deliver(
                        $tenantId, $conversationId, $waNumberId, $customerPhone,
                        new BotReply(
                            reply: $softMsg,
                            usedSources: $groundedReply->usedSources,
                            confidence: $groundedReply->confidence,
                            needsHuman: false,
                            handoffReason: null,
                            factsUpdate: $groundedReply->factsUpdate,
                            nextQuestion: $groundedReply->nextQuestion,
                        ),
                        ['to' => $customerPhone]
                    );
                }

                return $this->makeResult(
                    outcome: $sandbox ? 'delivered' : 'delivered',
                    replyText: $softMsg,
                    botReply: $groundedReply,
                    groundingResult: $groundingResult,
                    styleResult: $styleResult,
                    intent: $context->intent,
                    difficulty: $context->difficulty,
                    kbChunksUsed: count($context->kbChunks),
                    propertiesFound: count($context->propertySearchResult['results'] ?? []),
                    tokensIn: $tokensIn,
                    tokensOut: $tokensOut,
                    segments: $sandbox ? $this->deliveryService->prepareSegments($softMsg) : [],
                    trace: $trace,
                    factsUpdated: $groundedReply->factsUpdate,
                );
            }

            $trace[] = 'handoff: ' . $handoffReason;
            $this->handoffService->pauseBot($aiState, $handoffReason);

            $handoffMsg = match ($handoffReason) {
                'grounding_verification_failed' => 'هالسؤال يحتاج متخصص. أحولك الحين.',
                'low_confidence'                => 'ما عندي معلومة كافية الحين، بيتواصل معك أحد من الفريق.',
                default                         => 'بيتواصل معك أحد من فريقنا قريب.',
            };

            if (! $sandbox) {
                $this->deliveryService->deliver(
                    $tenantId, $conversationId, $waNumberId, $customerPhone,
                    new BotReply(
                        reply: $handoffMsg, usedSources: [], confidence: 100,
                        needsHuman: false, handoffReason: $handoffReason, factsUpdate: [], nextQuestion: null
                    ),
                    ['to' => $customerPhone]
                );
            }

            return $this->makeResult(
                outcome: 'handoff',
                replyText: $handoffMsg,
                botReply: $groundedReply,
                groundingResult: $groundingResult,
                styleResult: $styleResult,
                intent: $context->intent,
                difficulty: $context->difficulty,
                kbChunksUsed: count($context->kbChunks),
                propertiesFound: count($context->propertySearchResult['results'] ?? []),
                tokensIn: $tokensIn,
                tokensOut: $tokensOut,
                segments: $sandbox ? $this->deliveryService->prepareSegments($handoffMsg) : [],
                trace: $trace,
                factsUpdated: [],
            );
        }

        // ─── Autonomy: shadow vs autonomous ──────────────────────────────────
        // In sandbox mode, 'off' is promoted to 'autonomous' so any config can be tested.
        $autonomy = $config->autonomy_level ?? 'off';
        if ($sandbox && $autonomy === 'off') {
            $autonomy = 'autonomous';
            $trace[] = 'autonomy: off → autonomous (sandbox override)';
        } else {
            $trace[] = 'autonomy: ' . $autonomy;
        }

        if ($autonomy === 'shadow') {
            $this->postTurn($aiState, $groundedReply, $context, $tokensIn, $tokensOut);
            if (! $sandbox) {
                $this->storeShadowDraft($context, $groundedReply, $triggerMessage);
            }
            return $this->makeResult(
                outcome: 'shadow_draft',
                replyText: $groundedReply->reply,
                botReply: $groundedReply,
                groundingResult: $groundingResult,
                styleResult: $styleResult,
                intent: $context->intent,
                difficulty: $context->difficulty,
                kbChunksUsed: count($context->kbChunks),
                propertiesFound: count($context->propertySearchResult['results'] ?? []),
                tokensIn: $tokensIn,
                tokensOut: $tokensOut,
                segments: $this->deliveryService->prepareSegments($groundedReply->reply),
                trace: $trace,
                factsUpdated: $groundedReply->factsUpdate,
            );
        }

        if ($autonomy !== 'autonomous') {
            // Bot is 'off' in production — should not reach here unless called explicitly
            return BotTurnResult::skipped('autonomy_off', $trace);
        }

        // ─── Disclosure prefix (first bot reply only) ─────────────────────────
        $finalReply = $groundedReply->reply;
        [$finalReply, $disclosureInjected] = $this->maybePrefixDisclosure(
            $finalReply,
            $config,
            $aiState,
            $discloseEnabled,
        );
        if ($disclosureInjected) {
            $trace[] = 'disclosure: injected';
        } elseif (
            $discloseEnabled
            && ! $aiState->disclosed_as_assistant
            && $aiState->last_bot_reply_at !== null
        ) {
            // Bot already spoke earlier without disclosure — do not bolt it on later
            $aiState->update(['disclosed_as_assistant' => true]);
            $trace[] = 'disclosure: skipped (not first bot reply)';
        }

        $finalBotReply = new BotReply(
            reply: $finalReply,
            usedSources: $groundedReply->usedSources,
            confidence: $groundedReply->confidence,
            needsHuman: false,
            handoffReason: null,
            factsUpdate: $groundedReply->factsUpdate,
            nextQuestion: $groundedReply->nextQuestion,
        );

        // ─── Deliver ──────────────────────────────────────────────────────────
        if (! $sandbox) {
            $messageMeta = ['to' => $customerPhone, 'wa_number_id' => $waNumberId];
            $delivered = $this->deliveryService->deliver(
                $tenantId, $conversationId, $waNumberId, $customerPhone,
                $finalBotReply,
                $messageMeta
            );

            if ($delivered) {
                $this->postTurn($aiState, $groundedReply, $context, $tokensIn, $tokensOut);
                // Cache high-confidence replies for future exact hits
                if ($groundingResult?->passed && $groundedReply->confidence >= 80) {
                    $this->retrievalService->cacheExactReply(
                        $tenantId,
                        ArabicNormalizer::normalizeForSearch($context->standaloneQuery ?: $inboundText),
                        $finalReply
                    );
                }
            }
        } else {
            $this->postTurn($aiState, $groundedReply, $context, $tokensIn, $tokensOut);
        }

        $elapsed = (int) round(microtime(true) * 1000) - $startMs;
        $trace[] = 'elapsed_ms: ' . $elapsed;

        Log::info('bot.orchestrator.handled', [
            'conversation_id' => $conversationId,
            'intent' => $context->intent,
            'confidence' => $groundedReply->confidence,
            'elapsed_ms' => $elapsed,
            'sandbox' => $sandbox,
        ]);

        return $this->makeResult(
            outcome: 'delivered',
            replyText: $finalReply,
            botReply: $finalBotReply,
            groundingResult: $groundingResult,
            styleResult: $styleResult,
            intent: $context->intent,
            difficulty: $context->difficulty,
            kbChunksUsed: count($context->kbChunks),
            propertiesFound: count($context->propertySearchResult['results'] ?? []),
            tokensIn: $tokensIn,
            tokensOut: $tokensOut,
            segments: $this->deliveryService->prepareSegments($finalReply),
            trace: $trace,
            factsUpdated: $groundedReply->factsUpdate,
        );
    }

    private function makeResult(
        string $outcome,
        ?string $replyText,
        ?BotReply $botReply,
        ?VerificationResult $groundingResult,
        ?StyleLintResult $styleResult,
        string $intent,
        string $difficulty,
        int $kbChunksUsed,
        int $propertiesFound,
        int $tokensIn,
        int $tokensOut,
        array $segments,
        array $trace,
        array $factsUpdated,
    ): BotTurnResult {
        return new BotTurnResult(
            outcome: $outcome,
            replyText: $replyText,
            botReply: $botReply,
            groundingResult: $groundingResult,
            styleResult: $styleResult,
            intent: $intent,
            difficulty: $difficulty,
            kbChunksUsed: $kbChunksUsed,
            propertiesFound: $propertiesFound,
            tokensIn: $tokensIn,
            tokensOut: $tokensOut,
            botSegments: $segments,
            trace: $trace,
            skipReason: null,
            factsUpdated: $factsUpdated,
        );
    }

    /**
     * @return array{0: ?BotReply, 1: int, 2: int}  [reply, tokensIn, tokensOut]
     */
    private function generateReply(BotContext $context, bool $sandbox = false): array
    {
        $systemPrompt = $this->personaBuilder->buildSystemPrompt($context);
        $messages = $this->contextBuilder->buildGenerationMessages($context, $systemPrompt);

        try {
            $driver = $this->driverFactory->makeForTenant($context->tenantId);
            $model = $context->config->getAttribute('chat_model') ?? (string) config('openai.chat_model', 'gpt-5-mini');

            // Use the tenant-configured reply length target (tokens, not chars)
            $maxTokens = (int) ($context->config->reply_length_target ?? 600);
            // reply_length_target is in characters; convert to tokens (roughly 1.5 tokens/word, ~4 chars/word)
            $maxTokens = max(200, min(1200, (int) round($maxTokens / 4)));

            $response = $driver->complete(new LlmRequest(
                messages: $messages,
                model: $model,
                maxTokens: $maxTokens,
                temperature: 0.4,
                jsonMode: true,
                timeoutSeconds: 30,
            ));

            $passType = $sandbox ? 'simulate' : 'generate';
            $this->usageRecorder->record(
                $context->tenantId, $passType, $response, $context->conversationId
            );

            if (! $response->success) { return [null, $response->tokensIn, $response->tokensOut]; }

            $reply = BotReply::fromJson($response->content);
            if ($reply === null) {
                Log::warning('bot.generate.invalid_json', [
                    'conversation_id' => $context->conversationId,
                    'raw' => substr($response->content, 0, 200),
                ]);
            }
            return [$reply, $response->tokensIn, $response->tokensOut];
        } catch (\Throwable $e) {
            Log::error('bot.generate.exception', [
                'conversation_id' => $context->conversationId,
                'error' => $e->getMessage(),
            ]);
            return [null, 0, 0];
        }
    }

    private function postTurn(
        WaConversationAiState $aiState,
        BotReply $reply,
        BotContext $context,
        int $tokensIn = 0,
        int $tokensOut = 0,
    ): void {
        // Update facts and increment token counters
        $updateData = ['last_bot_reply_at' => now()];
        if (! empty($reply->factsUpdate)) {
            $incoming = $reply->factsUpdate;
            // Normalize LLM aliases
            if (isset($incoming['budget']) && ! isset($incoming['budget_max'])) {
                $incoming['budget_max'] = $incoming['budget'];
            }
            unset($incoming['budget'], $incoming['rooms']);
            // Coerce budget fields to numbers — LLMs sometimes emit "3 مليون".
            foreach (['budget_max', 'budget_min'] as $budgetKey) {
                if (! array_key_exists($budgetKey, $incoming)) {
                    continue;
                }
                $coerced = $this->coerceBudgetFact($incoming[$budgetKey]);
                if ($coerced === null) {
                    unset($incoming[$budgetKey]);
                } else {
                    $incoming[$budgetKey] = $coerced;
                }
            }
            // LLMs sometimes emit bedrooms: 0 / empty strings — ignore those.
            if (array_key_exists('bedrooms', $incoming) && (int) $incoming['bedrooms'] <= 0) {
                unset($incoming['bedrooms']);
            }
            // Never let LLM overwrite an already-known bedroom count (e.g. "دور رابع" → 4).
            // Customer corrections still flow through MessageFactExtractor on inbound.
            if (
                array_key_exists('bedrooms', $incoming)
                && isset(($aiState->facts ?? [])['bedrooms'])
            ) {
                unset($incoming['bedrooms']);
            }
            foreach (['type', 'intent', 'city', 'district'] as $k) {
                if (array_key_exists($k, $incoming) && ($incoming[$k] === null || $incoming[$k] === '')) {
                    unset($incoming[$k]);
                }
            }
            // Reuse sticky merge so a hallucinated type/intent mid-session cannot wipe criteria.
            $updateData['facts'] = $this->mergeExtractedFacts(
                $aiState->facts ?? [],
                $incoming,
                '' // no revision signal from LLM facts_update
            );
        }
        if ($tokensIn > 0 || $tokensOut > 0) {
            $updateData['tokens_in_total']  = ($aiState->tokens_in_total ?? 0) + $tokensIn;
            $updateData['tokens_out_total'] = ($aiState->tokens_out_total ?? 0) + $tokensOut;
        }
        $aiState->update($updateData);

        // Trigger summarization every N new turns
        $newTurns = Message::where('conversation_id', $context->conversationId)
            ->when($aiState->summary_through_message_id, fn ($q) => $q->where('id', '>', $aiState->summary_through_message_id))
            ->count();

        if ($newTurns >= self::MIN_TURNS_BEFORE_SUMMARY) {
            SummarizeConversationJob::dispatch(
                $context->conversationId,
                $context->tenantId,
                $context->customerPhone,
            );
        }
    }

    private function storeShadowDraft(BotContext $context, BotReply $reply, Message $trigger): void
    {
        try {
            ShadowBotDraft::create([
                'conversation_id'  => $context->conversationId,
                'user_id'          => $context->tenantId,
                'trigger_message_id' => $trigger->id,
                'draft_reply'      => $reply->reply,
                'used_sources'     => $reply->usedSources,
                'confidence'       => $reply->confidence,
                'status'           => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::warning('bot.shadow.store_failed', ['error' => $e->getMessage()]);
        }
    }

    private function recordUnanswered(int $tenantId, int $conversationId, string $question, string $reason): void
    {
        try {
            $clusterKey = md5(mb_strtolower(trim($question)));
            $existing = BotUnansweredQuestion::where('user_id', $tenantId)
                ->where('cluster_key', $clusterKey)->first();
            if ($existing) {
                $existing->increment('occurrence_count');
            } else {
                BotUnansweredQuestion::create([
                    'user_id'          => $tenantId,
                    'conversation_id'  => $conversationId,
                    'question'         => $question,
                    'cluster_key'      => $clusterKey,
                    'occurrence_count' => 1,
                ]);
            }
        } catch (\Throwable $e) {
            // Non-critical
        }
    }

    private function isWithinBusinessHours(WaAiConfig $config): bool
    {
        $hours = $config->business_hours;
        if (empty($hours)) { return true; } // No hours configured = always on

        try {
            $tz   = $config->timezone ?? 'Asia/Riyadh';
            $now  = now()->timezone($tz);
            $day  = strtolower($now->format('l')); // e.g. 'sunday'
            $time = $now->format('H:i');

            $dayHours = $hours[$day] ?? null;
            if ($dayHours === null) { return false; }
            if (($dayHours['open'] ?? false) === false) { return false; }

            $from = $dayHours['from'] ?? '09:00';
            $to   = $dayHours['to']   ?? '17:00';
            return $time >= $from && $time <= $to;
        } catch (\Throwable) {
            return true;
        }
    }

    private function isPendingTranscription(Message $message): bool
    {
        $meta = is_array($message->meta) ? $message->meta : [];
        return ($meta['type'] ?? null) === 'audio' && ($meta['transcription_status'] ?? null) === 'pending';
    }

    /**
     * Normalize LLM / mixed budget values to a positive float in SAR.
     * Accepts numbers and Arabic phrases like "3 مليون".
     */
    private function coerceBudgetFact(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value > 0 ? (float) $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (is_numeric($trimmed)) {
            $n = (float) $trimmed;

            return $n > 0 ? $n : null;
        }

        $parsed = MessageFactExtractor::extract([$trimmed]);
        foreach (['budget_max', 'budget_min'] as $key) {
            if (isset($parsed[$key]) && (float) $parsed[$key] > 0) {
                return (float) $parsed[$key];
            }
        }

        return null;
    }

    private function isLooping(int $conversationId): bool
    {
        $key = self::LOOP_GUARD_KEY . $conversationId;
        $count = (int) \Illuminate\Support\Facades\Cache::get($key, 0);
        if ($count >= self::LOOP_GUARD_MAX) { return true; }
        \Illuminate\Support\Facades\Cache::put($key, $count + 1, 60);
        return false;
    }

    /**
     * Merge newly extracted facts into the conversation state.
     *
     * Most fields: current-turn extract wins (so corrections stick).
     * type / intent: sticky while a search session is active, unless the message
     * clearly revises criteria (بدور على / أبغى / مو فيلا / بدل …).
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $extracted
     * @return array<string, mixed>
     */
    private function mergeExtractedFacts(array $current, array $extracted, string $inboundText): array
    {
        $merged = array_merge($current, $extracted);

        $stickyKeys = ['type', 'intent', 'city', 'district'];
        $sessionActive = SearchSession::isActive($current);
        $isRevision = (bool) preg_match(
            '/(?:بدور|أبغى|ابغى|أبحث|ابحث|غيرت|بدل|عندكم|عندك|أو\s+إذا|او\s+اذا|مو\s+(?:شقة|فيلا|عمارة|مكتب|أرض|ارض)|ليس)\b/u',
            $inboundText
        );

        // Explicit new city/district in the message counts as a location revision.
        if (
            ! $isRevision
            && (
                (isset($extracted['city']) && ($extracted['city'] ?? null) !== ($current['city'] ?? null))
                || (isset($extracted['district']) && ($extracted['district'] ?? null) !== ($current['district'] ?? null))
            )
            && preg_match('/(?:في|ب|حي|شمال|جنوب|شرق|غرب)\b/u', $inboundText)
        ) {
            $isRevision = true;
        }

        if ($sessionActive && ! $isRevision) {
            foreach ($stickyKeys as $key) {
                if (
                    isset($current[$key])
                    && $current[$key] !== ''
                    && isset($extracted[$key])
                    && $extracted[$key] !== $current[$key]
                ) {
                    $merged[$key] = $current[$key];
                }
            }
        }

        // When type truly revises, drop a focused listing from the previous type.
        if (
            isset($extracted['type'], $current['type'])
            && $extracted['type'] !== $current['type']
            && ($merged['type'] ?? null) === $extracted['type']
        ) {
            unset($merged['focused_property_id']);
        }

        return $merged;
    }

    /**
     * Prefix the AI disclosure on the first bot reply only, and only when enabled.
     *
     * @return array{0: string, 1: bool} [reply, injected]
     */
    private function maybePrefixDisclosure(
        string $reply,
        WaAiConfig $config,
        WaConversationAiState $aiState,
        bool $discloseEnabled,
    ): array {
        if (
            ! $discloseEnabled
            || $aiState->disclosed_as_assistant
            || $aiState->last_bot_reply_at !== null
        ) {
            return [$reply, false];
        }

        $prefix = $this->complianceService->buildDisclosurePrefix(
            $config->assistant_name ?? 'المساعد العقاري'
        );
        $aiState->update(['disclosed_as_assistant' => true]);

        return [$prefix . $reply, true];
    }

    private function buildContextTextForVerification(BotContext $context): string
    {
        $parts = [];
        foreach ($context->kbChunks as $chunk) {
            $parts[] = $chunk['content'] ?? '';
        }
        foreach ($context->propertySearchResult['results'] ?? [] as $prop) {
            // Include both raw JSON and human-readable forms so grounding can
            // match LLM phrasing like "7,000,000 ريال" / "524 م²".
            $parts[] = json_encode($prop, JSON_UNESCAPED_UNICODE);
            $price = number_format((float) ($prop['price'] ?? 0));
            $area  = $prop['area_sqm'] ?? null;
            $parts[] = sprintf(
                "عقار #%d %s السعر %s ريال %s SAR %s م² متر %s",
                (int) ($prop['id'] ?? 0),
                (string) ($prop['title'] ?? ''),
                $price,
                (string) ($prop['price'] ?? ''),
                $area !== null && $area !== '' ? (string) $area : '',
                (string) ($prop['address'] ?? '')
            );
            if ($area !== null && $area !== '' && preg_match('/^\d+(?:\.\d+)?$/', (string) $area)) {
                // Also expose integer form (524.00 → 524) for claim matching
                $parts[] = ((string) (int) $area) . ' م²';
            }
        }
        return implode("\n", $parts);
    }

    /**
     * Viewing / detail follow-ups should not burn `_failed_turns` when inventory is empty.
     */
    private function isInventoryFollowUp(string $inboundText, string $intent): bool
    {
        if ($intent === 'viewing') {
            return true;
        }

        return (bool) preg_match(
            '/(?:موعد|زيارة|معاينة|وين\s*المكتب|كم\s*شقة|الإيجار\s*السنوي|الدخل\s*السنوي|تفاصيل|رابط|ارسلي|أرسللي|أبي\s*أشوف|ابغى\s*اشوف|زاوية|سعر\s*المتر|كم\s*(?:السعر|سعر)|مطابق|ورني|العروض|فيه\s*درج|جنوب\s*سلمان|شمال\s*سلمان)/u',
            $inboundText
        );
    }

    /**
     * Detect LLM replies that falsely claim no inventory was found.
     */
    private function replyDeniesPropertyResults(string $reply): bool
    {
        $denyPhrases = [
            'ما لقيت نتائج',
            'ما لقيت نتيجة',
            'لم أجد نتائج',
            'لم اجد نتائج',
            'مافي نتائج',
            'ما في نتائج',
            'لا توجد نتائج',
            'ما لقيت شي',
            'ما لقيت شيء',
            'ما عندي نتائج',
        ];

        foreach ($denyPhrases as $phrase) {
            if (mb_strpos($reply, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the reply already discloses that the asked location had no hits.
     */
    private function replyDisclosesLocationRelax(string $reply): bool
    {
        return (bool) preg_match(
            '/ما\s*لقيت\s*في|لم\s*(?:أجد|اجد)\s*في|ما\s*عندي\s*في|خارج\s*(?:الموقع|المدينة)|في\s*مدن\s*أخرى/u',
            $reply
        );
    }

    /**
     * @param  array<string, mixed>  $searchResult
     */
    private function prefixLocationRelaxDisclosure(string $reply, array $searchResult): string
    {
        $requested = trim((string) (
            $searchResult['requested_location']
            ?? $searchResult['requested_city']
            ?? $searchResult['requested_district']
            ?? ''
        ));
        if ($requested === '') {
            $requested = 'الموقع اللي طلبته';
        }

        $prefix = "ما لقيت في {$requested} الحين، لكن عندي خيارات قريبة في مواقع ثانية: ";
        $reply = trim($reply);
        if ($reply === '') {
            return rtrim($prefix, ': ') . '.';
        }

        return $prefix . $reply;
    }

    /**
     * Deterministic Saudi-dialect reply that presents found inventory.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<string, mixed>|null         $searchMeta  Full propertySearchResult (for location_relaxed)
     */
    private function buildFoundPropertiesReply(array $results, ?array $searchMeta = null): string
    {
        if ($results === []) {
            return 'لقيت خيارات مناسبة. تبي التفاصيل؟';
        }

        $locationRelaxed = (bool) ($searchMeta['location_relaxed'] ?? false);
        $requested = trim((string) (
            $searchMeta['requested_location']
            ?? $searchMeta['requested_city']
            ?? $searchMeta['requested_district']
            ?? ''
        ));

        if (count($results) === 1) {
            $p     = $results[0];
            $title = trim((string) ($p['title'] ?? ''));
            if ($title === '') {
                $title = 'عقار #' . (int) ($p['id'] ?? 0);
            }
            $price = number_format((float) ($p['price'] ?? 0));
            $area  = $p['area_sqm'] ?? null;
            $addr  = trim((string) ($p['address'] ?? ''));

            if ($locationRelaxed) {
                $where = $requested !== '' ? $requested : 'الموقع اللي طلبته';
                $line = "ما لقيت في {$where} الحين، لكن عندي خيار قريب: *{$title}* بسعر {$price} ريال";
            } else {
                $line = "لقيت عقار مناسب: *{$title}* بسعر {$price} ريال";
            }
            if ($area !== null && $area !== '') {
                $line .= " ومساحة {$area} م²";
            }
            if ($addr !== '') {
                $line .= ". الموقع: {$addr}";
            }

            return $line . '. تبي تفاصيل أكثر ولا نرتب زيارة؟';
        }

        if ($locationRelaxed) {
            $where = $requested !== '' ? $requested : 'الموقع اللي طلبته';
            $lines = ["ما لقيت في {$where} الحين، لكن لقيت " . count($results) . ' خيارات قريبة في مواقع ثانية:'];
        } else {
            $lines = ['لقيت ' . count($results) . ' خيارات مناسبة:'];
        }
        foreach (array_slice($results, 0, 3) as $i => $p) {
            $title = trim((string) ($p['title'] ?? '')) ?: ('عقار #' . (int) ($p['id'] ?? 0));
            $price = number_format((float) ($p['price'] ?? 0));
            $addr  = trim((string) ($p['address'] ?? ''));
            $line  = ($i + 1) . ") *{$title}* — {$price} ريال";
            if ($addr !== '') {
                $line .= " ({$addr})";
            }
            $lines[] = $line;
        }
        $lines[] = 'أي واحد تبي نركز عليه؟';

        return implode("\n", $lines);
    }

    /**
     * Exact-reply cache: looks for a KB chunk that is an exact FAQ match
     * for the normalized query. Returns the cached answer or null.
     */
    private function checkExactReplyCache(int $tenantId, string $query): ?string
    {
        try {
            $normalized = ArabicNormalizer::normalizeForSearch(trim($query));
            if (mb_strlen($normalized) < 5) {
                return null;
            }
            $chunks = $this->retrievalService->retrieveExact($tenantId, $normalized);
            return $chunks[0]['answer'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Deliver and close a bot turn without full generation (for cache hits).
     */
    private function deliverAndClose(
        WaAiConfig $config,
        WaConversationAiState $aiState,
        BotContext $context,
        BotReply $reply,
        Message $triggerMessage,
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $customerPhone,
        bool $sandbox,
        array $facts,
        int $tokensIn,
        int $tokensOut,
        array &$trace,
    ): void {
        if (! $sandbox) {
            $this->deliveryService->deliver(
                $tenantId, $conversationId, $waNumberId, $customerPhone,
                $reply, ['to' => $customerPhone]
            );
        }
        $this->postTurn($aiState, $reply, $context, $tokensIn, $tokensOut);
        // Cache the reply for future exact hits
        $this->retrievalService->cacheExactReply(
            $tenantId,
            \App\Domain\Ai\Knowledge\ArabicNormalizer::normalizeForSearch($context->standaloneQuery ?: ''),
            $reply->reply
        );
    }
}
