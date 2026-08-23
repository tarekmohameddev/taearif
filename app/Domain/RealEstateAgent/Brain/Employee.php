<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Brain;

use App\Domain\Ai\Agent\Runtime\AgentLoop;
use App\Domain\Ai\Agent\Runtime\AgentLoopResult;
use App\Domain\Ai\Agent\Runtime\StepBudget;
use App\Domain\Ai\Agent\Runtime\ToolRegistry;
use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\Telemetry\TurnTrace;
use App\Domain\Ai\Agent\Telemetry\TraceRecorder;
use App\Domain\Ai\Agent\Transport\OpenAiTransport;
use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Domain\Ai\Knowledge\RetrievalService;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Domain\Ai\Services\UsageRecorder;
use App\Domain\Communication\WhatsApp\Bot\ComplianceService;
use App\Domain\Communication\WhatsApp\Bot\CrmFlywheelService;
use App\Domain\Communication\WhatsApp\Bot\HandoffService;
use App\Domain\Communication\WhatsApp\Bot\Jobs\SummarizeConversationJob;
use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;
use App\Domain\RealEstateAgent\Delivery\HumanCadence;
use App\Domain\RealEstateAgent\Leads\PortalLeadParser;
use App\Domain\RealEstateAgent\Safety\CitationGuard;
use App\Domain\RealEstateAgent\Safety\FactLedger;
use App\Domain\RealEstateAgent\Safety\GroundingPolicy;
use App\Domain\RealEstateAgent\Safety\HandoffGuard;
use App\Domain\RealEstateAgent\Safety\NumberProvenance;
use App\Domain\RealEstateAgent\Safety\PolicyGate;
use App\Domain\RealEstateAgent\Safety\ReplyRedactor;
use App\Domain\RealEstateAgent\Safety\ReplyRenderer;
use App\Domain\RealEstateAgent\Safety\RepetitionGuard;
use App\Domain\RealEstateAgent\State\BriefMerger;
use App\Domain\RealEstateAgent\State\CustomerBrief;
use App\Domain\RealEstateAgent\Tools\EscalateToHumanTool;
use App\Domain\RealEstateAgent\Tools\GetPropertyDetailsTool;
use App\Domain\RealEstateAgent\Tools\ProposeViewingTool;
use App\Domain\RealEstateAgent\Tools\RecordCustomerFactTool;
use App\Domain\RealEstateAgent\Tools\ResolveListingTool;
use App\Domain\RealEstateAgent\Tools\SearchInventoryTool;
use App\Domain\RealEstateAgent\Tools\SearchKnowledgeTool;
use App\Models\AiProviderCredential;
use App\Models\Message;
use App\Models\ShadowBotDraft;
use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The single entry point for the AI employee.
 *
 * Every inbound WhatsApp message that passes AutomationEngine gating lands here.
 * The class orchestrates: TurnGate → AgentLoop → CitationGuard → ReplyRenderer →
 * PolicyGate → delivery/shadow/handoff → brief persistence → telemetry.
 *
 * `runTurn` is intentionally long rather than spread across nested private calls:
 * the linear flow must be auditable at a glance.
 */
final class Employee
{
    private const LOOP_GUARD_CACHE_TTL = 60;  // seconds
    private const VERBATIM_TURN_LIMIT  = 16;
    private const MAX_CONTEXT_CHARS    = 12_000;
    private const DEFAULT_MAX_TOKENS   = 800;

    public function __construct(
        private readonly LlmDriverFactory    $driverFactory,
        private readonly UsageRecorder       $usageRecorder,
        private readonly PropertySearchTool  $propertySearchTool,
        private readonly EmbeddingService    $embeddingService,
        private readonly RetrievalService    $retrievalService,
        private readonly HumanCadence        $humanCadence,
        private readonly HandoffService      $handoffService,
        private readonly ComplianceService   $complianceService,
        private readonly PolicyGate          $policyGate,
        private readonly CitationGuard       $citationGuard,
        private readonly ReplyRenderer       $replyRenderer,
        private readonly BriefMerger         $briefMerger,
        private readonly PersonaComposer     $personaComposer,
        private readonly TraceRecorder       $traceRecorder,
        private readonly CrmFlywheelService  $crmFlywheel,
    ) {}

