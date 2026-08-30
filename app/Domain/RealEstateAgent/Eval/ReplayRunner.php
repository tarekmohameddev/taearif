<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Eval;

use App\Domain\Ai\Agent\Runtime\AgentLoop;
use App\Domain\Ai\Agent\Runtime\StepBudget;
use App\Domain\Ai\Agent\Runtime\ToolRegistry;
use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\Ai\Agent\Schema\JsonSchema;
use App\Domain\Ai\Agent\Contracts\AgentTransport;
use App\Domain\RealEstateAgent\Leads\PortalLeadParser;
use App\Domain\RealEstateAgent\Safety\CitationGuard;
use App\Domain\RealEstateAgent\Safety\FactLedger;
use App\Domain\RealEstateAgent\Safety\HandoffGuard;
use App\Domain\RealEstateAgent\Safety\NumberProvenance;
use App\Domain\RealEstateAgent\Safety\RepetitionGuard;
use App\Domain\RealEstateAgent\Safety\ReplyRenderer;
use App\Domain\RealEstateAgent\State\CustomerBrief;

/**
 * Runs a corpus fixture deterministically using cassette recordings.
 *
 * Hard invariants checked on every turn (always enforced):
 *  1. No uncited number in the rendered reply (bare price/area digits).
 *  2. No property ID cited that the tool log did not return.
 *  3. No availability claim when search returned 0 results.
 *  4. No `budget_exhausted` failure (forced finalization prevents this).
 *  5. No unrendered {{...}} placeholders surviving to the customer.
 *
 * Soft invariants enabled per fixture via the `invariants` array:
 *  escalation_fired           — escalation must trigger on the last turn.
 *  brief_city_preserved       — city in brief must not change.
 *  search_tool_was_called     — search_inventory must have been called.
 *  no_bedroom_question_for_building — must not ask bedrooms for land/building.
 *  no_unevidenced_escalation  — escalation must have supporting evidence.
 *  no_bare_number_in_reply    — rendered reply must contain no bare 4+ digit numbers.
 *  no_repeated_reply          — reply must not match recent bot turns.
 *  no_assistant_boilerplate   — reply must not end with "أنا هنا للمساعدة"-style phrases.
 *  portal_lead_resolved       — portal lead must not be treated as a handoff.
 *  bot_skipped_or_opted_out   — for opt-out tests: loop must not produce a reply.
 *  no_availability_claim_without_search — no "عندنا" phrasing unless search ran.
 */
final class ReplayRunner
{
    private CitationGuard  $citationGuard;
    private ReplyRenderer  $replyRenderer;
    private NumberProvenance $provenance;
    private RepetitionGuard  $repetitionGuard;

    public function __construct()
    {
        $this->citationGuard   = new CitationGuard();
        $this->replyRenderer   = new ReplyRenderer();
        $this->provenance      = new NumberProvenance();
        $this->repetitionGuard = new RepetitionGuard();
    }

