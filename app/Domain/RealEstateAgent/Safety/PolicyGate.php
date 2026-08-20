<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

use App\Domain\Communication\WhatsApp\Bot\ComplianceService;
use App\Domain\Communication\WhatsApp\Bot\HandoffService;
use App\Domain\RealEstateAgent\State\CustomerBrief;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;

/**
 * Post-loop safety gate.
 *
 * Applies compliance rules (opt-out, regulated topics, abuse) and decides
 * whether to deliver the reply, shadow it, or escalate to human.
 *
 * This is the only class that calls HandoffService — guaranteeing a single
 * escalation authority.
 */
final class PolicyGate
{
    private const MAX_WEAK_TURNS = 3;

    public function __construct(
        private readonly ComplianceService $compliance,
        private readonly HandoffService    $handoff,
    ) {}

    /**
     * @return array{decision: string, message: string|null, reason: string|null}
     *   decision: "deliver" | "shadow" | "handoff" | "opt_out"
     */
    public function evaluate(
        string               $inboundText,
        WaConversationAiState $aiState,
        WaAiConfig           $config,
        CustomerBrief        $brief,
        FactLedger           $ledger,
        array                $agentReply,    // decoded final reply from model
        bool                 $sandbox,
    ): array {
        // 1. Pre-generation compliance (opt-out, regulated, abuse)
        $compResult = $this->compliance->check(
            $inboundText,
            $aiState,
            $brief->isFirstContact,
            (bool) ($config->disclose_as_assistant ?? true),
        );

        if ($compResult['action'] === 'opt_out') {
            if (!$sandbox) {
                $aiState->update(['opt_out_status' => 'opted_out']);
            }
            return ['decision' => 'opt_out', 'message' => $compResult['message'] ?? null, 'reason' => 'customer_opt_out'];
        }

        if ($compResult['action'] === 'handoff') {
            return ['decision' => 'handoff', 'message' => $compResult['message'] ?? null, 'reason' => $compResult['reason'] ?? 'compliance'];
        }

        // 2. Tool-triggered escalation
        if ($ledger->escalationRequested()) {
            return ['decision' => 'handoff', 'message' => null, 'reason' => $ledger->escalationReason() ?? 'agent_tool'];
        }

        // 3. Model requested human
        if ((bool) ($agentReply['needs_human'] ?? false)) {
            return ['decision' => 'handoff', 'message' => null, 'reason' => 'model_needs_human'];
        }

        // 4. Low confidence — soft handoff on active search, hard only after repeated
        $confidence = (int) ($agentReply['confidence'] ?? 100);
        $threshold  = (int) ($config->confidence_threshold ?? 70);
        if ($confidence < $threshold && $ledger->searchWasRun()) {
            // Increment weak turn counter
            return ['decision' => 'low_confidence_soft', 'message' => null, 'reason' => "confidence_{$confidence}"];
        }

        // 5. Repeated empty searches → escalate
        if ($brief->weakSearchTurns >= self::MAX_WEAK_TURNS) {
            return ['decision' => 'handoff', 'message' => null, 'reason' => 'repeated_empty_search'];
        }

        // 6. Human-request keyword (check again in case compliance missed it above)
        if ($this->compliance->isHumanRequestKeyword($inboundText)) {
            return ['decision' => 'handoff', 'message' => null, 'reason' => 'customer_requested_human'];
        }

        // 7. Autonomy routing
        $autonomy = (string) ($config->autonomy_level ?? 'shadow');
        if ($autonomy === 'shadow' && !$sandbox) {
            return ['decision' => 'shadow', 'message' => null, 'reason' => null];
        }

        return ['decision' => 'deliver', 'message' => null, 'reason' => null];
    }

    public function triggerHandoff(WaConversationAiState $aiState, string $reason, bool $sandbox): void
    {
        if ($sandbox) {
            return;
        }
        $this->handoff->pauseBot($aiState, $reason);
    }
}
