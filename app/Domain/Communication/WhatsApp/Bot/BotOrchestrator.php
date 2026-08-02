<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotContext;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotReply;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotTurnResult;
use App\Domain\Communication\WhatsApp\Bot\Jobs\SummarizeConversationJob;
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
        $config = WaAiConfig::where('user_id', $tenantId)
            ->where('wa_number_id', $waNumberId)
            ->first();

        if ($config === null || ! $config->enabled) {
            return BotTurnResult::skipped('no_config_or_disabled', $trace);
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
        if (trim($inboundText) === '') {
            return BotTurnResult::skipped('empty_message', $trace);
        }

        // ─── Compliance check ────────────────────────────────────────────────
        $isFirstContact = $aiState->tokens_in_total === 0 && $aiState->tokens_out_total === 0;
        $compliance = $this->complianceService->check($inboundText, $aiState, $isFirstContact);
        $trace[] = 'compliance: ' . $compliance['action'];

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

        // ─── Build context (Pass 1 inside) ───────────────────────────────────
        $context = $this->contextBuilder->build(
            $tenantId, $conversationId, $waNumberId,
            $customerPhone, $config, $triggerMessage
        );
        $trace[] = 'intent: ' . $context->intent . ' | difficulty: ' . $context->difficulty;
        $trace[] = 'kb_chunks: ' . count($context->kbChunks) . ' | properties: ' . count($context->propertySearchResult['results'] ?? []);

        // ─── Generate reply (Pass 2) ─────────────────────────────────────────
        $draft = $this->generateReply($context, $sandbox);
        $tokensIn = 0;
        $tokensOut = 0;

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

        // ─── Grounding verification (Pass 3) ─────────────────────────────────
        $contextText = $this->buildContextTextForVerification($context);
        $groundingResult = $this->groundingVerifier->verify($draft, $contextText);
        $styleResult = $this->groundingVerifier->applyStyleLint($draft->reply);
        $trace[] = 'grounding: ' . ($groundingResult->passed ? 'passed' : 'FAILED (' . implode(', ', $groundingResult->failedClaims) . ')');
        $trace[] = 'style_lint: ' . ($styleResult->passed ? 'passed' : implode(', ', $styleResult->issues));

        $groundedReply = $draft;
        if (! $groundingResult->passed) {
            Log::warning('bot.grounding.failed', [
                'conversation_id' => $conversationId,
                'claims' => $groundingResult->failedClaims,
            ]);
            if (! $sandbox) {
                $this->recordUnanswered($tenantId, $conversationId, $inboundText, 'grounding_failure');
            }
            $groundedReply = BotReply::handoff('grounding_verification_failed');
        }

        // ─── Handoff logic ────────────────────────────────────────────────────
        if (
            $groundedReply->needsHuman ||
            $this->handoffService->shouldHandoff(
                $groundedReply->confidence,
                ! $groundingResult->passed,
                $context->intent,
                0
            )
        ) {
            $handoffReason = $groundedReply->handoffReason ?? 'low_confidence';
            $trace[] = 'handoff: ' . $handoffReason;
            $this->handoffService->pauseBot($aiState, $handoffReason);

            $handoffMsg = match ($handoffReason) {
                'grounding_verification_failed' => 'هذا السؤال يحتاج متخصص. سأحوّلك الآن.',
                'low_confidence'                => 'ما عندي معلومة كافية الآن، سيتواصل معك شخص من فريقنا.',
                default                         => 'سيتواصل معك أحد موظفينا قريباً.',
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
            $this->postTurn($aiState, $groundedReply, $context);
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

        // ─── Disclosure prefix (first contact) ───────────────────────────────
        $finalReply = $groundedReply->reply;
        if ($compliance['action'] === 'disclosure' && ! $aiState->disclosed_as_assistant) {
            $prefix = $this->complianceService->buildDisclosurePrefix(
                $config->assistant_name ?? 'المساعد العقاري'
            );
            $finalReply = $prefix . $finalReply;
            $aiState->update(['disclosed_as_assistant' => true]);
            $trace[] = 'disclosure: injected';
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
                $this->postTurn($aiState, $groundedReply, $context);
            }
        } else {
            $this->postTurn($aiState, $groundedReply, $context);
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

    private function generateReply(BotContext $context, bool $sandbox = false): ?BotReply
    {
        $systemPrompt = $this->personaBuilder->buildSystemPrompt($context);
        $messages = $this->contextBuilder->buildGenerationMessages($context, $systemPrompt);

        try {
            $driver = $this->driverFactory->makeForTenant($context->tenantId);
            $model = $context->config->getAttribute('chat_model') ?? env('OPENAI_CHAT_MODEL', 'gpt-5-mini');

            $response = $driver->complete(new LlmRequest(
                messages: $messages,
                model: $model,
                maxTokens: 600,
                temperature: 0.4,
                jsonMode: true,
                timeoutSeconds: 30,
            ));

            $passType = $sandbox ? 'simulate' : 'generate';
            $this->usageRecorder->record(
                $context->tenantId, $passType, $response, $context->conversationId
            );

            if (! $response->success) { return null; }

            $reply = BotReply::fromJson($response->content);
            if ($reply === null) {
                Log::warning('bot.generate.invalid_json', [
                    'conversation_id' => $context->conversationId,
                    'raw' => substr($response->content, 0, 200),
                ]);
            }
            return $reply;
        } catch (\Throwable $e) {
            Log::error('bot.generate.exception', [
                'conversation_id' => $context->conversationId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function postTurn(
        WaConversationAiState $aiState,
        BotReply $reply,
        BotContext $context,
    ): void {
        // Update facts
        if (! empty($reply->factsUpdate)) {
            $aiState->update([
                'facts'           => array_merge($aiState->facts ?? [], $reply->factsUpdate),
                'last_bot_reply_at' => now(),
            ]);
        } else {
            $aiState->update(['last_bot_reply_at' => now()]);
        }

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

    private function isLooping(int $conversationId): bool
    {
        $key = self::LOOP_GUARD_KEY . $conversationId;
        $count = (int) \Illuminate\Support\Facades\Cache::get($key, 0);
        if ($count >= self::LOOP_GUARD_MAX) { return true; }
        \Illuminate\Support\Facades\Cache::put($key, $count + 1, 60);
        return false;
    }

    private function buildContextTextForVerification(BotContext $context): string
    {
        $parts = [];
        foreach ($context->kbChunks as $chunk) {
            $parts[] = $chunk['content'] ?? '';
        }
        foreach ($context->propertySearchResult['results'] ?? [] as $prop) {
            $parts[] = json_encode($prop, JSON_UNESCAPED_UNICODE);
        }
        return implode("\n", $parts);
    }
}