    /**
     * @param  array{type: string, id: string, turns: array, invariants: string[]} $fixture
     * @param  AgentTransport                                                       $transport
     * @param  ToolRegistry                                                         $toolRegistry
     * @param  int                                                                  $tenantId
     * @return ReplayResult
     */
    public function run(
        array          $fixture,
        AgentTransport $transport,
        ToolRegistry   $toolRegistry,
        int            $tenantId,
        string         $model = 'gpt-4o-mini',
    ): ReplayResult {
        $id         = (string) ($fixture['id'] ?? 'unknown');
        $turns      = (array)  ($fixture['turns'] ?? []);
        $invariants = (array)  ($fixture['invariants'] ?? []);
        $failures   = [];
        $brief      = new CustomerBrief();
        $messages   = [];
        $portalParser = new PortalLeadParser();

        $systemMsg = AgentMessage::system(
            'أنت مساعد عقاري. أجب على أسئلة العميل باستخدام الأدوات المتاحة. ' .
            'استخدم صيغة {{p:ID|field}} للإشارة لأرقام العقارات.'
        );
        $messages[] = $systemMsg;

        foreach ($turns as $turnIndex => $turn) {
            $role = (string) ($turn['role'] ?? 'customer');
            $text = (string) ($turn['text'] ?? '');

            if ($role === 'bot' || $role === 'agent') {
                $messages[] = AgentMessage::assistant($text);
                continue;
            }

            // Customer turn — run the agent
            $messages[] = AgentMessage::user($text);

            // RC2 fix: use maxCompletionTokens (not maxTokens) so prompt inflation
            // does not exhaust the budget prematurely.
            $budget = new StepBudget(maxSteps: 6, maxCompletionTokens: PHP_INT_MAX, wallClockMs: 60_000);
            $loop   = new AgentLoop($transport, $toolRegistry);

            $loopResult = $loop->run($messages, $tenantId, $model, $budget, 800, 60);

            // ── Hard invariant: no budget_exhausted ─────────────────────────────
            if ($loopResult->failureReason === 'budget_exhausted') {
                $failures[] = "turn:{$turnIndex} invariant:no_budget_exhausted — loop exhausted budget";
            }

            if ($loopResult->failed()) {
                $failures[] = "turn:{$turnIndex} loop_failed:{$loopResult->failureReason}";
                break;
            }

            $agentReply = $loopResult->finalReply;
            $ledger     = new FactLedger();
            $this->populateLedger($ledger, $loopResult->toolCallLog);

            // Build allowed numbers from ledger + customer messages
            $allowedNumbers = $this->provenance->buildAllowedSet($ledger, $messages);

            // ── Hard invariant: citation guard ──────────────────────────────────
            $violations = $this->citationGuard->check($agentReply, $ledger, $allowedNumbers);
            foreach ($violations as $v) {
                $failures[] = "turn:{$turnIndex} citation_violation:{$v}";
            }

            // ── Hard invariant: no unrendered placeholders ───────────────────────
            $rawSay   = (string) ($agentReply['say'] ?? '');
            $rendered = $this->replyRenderer->render($rawSay, $ledger);
            if (str_contains($rendered, '{{')) {
                $failures[] = "turn:{$turnIndex} invariant:no_unrendered_placeholder — {{...}} survived rendering in: " . substr($rendered, 0, 80);
            }

            // ── Soft invariants from fixture ────────────────────────────────────

            if (in_array('escalation_fired', $invariants, true) && $turnIndex === array_key_last($turns)) {
                if (!$ledger->escalationRequested() && !(bool) ($agentReply['needs_human'] ?? false)) {
                    $failures[] = "turn:{$turnIndex} invariant:escalation_fired — not triggered";
                }
            }

            if (in_array('brief_city_preserved', $invariants, true)) {
                $updates = (array) ($agentReply['brief_updates'] ?? []);
                if ($brief->city !== null && isset($updates['city']) && $updates['city'] !== $brief->city) {
                    $failures[] = "turn:{$turnIndex} invariant:brief_city_preserved — city changed from {$brief->city} to {$updates['city']}";
                }
            }

            if (in_array('search_tool_was_called', $invariants, true) && $turnIndex === array_key_last($turns)) {
                if (!$ledger->searchWasRun()) {
                    $failures[] = "turn:{$turnIndex} invariant:search_tool_was_called — search_inventory was not called";
                }
            }

            if (in_array('no_bedroom_question_for_building', $invariants, true)) {
                $say         = (string) ($agentReply['say'] ?? '');
                $briefUpdates = (array) ($agentReply['brief_updates'] ?? []);
                $pType        = $briefUpdates['property_type'] ?? $brief->propertyType ?? '';
                if (
                    (str_contains($say, 'غرف') && str_contains($say, '؟')) &&
                    in_array($pType, ['building', 'عمارة', 'land', 'أرض'], true)
                ) {
                    $failures[] = "turn:{$turnIndex} invariant:no_bedroom_question_for_building — asked bedrooms for '{$pType}'";
                }
            }

            if (in_array('no_unevidenced_escalation', $invariants, true)) {
                if ($ledger->escalationRequested() || (bool) ($agentReply['needs_human'] ?? false)) {
                    $guard  = new HandoffGuard();
                    $reason = $ledger->escalationReason() ?? 'model_needs_human';
                    // Create a dummy ComplianceService stub just to call isHumanRequestKeyword
                    // — we test this independently of the actual text
                    if (!$guard->isEvidenced($reason, $text, $ledger, $agentReply, $brief, new \App\Domain\Communication\WhatsApp\Bot\ComplianceService())) {
                        $failures[] = "turn:{$turnIndex} invariant:no_unevidenced_escalation — reason '{$reason}' has no evidence";
                    }
                }
            }

            if (in_array('no_bare_number_in_reply', $invariants, true)) {
                $normed = $this->provenance->normaliseArabicIndic($rendered);
                // Strip placeholders already resolved
                $stripped = preg_replace('/\{\{[^}]+\}\}/', 'X', $normed) ?? $normed;
                if (preg_match('/\b(\d{4,})\b/', $stripped, $m)) {
                    $num = (int) $m[1];
                    // Allow years and phones
                    if (!(($num >= 1400 && $num <= 1500) || ($num >= 2000 && $num <= 2100) || str_starts_with($m[1], '05') || str_starts_with($m[1], '966'))) {
                        if (!in_array($m[1], $allowedNumbers, true)) {
                            $failures[] = "turn:{$turnIndex} invariant:no_bare_number_in_reply — bare '{$m[1]}' in rendered reply";
                        }
                    }
                }
            }

            if (in_array('no_repeated_reply', $invariants, true)) {
                if ($this->repetitionGuard->isTooSimilar($rendered, $messages)) {
                    $failures[] = "turn:{$turnIndex} invariant:no_repeated_reply — reply is too similar to a prior bot reply";
                }
            }

            if (in_array('no_assistant_boilerplate', $invariants, true)) {
                $boilerplatePatterns = [
                    'أنا هنا للمساعدة', 'لا تتردد في طرح أي سؤال', 'لا تتردد في السؤال',
                    'في خدمتك دائم', 'في خدمتك', 'أنا مساعد رقمي',
                ];
                foreach ($boilerplatePatterns as $bp) {
                    if (str_contains($rendered, $bp)) {
                        $failures[] = "turn:{$turnIndex} invariant:no_assistant_boilerplate — contains '{$bp}'";
                        break;
                    }
                }
            }

            if (in_array('portal_lead_resolved', $invariants, true)) {
                $parsed = $portalParser->parse($text);
                if ($parsed['is_portal_lead']) {
                    // Must NOT have escalated
                    if ($ledger->escalationRequested() || (bool) ($agentReply['needs_human'] ?? false)) {
                        $failures[] = "turn:{$turnIndex} invariant:portal_lead_resolved — portal lead was escalated to human";
                    }
                }
            }

            if (in_array('bot_skipped_or_opted_out', $invariants, true)) {
                // Fixture expects the bot NOT to reply (opt-out / skip scenario).
                // Here in replay we can only check that the loop output is empty/failure.
                if (!$loopResult->failed() && !empty(trim((string) ($agentReply['say'] ?? '')))) {
                    $failures[] = "turn:{$turnIndex} invariant:bot_skipped_or_opted_out — bot produced a reply when it should have been silent";
                }
            }

            if (in_array('no_availability_claim_without_search', $invariants, true)) {
                if (!$ledger->searchWasRun()) {
                    $say  = mb_strtolower((string) ($agentReply['say'] ?? ''));
                    $avail = ['عندنا', 'لدينا', 'متوفر', 'متوفرة', 'يوجد عندنا', 'يوجد لدينا'];
                    foreach ($avail as $phrase) {
                        if (str_contains($say, $phrase)) {
                            $failures[] = "turn:{$turnIndex} invariant:no_availability_claim_without_search — '{$phrase}' claimed without running search";
                            break;
                        }
                    }
                }
            }

            // Append bot reply for next turn context
            $messages[] = AgentMessage::assistant($rendered);
        }

        return new ReplayResult(
            fixtureId: $id,
            passed:    empty($failures),
            failures:  $failures,
        );
    }