    /**
     * Production entry point — delivers to customer (or shadows/handoffs).
     */
    public function runTurn(
        int     $tenantId,
        int     $conversationId,
        int     $waNumberId,
        string  $customerPhone,
        Message $triggerMessage,
        bool    $dryRun = false,
    ): EmployeeTurnResult {
        $startMs = (int) round(microtime(true) * 1000);

        // ── 0. TurnGate: loop guard ────────────────────────────────────────────
        if (!$dryRun && $this->isLooping($conversationId)) {
            return EmployeeTurnResult::skipped('loop_detected');
        }

        // ── 1. Load config ─────────────────────────────────────────────────────
        $config = WaAiConfig::with('excludedPhones')
            ->where('user_id', $tenantId)
            ->where('wa_number_id', $waNumberId)
            ->where('enabled', true)
            ->first();

        if ($config === null || !in_array($config->autonomy_level, ['shadow', 'autonomous'], true)) {
            return EmployeeTurnResult::skipped('no_config_or_off');
        }

        // ── Guard: excluded customer number ────────────────────────────────
        $excludedPhones = $config->excludedPhones->pluck('phone')->all();
        if ($excludedPhones !== [] && in_array($customerPhone, $excludedPhones, true)) {
            return EmployeeTurnResult::skipped('excluded_number');
        }

        // ── 2. Business hours gate ─────────────────────────────────────────────
        if (!$dryRun && !$this->isWithinBusinessHours($config)) {
            return EmployeeTurnResult::skipped('outside_business_hours');
        }

        // ── 2b. Per-conversation distributed lock ─────────────────────────────
        // Acquired here, after cheap early gates, before any DB writes or LLM calls.
        // A concurrent message (webhook retry or rapid double-send) will see this lock
        // and skip rather than generating a duplicate reply.
        $convLock         = Cache::lock("agent:turn:{$conversationId}", 35);
        $convLockAcquired = false;
        if (!$dryRun) {
            if (!$convLock->get()) {
                Log::info('agent.employee.lock_contention', ['conversation_id' => $conversationId]);
                return EmployeeTurnResult::skipped('lock_contention');
            }
            $convLockAcquired = true;
        }

        try {
            return $this->executeLockedTurn(
                $tenantId, $conversationId, $waNumberId, $customerPhone,
                $triggerMessage, $dryRun, $startMs, $config
            );
        } finally {
            if ($convLockAcquired) {
                $convLock->release();
            }
        }
    }

    /**
     * The main turn body — called while holding the per-conversation lock.
     */
    private function executeLockedTurn(
        int         $tenantId,
        int         $conversationId,
        int         $waNumberId,
        string      $customerPhone,
        Message     $triggerMessage,
        bool        $dryRun,
        int         $startMs,
        WaAiConfig  $config,
    ): EmployeeTurnResult {

        // ── 3. Load / create AI state ──────────────────────────────────────────
        $aiState = WaConversationAiState::firstOrCreate(
            ['conversation_id' => $conversationId],
            ['user_id' => $tenantId, 'opt_out_status' => 'active', 'facts' => []]
        );

        if ($aiState->isOptedOut()) {
            return EmployeeTurnResult::skipped('opted_out');
        }
        if ($aiState->isBotPaused()) {
            return EmployeeTurnResult::skipped('bot_paused');
        }

        // ── 4. Idempotency check ───────────────────────────────────────────────
        $idempotencyKey = $this->buildIdempotencyKey($triggerMessage);
        if (!$dryRun && $this->turnAlreadyProcessed($idempotencyKey)) {
            return EmployeeTurnResult::skipped('duplicate_message');
        }

        // ── 5. Media / empty text gate ────────────────────────────────────────
        $inboundText = trim((string) ($triggerMessage->content ?? ''));
        $meta        = is_array($triggerMessage->meta) ? $triggerMessage->meta : [];
        $messageType = (string) ($meta['type'] ?? 'text');
        $isPendingAudio = $messageType === 'audio' && ($meta['transcription_status'] ?? null) === 'pending';

        if ($isPendingAudio) {
            return EmployeeTurnResult::skipped('pending_transcription');
        }

        if (in_array($messageType, ['image', 'video', 'document'], true) && $inboundText === '') {
            $ackReply = 'وصلت الصورة/الملف. سيراجعها أحد موظفينا ويرد عليك قريباً.';
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $ackReply, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $this->policyGate->triggerHandoff($aiState, 'media_message', $dryRun);
            }
            return EmployeeTurnResult::delivered($ackReply, 'media_ack');
        }

        if ($inboundText === '') {
            return EmployeeTurnResult::skipped('empty_message');
        }