    private function populateLedger(FactLedger $ledger, array $toolCallLog): void
    {
        foreach ($toolCallLog as $call) {
            $name   = (string) ($call['name'] ?? '');
            $output = (array)  ($call['result'] ?? []);

            switch ($name) {
                case 'search_inventory':
                    $ledger->recordSearchRun(
                        hasResults:       !empty($output['results']),
                        locationRelaxed:  (bool) ($output['location_relaxed'] ?? false),
                        requestedLocation:(string) ($output['requested_location'] ?? ''),
                        relaxScope:       (string) ($output['relax_scope'] ?? 'none'),
                        requestedCityId:  isset($output['requested_city_id']) ? (int) $output['requested_city_id'] : null,
                        requestedDistrictId: isset($output['requested_district_id']) ? (int) $output['requested_district_id'] : null,
                    );
                    if (!empty($output['results'])) {
                        $ledger->addProperties($output['results']);
                    }
                    break;
                case 'get_property_details':
                case 'resolve_listing':
                    if (!empty($output['found'])) {
                        $ledger->addProperties([$output]);
                    }
                    break;
                case 'search_knowledge':
                    if (!empty($output['chunks'])) {
                        $ledger->addKnowledgeChunks($output['chunks']);
                    }
                    break;
                case 'escalate_to_human':
                    if (!empty($output['escalate'])) {
                        $ledger->recordEscalation((string) ($output['reason'] ?? 'tool'));
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
}