        // ── 6. Monthly token budget ────────────────────────────────────────────
        $monthlyBudget = (int) ($config->monthly_token_budget ?? 0);
        if ($monthlyBudget > 0 && $this->usageRecorder->isBudgetExceeded($tenantId, $monthlyBudget)) {
            $budgetMsg = 'نعتذر، تم استنفاد حصة الردود التلقائية هذا الشهر. سيتواصل معك فريقنا مباشرة.';
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $budgetMsg, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
            }
            return EmployeeTurnResult::delivered($budgetMsg, 'budget_exceeded');
        }

        // ── 7. Load brief and playbook ─────────────────────────────────────────
        $brief   = CustomerBrief::fromArray((array) ($aiState->facts ?? []));
        $playbook = Playbook::fromConfig($config);

        // ── 8. Pre-compliance check (opt-out / abuse / regulated) ─────────────
        $preComp = $this->complianceService->check(
            $inboundText,
            $aiState,
            $brief->isFirstContact,
            (bool) ($config->disclose_as_assistant ?? true),
        );

        if ($preComp['action'] === 'opt_out') {
            if (!$dryRun) {
                $aiState->update(['opt_out_status' => 'opted_out']);
                $msg = $preComp['message'] ?? 'تم تسجيل طلبك.';
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $msg, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
            }
            return EmployeeTurnResult::delivered($preComp['message'] ?? '', 'opt_out');
        }

        if ($preComp['action'] === 'handoff') {
            $msg = $preComp['message'] ?? 'سأحوّلك لأحد موظفينا.';
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $msg, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $this->policyGate->triggerHandoff($aiState, $preComp['reason'] ?? 'compliance', $dryRun);
            }
            return EmployeeTurnResult::handoff($msg, $preComp['reason'] ?? 'compliance');
        }

        // 'proceed_with_kb' means it's a payment-plan question — the loop will
        // call search_knowledge before deciding. No special action needed here;
        // just proceed and let the model handle it via the knowledge tool.

        // Human keyword shortcut
        if ($this->complianceService->isHumanRequestKeyword($inboundText)) {
            $msg = 'تمام! سيتواصل معك أحد موظفينا قريباً. شكراً لتواصلك معنا.';
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $msg, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $this->policyGate->triggerHandoff($aiState, 'customer_requested_human', $dryRun);
            }
            return EmployeeTurnResult::handoff($msg, 'customer_requested_human');
        }

        // ── 9. Greeting shortcut (cost control) ───────────────────────────────
        // Pure greetings on a returning customer don't need tool calls or a large
        // context window. We reply with a minimal template to keep cost near zero.
        if ($this->isPureGreeting($inboundText) && !$brief->isFirstContact && !$dryRun) {
            $name       = $brief->customerName ? "، {$brief->customerName}" : '';
            $greetReply = "أهلاً وسهلاً{$name}! كيف أقدر أساعدك اليوم؟";
            $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $greetReply, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
            return EmployeeTurnResult::delivered($greetReply, 'greeting_shortcut');
        }

        // ── 9b. Portal lead detection ──────────────────────────────────────────
        // Messages like "أرغب في التواصل مع المعلن على تطبيق عقار..." are BUYER
        // leads arriving from portal apps. The model must NOT treat them as human
        // escalation requests. Parse the lead and inject a system note.
        $portalParser  = new PortalLeadParser();
        $portalLead    = $portalParser->parse($inboundText);
        $portalLeadNote = '';

        // ── 9c. 24-hour cost alert ────────────────────────────────────────────
        $this->checkCostAlert($tenantId, $config);

        // ── 10. Build initial messages ─────────────────────────────────────────
        $ledger     = new FactLedger();
        $tenant     = User::find($tenantId);
        $tenantName = (string) ($tenant?->name ?? 'المكتب العقاري');

        // Disclosure prefix — only on first actual LLM reply
        $disclosurePrefix = '';
        if ($playbook->discloseAsAssistant && $brief->isFirstContact && !$brief->disclosedAsAssistant) {
            $disclosurePrefix = $this->complianceService->buildDisclosurePrefix($playbook->assistantName);
        }

        $systemMsg = $this->personaComposer->compose($playbook, $brief, $ledger, $tenantName);
        $history   = $this->buildHistory($conversationId, $tenantId, $aiState, $triggerMessage);

        $initialMessages = [$systemMsg, ...$history];

        // Portal lead injection: if detected, add a system note so the model
        // knows this is a buyer inquiry about a specific property — not a human request.
        if ($portalLead['is_portal_lead']) {
            $portalLeadNote = $this->buildPortalLeadNote($portalLead);
            $initialMessages[] = AgentMessage::system($portalLeadNote);
            Log::info('agent.employee.portal_lead_detected', [
                'conversation_id' => $conversationId,
                'platform'        => $portalLead['platform'],
                'ad_id'           => $portalLead['ad_id'],
            ]);
        }

        // ── 11. Build tool registry ────────────────────────────────────────────
        $tools = [
            new SearchInventoryTool($this->propertySearchTool),
            new GetPropertyDetailsTool(),
            new SearchKnowledgeTool($this->embeddingService, $this->retrievalService),
            new ProposeViewingTool(),
            new EscalateToHumanTool(),
            new RecordCustomerFactTool(),
        ];

        // Add resolve_listing when dealing with a portal lead
        if ($portalLead['is_portal_lead']) {
            $tools[] = new ResolveListingTool();
        }

        $toolRegistry = new ToolRegistry($tools);

        // ── 12. Resolve transport ──────────────────────────────────────────────
        $credential  = $this->resolveCredential($tenantId);
        $transport   = new OpenAiTransport(
            apiKey:        $credential['key'],
            baseUrl:       $credential['base_url'],
            providerLabel: $credential['provider'],
        );

        // ── 13. Run agent loop ─────────────────────────────────────────────────
        $maxTokens = (int) ($config->max_tokens_per_turn ?? self::DEFAULT_MAX_TOKENS);

        // Budget: track COMPLETION tokens only (not prompt tokens, which inflate per step).
        // maxCompletionTokens = maxTokens * maxSteps covers 6 full model outputs.
        // Prompt tokens are counted in the log for cost accounting but don't exhaust the budget.
        $budget    = new StepBudget(maxSteps: 6, maxCompletionTokens: $maxTokens * 6, wallClockMs: 50_000);
        $loop      = new AgentLoop($transport, $toolRegistry);
        $model     = $credential['chat_model'];

        $loopResult = $loop->run($initialMessages, $tenantId, $model, $budget, $maxTokens, 45);

        // ── 14. Populate FactLedger from tool call log ─────────────────────────
        $this->populateLedger($ledger, $loopResult, $tenantId);

        // ── 14b. GroundingPolicy: force search when intent is inventory but no search ran ──
        if (!$loopResult->failed() && (new GroundingPolicy())->needsForcedSearch($inboundText, $ledger, $loopResult->finalReply ?? [])) {
            $groundingBudget = new StepBudget(maxSteps: 2, maxCompletionTokens: $maxTokens * 2, wallClockMs: 20_000);
            $groundingMsgs   = array_merge($initialMessages, [
                AgentMessage::system('[تعليمة نظام]: السؤال عن عقارات ولم يجرِ بحث بعد. استخدم search_inventory الآن قبل الإجابة.'),
            ]);
            $groundingResult = (new AgentLoop($transport, $toolRegistry))->run($groundingMsgs, $tenantId, $model, $groundingBudget, $maxTokens, 30);
            if (!$groundingResult->failed()) {
                $this->populateLedger($ledger, $groundingResult, $tenantId);
                $loopResult = $groundingResult;
                Log::info('agent.employee.grounding_forced', ['conversation_id' => $conversationId]);
            }
        }

        // ── 14c. HandoffGuard: reject unevidenced escalations and retry ────────
        if (!$loopResult->failed() && ($ledger->escalationRequested() || (bool) ($loopResult->finalReply['needs_human'] ?? false))) {
            $handoffGuard = new HandoffGuard();
            $escalReason  = $ledger->escalationReason() ?? ((bool) ($loopResult->finalReply['needs_human'] ?? false) ? 'model_needs_human' : 'unknown');

            if (!$handoffGuard->isEvidenced($escalReason, $inboundText, $ledger, $loopResult->finalReply ?? [], $brief, $this->complianceService)) {
                Log::info('agent.employee.handoff_rejected', [
                    'conversation_id' => $conversationId,
                    'reason'          => $escalReason,
                ]);
                $ledger->clearEscalation();

                // Retry without escalate_to_human in the registry
                $noEscalTools = array_filter($tools, fn ($t) => $t->name() !== 'escalate_to_human');
                $retryReg     = new ToolRegistry(array_values($noEscalTools));
                $retryBudget2 = new StepBudget(maxSteps: 2, maxCompletionTokens: $maxTokens * 2, wallClockMs: 20_000);
                $retryMsgs2   = array_merge($initialMessages, [
                    AgentMessage::system('[تعليمة نظام]: لا يمكن التحويل لموظف في هذه الحالة. أجب مباشرةً من المعلومات المتاحة أو اقترح تعديل معايير البحث.'),
                ]);
                $retryResult2 = (new AgentLoop($transport, $retryReg))->run($retryMsgs2, $tenantId, $model, $retryBudget2, $maxTokens, 25);
                if (!$retryResult2->failed()) {
                    $this->populateLedger($ledger, $retryResult2, $tenantId);
                    $loopResult = $retryResult2;
                    Log::info('agent.employee.handoff_retry_ok', ['conversation_id' => $conversationId]);
                }
            }
        }

        // ── 15. Handle loop failure ────────────────────────────────────────────
        if ($loopResult->failed()) {
            // budget_exhausted = agent couldn't find the info within the step limit;
            // keep the tone helpful rather than alarming.
            $failReason = $loopResult->failureReason ?? 'unknown';
            $fallback   = $failReason === 'budget_exhausted'
                ? 'آسف، ما قدرت أجيب على سؤالك بشكل كامل الآن. هل تودّ التحدث مع أحد موظفينا مباشرةً؟'
                : 'عذراً، واجهتنا مشكلة تقنية مؤقتة. سيتواصل معك أحد موظفينا قريباً.';
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $fallback, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $this->policyGate->triggerHandoff($aiState, "loop_failed:{$failReason}", $dryRun);
            }
            $this->recordTrace($idempotencyKey, $triggerMessage, $tenantId, $conversationId, $brief, $brief, [], $loopResult, [], $model, 'failed', $fallback, 'sent', $startMs);
            return EmployeeTurnResult::failed($fallback, $failReason);
        }

        $agentReply = $loopResult->finalReply;

        // ── 16. CitationGuard ─────────────────────────────────────────────────
        // Build allowed numbers from ledger + customer messages (RC3: NumberProvenance)
        $provenance     = new NumberProvenance();
        $allowedNumbers = $provenance->buildAllowedSet($ledger, $history);
        $violations     = $this->citationGuard->check($agentReply, $ledger, $allowedNumbers);
        if (count($violations) > 0) {
            Log::warning('agent.employee.citation_violation', [
                'conversation_id' => $conversationId,
                'violations'      => $violations,
                'attempt'         => 1,
            ]);

            // One retry: rebuild the system prompt with the now-populated FactLedger
            // so property IDs and the citation format are visible to the model, then
            // ask it to regenerate the reply without bare numbers.
            $retrySystem   = $this->personaComposer->compose($playbook, $brief, $ledger, $tenantName);
            $firstId       = (string) array_key_first($ledger->allProperties() ?: ['0' => null]);
            $retryMessages = [
                $retrySystem,
                AgentMessage::system(
                    'تنبيه نظام: الرد السابق كان يحتوي على أرقام مباشرة. ' .
                    'يجب أن يحتوي حقل "say" على {{p:ID|field}} لكل رقم من 4 خانات أو أكثر. ' .
                    "مثال صحيح: {{p:{$firstId}|price}}"
                ),
                ...$history,
            ];
            $retryBudget  = new StepBudget(maxSteps: 1, maxCompletionTokens: $maxTokens, wallClockMs: 20_000);
            $retryResult  = (new AgentLoop($transport, $toolRegistry))->run(
                $retryMessages, $tenantId, $model, $retryBudget, $maxTokens, 20
            );

            if (!$retryResult->failed()) {
                $retryViolations = $this->citationGuard->check($retryResult->finalReply, $ledger, $allowedNumbers);
                if (empty($retryViolations)) {
                    Log::info('agent.employee.citation_retry_ok', ['conversation_id' => $conversationId]);
                    $agentReply = $retryResult->finalReply;
                    $loopResult = $retryResult;
                    $violations = [];
                } else {
                    $violations = $retryViolations;
                    Log::warning('agent.employee.citation_retry_still_violated', [
                        'conversation_id' => $conversationId,
                        'violations'      => $retryViolations,
                    ]);
                }
            }
        }

        // If violations still remain after one retry — try ReplyRedactor before escalating (RC3 fix).
        // Redacting the offending sentence is safer than handing off every time.
        if (count($violations) > 0) {
            $redactor       = new ReplyRedactor();
            $redactResult   = $redactor->redact((string) ($agentReply['say'] ?? ''), $violations);

            if (!$redactResult['was_emptied']) {
                // Salvage the redacted reply — clear the violation so rendering proceeds
                $agentReply['say'] = $redactResult['redacted'];
                $violations        = [];
                Log::info('agent.employee.citation_redacted', ['conversation_id' => $conversationId]);
            } else {
                // Nothing left after redaction — handoff as final resort
                $safeReply = 'عذراً على الإزعاج. سأحوّلك لأحد موظفينا للمساعدة.';
                if (!$dryRun) {
                    $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $safeReply, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                    $this->policyGate->triggerHandoff($aiState, 'citation_violation', $dryRun);
                }
                $briefAfter = $brief;
                $this->recordTrace($idempotencyKey, $triggerMessage, $tenantId, $conversationId, $brief, $briefAfter, $violations, $loopResult, $ledger->allProperties(), $model, 'handoff', $safeReply, 'sent', $startMs);
                return EmployeeTurnResult::handoff($safeReply, 'citation_violation');
            }
        }

        // ── 17. Render reply ───────────────────────────────────────────────────
        $rawSay        = (string) ($agentReply['say'] ?? '');
        $renderedReply = $this->replyRenderer->render($rawSay, $ledger);

        // Prepend disclosure if this is first contact
        if ($disclosurePrefix !== '') {
            $renderedReply = $disclosurePrefix . $renderedReply;
        }

        if (trim($renderedReply) === '') {
            $renderedReply = 'يسعدني مساعدتك. هل تودّ الاستفسار عن عقارات أو معلومات أخرى؟';
        }

        // ── 17b. RepetitionGuard — rephrase if too similar to recent replies ──
        $repetitionGuard = new RepetitionGuard();
        if ($repetitionGuard->isTooSimilar($renderedReply, $history)) {
            Log::info('agent.employee.repetition_detected', ['conversation_id' => $conversationId]);
            // One rephrase step: list the recent bot replies as forbidden phrases
            $recentBotTexts = array_slice(
                array_map(
                    fn ($m) => $m->content ?? '',
                    array_filter($history, fn ($m) => $m->role === 'assistant' && $m->content !== null)
                ),
                -5
            );
            $forbiddenList = implode("\n- ", $recentBotTexts);
            $rephraseMessages = array_merge($initialMessages, [
                AgentMessage::system(
                    "[تعليمة نظام]: ردك مكرر. يجب أن يكون الرد مختلفاً تماماً عن هذه الردود السابقة:\n- {$forbiddenList}"
                ),
            ]);
            $rephraseBudget = new StepBudget(maxSteps: 1, maxCompletionTokens: $maxTokens, wallClockMs: 15_000);
            $rephraseResult = (new AgentLoop($transport, $toolRegistry))->run($rephraseMessages, $tenantId, $model, $rephraseBudget, $maxTokens, 20);
            if (!$rephraseResult->failed()) {
                $rephraseRendered = $this->replyRenderer->render((string) ($rephraseResult->finalReply['say'] ?? ''), $ledger);
                if (trim($rephraseRendered) !== '') {
                    $renderedReply = $rephraseRendered;
                }
            }
        }

        // ── 17c. Markdown stripper ────────────────────────────────────────────
        // The LLM sometimes outputs **bold** even though Rule 4 bans it.
        // Strip WhatsApp-incompatible markdown headings (##) and bold markers (**).
        $renderedReply = preg_replace('/^\s*#{1,6}\s+/mu', '', $renderedReply) ?? $renderedReply;
        $renderedReply = preg_replace('/\*\*([^*\n]+)\*\*/u', '$1', $renderedReply) ?? $renderedReply;

        // ── 17d. Boilerplate stripper ─────────────────────────────────────────
        // Remove closing filler phrases that make the bot sound like a support bot
        $renderedReply = $repetitionGuard->stripBoilerplate($renderedReply);

        // ── 18. Brief merge ────────────────────────────────────────────────────
        $briefUpdates  = (array) ($agentReply['brief_updates'] ?? []);
        $ledgerFacts   = $ledger->mergedRecordedFacts();
        $mergedUpdates = array_merge($ledgerFacts, $briefUpdates);
        $briefAfter    = $this->briefMerger->merge($brief, $mergedUpdates);

        // Track weak search turns
        if ($ledger->searchWasRun() && $ledger->searchReturnedNoResults()) {
            $briefAfter = $this->briefMerger->withWeakSearchTurn($briefAfter);
        } elseif (!empty($mergedUpdates)) {
            // New criteria supplied — reset weak turn counter
            $briefAfter = $this->briefMerger->withWeakSearchTurnsReset($briefAfter);
        }

        if ($brief->isFirstContact) {
            $briefAfter = $this->briefMerger->withFirstContactDone($briefAfter);
        }
        if ($disclosurePrefix !== '') {
            $briefAfter = $this->briefMerger->withDisclosed($briefAfter);
        }

        // ── 19. PolicyGate ────────────────────────────────────────────────────
        $policyResult = $this->policyGate->evaluate(
            $inboundText, $aiState, $config, $briefAfter, $ledger, $agentReply, $dryRun
        );

        $decision = $policyResult['decision'];

        // ── 20. Route to delivery / shadow / handoff ──────────────────────────
        $deliveryStatus = 'pending';
        $finalDecision  = $decision;

        if ($decision === 'handoff' || $decision === 'opt_out') {
            $handoffMsg = $policyResult['message'] ?? $renderedReply;
            if (!$dryRun) {
                $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $handoffMsg, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $this->policyGate->triggerHandoff($aiState, $policyResult['reason'] ?? $decision, $dryRun);
            }
            $deliveryStatus = 'sent';
            $renderedReply  = $handoffMsg;
            $finalDecision  = 'handoff';

        } elseif ($decision === 'low_confidence_soft') {
            // Deliver but track weak turn
            $briefAfter = $this->briefMerger->withWeakSearchTurn($briefAfter);
            if (!$dryRun) {
                $sent = $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $renderedReply, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $deliveryStatus = $sent ? 'sent' : 'failed';
            }
            $finalDecision = 'delivered';

        } elseif ($decision === 'shadow') {
            if (!$dryRun) {
                ShadowBotDraft::create([
                    'conversation_id'   => $conversationId,
                    'user_id'           => $tenantId,
                    'trigger_message_id'=> $triggerMessage->id,
                    'draft_reply'       => $renderedReply,
                    'used_sources'      => $agentReply['cited_properties'] ?? [],
                    'confidence'        => $agentReply['confidence'] ?? 0,
                    'status'            => 'pending',
                ]);
            }
            $deliveryStatus = 'pending';
            $finalDecision  = 'shadow';

        } else {
            // deliver
            if (!$dryRun) {
                $sent = $this->humanCadence->send($tenantId, $conversationId, $waNumberId, $customerPhone, $renderedReply, ['to' => $customerPhone, 'wa_number_id' => $waNumberId]);
                $deliveryStatus = $sent ? 'sent' : 'failed';
                if (!$sent) {
                    $finalDecision = 'failed';
                }
            }
            $finalDecision = 'delivered';
        }

        // ── 21. Persist brief ─────────────────────────────────────────────────
        if (!$dryRun) {
            DB::transaction(function () use ($aiState, $briefAfter, $tenantId): void {
                $aiState->update(['facts' => $briefAfter->toArray()]);
                if (!$aiState->disclosed_as_assistant && $briefAfter->disclosedAsAssistant) {
                    $aiState->update(['disclosed_as_assistant' => true]);
                }
            });

            // ── 22. Record usage ────────────────────────────────────────────────
            $totalIn  = array_sum(array_column($loopResult->budget->log(), 'tokens_in'));
            $totalOut = array_sum(array_column($loopResult->budget->log(), 'tokens_out'));
            $this->usageRecorder->recordRaw($tenantId, 'agent_turn', $totalIn, $totalOut, $loopResult->budget->elapsedMs(), $model, $conversationId);

            // ── 23. Mark last bot reply ─────────────────────────────────────────
            $aiState->update(['last_bot_reply_at' => now()]);

            // ── 24. Summarize if enough turns ────────────────────────────────────
            $msgCount = Message::where('conversation_id', $conversationId)->count();
            if ($msgCount > 0 && $msgCount % 8 === 0) {
                SummarizeConversationJob::dispatch($conversationId, $tenantId, $customerPhone)->onQueue('ai');
            }

            // ── 25. CRM flywheel ─────────────────────────────────────────────────
            try {
                $this->crmFlywheel->sync($tenantId, $customerPhone, $aiState);
            } catch (\Throwable $e) {
                Log::warning('agent.crm_flywheel.failed', ['error' => $e->getMessage()]);
            }
        }

        // ── 26. Telemetry ─────────────────────────────────────────────────────
        $this->recordTrace($idempotencyKey, $triggerMessage, $tenantId, $conversationId, $brief, $briefAfter, $violations, $loopResult, $ledger->allProperties(), $model, $finalDecision, $renderedReply, $deliveryStatus, $startMs);

        return match ($finalDecision) {
            'shadow'  => EmployeeTurnResult::shadowed($renderedReply),
            'handoff' => EmployeeTurnResult::handoff($renderedReply, $policyResult['reason'] ?? 'policy'),
            'failed'  => EmployeeTurnResult::failed($renderedReply, 'delivery_failed'),
            default   => EmployeeTurnResult::delivered($renderedReply, $finalDecision),
        };
    }

    // ────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────

    private function isLooping(int $conversationId): bool
    {
        $key   = "bot:loop:{$conversationId}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, self::LOOP_GUARD_CACHE_TTL);
        return $count > 3;
    }

    private function isWithinBusinessHours(WaAiConfig $config): bool
    {
        if (empty($config->business_hours)) {
            return true;
        }
        $tz   = (string) ($config->timezone ?? 'Asia/Riyadh');
        $now  = now()->setTimezone($tz);
        $day  = mb_strtolower($now->format('l')); // e.g. "sunday"
        $plan = $config->business_hours[$day] ?? null;
        if (!is_array($plan) || empty($plan['open'])) {
            return false;
        }
        $from = $plan['from'] ?? '00:00';
        $to   = $plan['to']   ?? '23:59';
        $time = $now->format('H:i');
        return $time >= $from && $time <= $to;
    }

    private function buildIdempotencyKey(Message $message): string
    {
        $providerId = (string) ($message->provider_message_id ?? '');
        if ($providerId !== '') {
            return 'agent:' . $providerId;
        }
        return 'agent:msg:' . $message->id;
    }

    private function turnAlreadyProcessed(string $key): bool
    {
        // We check ai_turn_traces — if a row with this key exists, skip
        try {
            return \App\Models\AiTurnTrace::where('idempotency_key', $key)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{key: string, base_url: string, provider: string, chat_model: string} */
    private function resolveCredential(int $tenantId): array
    {
        $cred = AiProviderCredential::where('user_id', $tenantId)->where('active', true)->first()
             ?? AiProviderCredential::whereNull('user_id')->where('is_platform_default', true)->where('active', true)->first();

        if ($cred !== null) {
            return [
                'key'        => $cred->getDecryptedKey() ?? '',
                'base_url'   => (string) ($cred->base_url ?? 'https://api.openai.com/v1'),
                'provider'   => (string) ($cred->provider ?? 'openai'),
                'chat_model' => (string) ($cred->chat_model ?? config('openai.chat_model', 'gpt-5-mini')),
            ];
        }

        return [
            'key'        => (string) config('openai.api_key', ''),
            'base_url'   => 'https://api.openai.com/v1',
            'provider'   => 'openai',
            'chat_model' => (string) config('openai.chat_model', 'gpt-5-mini'),
        ];
    }

    /** @return AgentMessage[] */
    private function buildHistory(int $conversationId, int $tenantId, WaConversationAiState $aiState, Message $trigger): array
    {
        $query = Message::where('conversation_id', $conversationId)
            ->where('user_id', $tenantId)
            ->orderByDesc('id');

        if ($aiState->summary_through_message_id) {
            $query->where('id', '>', $aiState->summary_through_message_id);
        }

        $messages = $query->limit(self::VERBATIM_TURN_LIMIT)->get()->sortBy('id')->values();

        $result     = [];
        $totalChars = 0;
        $current    = trim((string) ($trigger->content ?? ''));
        $included   = [];

        foreach ($messages->reverse() as $msg) {
            if ((int) $msg->id === (int) $trigger->id) {
                continue;
            }
            $content = (string) ($msg->content ?? '');
            $totalChars += mb_strlen($content);
            if ($totalChars > self::MAX_CONTEXT_CHARS) {
                break;
            }
            $included[] = $msg;
        }

        foreach (array_reverse($included) as $msg) {
            $content = (string) ($msg->content ?? '');
            if ($msg->direction === 'inbound') {
                $result[] = AgentMessage::user($content);
            } else {
                $meta   = is_array($msg->meta) ? $msg->meta : [];
                $source = $meta['source'] ?? null;
                if ($source === 'evolution_agent' || $source === 'whatsapp_echo') {
                    $result[] = AgentMessage::system("[رد موظف بشري سابق]: {$content}");
                } else {
                    $result[] = AgentMessage::assistant($content);
                }
            }
        }

        // Summary context as system message
        if ($aiState->summary_through_message_id && ($aiState->situation || $aiState->requirements)) {
            $summary = implode(' ', array_filter([
                $aiState->situation   ? "الوضع: {$aiState->situation}" : null,
                $aiState->requirements? "المتطلبات: {$aiState->requirements}" : null,
                $aiState->commitments ? "التزاماتنا: {$aiState->commitments}" : null,
            ]));
            if ($summary !== '') {
                array_unshift($result, AgentMessage::system("[ملخص المحادثة السابقة]: {$summary}"));
            }
        }

        // Current turn (always last)
        $result[] = AgentMessage::user($current);

        return $result;
    }

    private function populateLedger(FactLedger $ledger, AgentLoopResult $result, int $tenantId): void
    {
        foreach ($result->toolCallLog as $call) {
            $name   = (string) ($call['name'] ?? '');
            $output = (array) ($call['result'] ?? []);

            switch ($name) {
                case 'search_inventory':
                    $ledger->recordSearchRun(
                        hasResults:       !empty($output['results']),
                        locationRelaxed:  (bool) ($output['location_relaxed'] ?? false),
                        requestedLocation:(string) ($output['requested_location'] ?? ''),
                    );
                    if (!empty($output['results'])) {
                        $ledger->addProperties($output['results']);
                    }
                    break;

                case 'get_property_details':
                    if (!empty($output['found'])) {
                        $ledger->addProperties([$output]);
                    }
                    break;

                case 'search_knowledge':
                    if (!empty($output['chunks'])) {
                        $ledger->addKnowledgeChunks($output['chunks']);
                    }
                    break;

                case 'resolve_listing':
                    if (!empty($output['found'])) {
                        $ledger->addProperties([$output]);
                    }
                    break;

                case 'escalate_to_human':
                    if (!empty($output['escalate'])) {
                        $ledger->recordEscalation((string) ($output['reason'] ?? 'tool_escalation'));
                    }
                    break;

                case 'record_customer_fact':
                    if (!empty($output['facts_recorded'])) {
                        $ledger->addRecordedFacts((array) $output['facts_recorded']);
                    }
                    break;
            }
        }
    }

    /**
     * Log an alert if rolling 24-hour cost per turn exceeds 3.5x the baseline
     * recorded in AiUsageLog for the same tenant.
     */
    private function checkCostAlert(int $tenantId, WaAiConfig $config): void
    {
        try {
            $cacheKey    = "agent.cost_alert.{$tenantId}." . now()->format('Y-m-d-H');
            if (Cache::has($cacheKey)) {
                return; // Only check once per hour per tenant
            }
            Cache::put($cacheKey, true, 3600);

            $yesterday = now()->subDay();
            $stats = \App\Models\AiUsageLog::query()
                ->where('user_id', $tenantId)
                ->where('created_at', '>=', $yesterday)
                ->selectRaw('SUM(cost_micros) as cost, COUNT(*) as turns')
                ->first();

            $cost  = (int) ($stats?->cost ?? 0);
            $turns = (int) ($stats?->turns ?? 0);

            if ($turns < 10) {
                return; // Not enough data
            }

            $avgCostPerTurn = $cost / $turns;

            // Get 7-day baseline average
            $baseline = \App\Models\AiUsageLog::query()
                ->where('user_id', $tenantId)
                ->where('created_at', '>=', now()->subDays(7))
                ->where('created_at', '<', $yesterday)
                ->selectRaw('AVG(cost_micros) as avg_cost')
                ->value('avg_cost');

            if ($baseline > 0 && $avgCostPerTurn > $baseline * 3.5) {
                Log::alert('agent.cost_alert', [
                    'tenant_id'       => $tenantId,
                    'avg_cost_micros' => $avgCostPerTurn,
                    'baseline_micros' => $baseline,
                    'ratio'           => round($avgCostPerTurn / $baseline, 2),
                    'turns_24h'       => $turns,
                ]);
            }
        } catch (\Throwable) {
            // Swallow — cost alerts must not break turn execution
        }
    }

    /**
     * Build a system note to inject when the inbound message is a portal lead template.
     *
     * @param array<string, mixed> $parsed  Output of PortalLeadParser::parse()
     */
    private function buildPortalLeadNote(array $parsed): string
    {
        $platform = $parsed['platform'] ?: 'منصة خارجية';
        $parts    = ["[تعليمة نظام]: هذه رسالة مشترٍ محتمل قادمة من تطبيق {$platform}."];
        $parts[]  = "المشتري يسأل عن إعلان محدد — وليس طلب موظف بشري.";

        if ($parsed['ad_url']) {
            $parts[] = "رابط الإعلان: {$parsed['ad_url']}";
        }
        if ($parsed['ad_id']) {
            $parts[] = "رقم الإعلان على المنصة: {$parsed['ad_id']}";
        }
        if ($parsed['property_type_ar']) {
            $parts[] = "نوع العقار: {$parsed['property_type_ar']}";
        }
        if ($parsed['purpose']) {
            $parts[] = "الغرض: " . ($parsed['purpose'] === 'rent' ? 'إيجار' : 'بيع');
        }
        if ($parsed['city']) {
            $parts[] = "المدينة: {$parsed['city']}";
        }
        if ($parsed['district']) {
            $parts[] = "الحي: {$parsed['district']}";
        }
        if ($parsed['price']) {
            $parts[] = "السعر في الإعلان: [قيمة متاحة — استخدم resolve_listing للتحقق من العقار]";
        }

        $parts[] = "الخطوات المطلوبة: 1) استخدم resolve_listing لمطابقة الإعلان بعقار في المخزون. 2) أجب من بيانات العقار وFAQs. 3) لا تحوّل لموظف إلا إذا طلب المشتري صراحةً.";

        return implode("\n", $parts);
    }

    private function isPureGreeting(string $text): bool
    {
        $greetings = ['السلام عليكم', 'مرحبا', 'مرحباً', 'هلا', 'أهلا', 'حياك', 'صباح', 'مساء', 'كيفك', 'كيف حالك', 'hi', 'hello'];
        $lower     = mb_strtolower(trim($text));
        foreach ($greetings as $g) {
            if (str_contains($lower, mb_strtolower($g))) {
                return mb_strlen($text) < 40; // short messages only
            }
        }
        return false;
    }

    private function recordTrace(
        string           $idempotencyKey,
        Message          $triggerMessage,
        int              $tenantId,
        int              $conversationId,
        CustomerBrief    $briefBefore,
        CustomerBrief    $briefAfter,
        array            $violations,
        AgentLoopResult  $loopResult,
        array            $properties,
        string           $model,
        string           $decision,
        ?string          $renderedReply,
        string           $deliveryStatus,
        int              $startMs,
    ): void {
        try {
            $totalIn  = array_sum(array_column($loopResult->budget->log(), 'tokens_in'));
            $totalOut = array_sum(array_column($loopResult->budget->log(), 'tokens_out'));
            $latency  = (int) round(microtime(true) * 1000) - $startMs;

            $trace = new TurnTrace(
                tenantId:         $tenantId,
                conversationId:   $conversationId,
                triggerMessageId: (int) $triggerMessage->id,
                idempotencyKey:   $idempotencyKey,
                briefBefore:      $briefBefore->toArray(),
                briefAfter:       $briefAfter->toArray(),
                steps:            $loopResult->steps,
                toolCallLog:      $loopResult->toolCallLog,
                guardViolations:  $violations,
                model:            $model,
                tokensIn:         $totalIn,
                tokensOut:        $totalOut,
                latencyMs:        $latency,
                decision:         $decision,
                renderedReply:    $renderedReply,
                deliveryStatus:   $deliveryStatus,
                cassetteKey:      '',
            );

            $this->traceRecorder->record($trace);
        } catch (\Throwable $e) {
            Log::warning('agent.employee.trace_failed', ['error' => $e->getMessage()]);
        }
    }
}
