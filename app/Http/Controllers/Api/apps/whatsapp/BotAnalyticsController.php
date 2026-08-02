<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Domain\Communication\WhatsApp\Bot\SandboxService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\ResetBotSandboxRequest;
use App\Http\Requests\Api\V1\WhatsApp\SimulateBotTurnRequest;
use App\Models\AiEvalRun;
use App\Models\AiUsageLog;
use App\Models\BotUnansweredQuestion;
use App\Models\ShadowBotDraft;
use App\Models\WaConversationAiState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotAnalyticsController extends BaseApiController
{
    public function __construct(
        private readonly SandboxService $sandbox,
    ) {}

    /**
     * Dashboard summary for the bot quality loop.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $period = $request->get('period', '30d');
        $since  = $this->periodToDate($period);

        $usageSummary = AiUsageLog::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('SUM(tokens_in + tokens_out) as total_tokens'),
                DB::raw('SUM(cost_micros) as total_cost_micros'),
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('AVG(latency_ms) as avg_latency_ms'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_calls')
            )
            ->first();

        $shadowSummary = ShadowBotDraft::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('COUNT(*) as total_drafts'),
                DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN status = "edited" THEN 1 ELSE 0 END) as edited'),
                DB::raw('SUM(CASE WHEN status = "discarded" THEN 1 ELSE 0 END) as discarded'),
                DB::raw('AVG(confidence) as avg_confidence')
            )
            ->first();

        $handoffSummary = WaConversationAiState::where('user_id', $userId)
            ->whereNotNull('handoff_reason')
            ->where('updated_at', '>=', $since)
            ->select('handoff_reason', DB::raw('COUNT(*) as count'))
            ->groupBy('handoff_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topUnanswered = BotUnansweredQuestion::where('user_id', $userId)
            ->where('added_to_faq', false)
            ->orderByDesc('occurrence_count')
            ->limit(20)
            ->get(['question', 'occurrence_count', 'cluster_key', 'id']);

        $lastEval = AiEvalRun::orderByDesc('created_at')->first();

        return response()->json([
            'period'        => $period,
            'since'         => $since->toIso8601String(),
            'usage'         => [
                'total_tokens'    => (int) ($usageSummary->total_tokens ?? 0),
                'total_calls'     => (int) ($usageSummary->total_calls ?? 0),
                'failed_calls'    => (int) ($usageSummary->failed_calls ?? 0),
                'avg_latency_ms'  => round((float) ($usageSummary->avg_latency_ms ?? 0)),
                'cost_usd'        => round((float) ($usageSummary->total_cost_micros ?? 0) / 1_000_000, 4),
            ],
            'shadow'        => [
                'total'          => (int) ($shadowSummary->total_drafts ?? 0),
                'approved'       => (int) ($shadowSummary->approved ?? 0),
                'edited'         => (int) ($shadowSummary->edited ?? 0),
                'discarded'      => (int) ($shadowSummary->discarded ?? 0),
                'avg_confidence' => round((float) ($shadowSummary->avg_confidence ?? 0)),
                'edit_rate_pct'  => $shadowSummary->total_drafts > 0
                    ? round((($shadowSummary->edited + $shadowSummary->discarded) / $shadowSummary->total_drafts) * 100, 1)
                    : null,
            ],
            'handoff_reasons' => $handoffSummary,
            'top_unanswered'  => $topUnanswered,
            'last_eval'       => $lastEval ? [
                'run_id'       => $lastEval->run_id,
                'passed'       => $lastEval->passed,
                'scores'       => $lastEval->scores,
                'passed_turns' => $lastEval->passed_turns,
                'total_turns'  => $lastEval->total_turns,
                'created_at'   => $lastEval->created_at->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Mark an unanswered question as added to FAQ (acknowledged).
     */
    public function markFaqAdded(int $id): JsonResponse
    {
        $question = BotUnansweredQuestion::where('user_id', auth()->id())->findOrFail($id);
        $question->update(['added_to_faq' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Shadow draft inbox — pending drafts awaiting agent decision.
     */
    public function shadowDrafts(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $drafts = ShadowBotDraft::where('user_id', $userId)
            ->where('status', 'pending')
            ->with([])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($drafts);
    }

    /**
     * Agent approves / edits / discards a shadow draft.
     */
    public function actOnDraft(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action'      => 'required|in:approved,edited,discarded',
            'agent_reply' => 'nullable|string|max:2000',
        ]);

        $draft = ShadowBotDraft::where('user_id', auth()->id())->findOrFail($id);
        if (! $draft->isPending()) {
            return response()->json(['error' => 'Draft is no longer pending.'], 409);
        }

        $draft->update([
            'status'      => $request->input('action'),
            'agent_reply' => $request->input('agent_reply'),
            'agent_id'    => auth()->id(),
            'acted_at'    => now(),
        ]);

        return response()->json(['success' => true, 'status' => $draft->status]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sandbox Simulator
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Run one bot turn in sandbox (dry-run) mode.
     *
     * The full BotOrchestrator pipeline executes (compliance, context, generation,
     * grounding, handoff, disclosure, facts update) but nothing is sent to WhatsApp
     * and no credits are deducted. All turns are persisted in an isolated sandbox
     * conversation so multi-turn context works correctly.
     */
    public function simulate(SimulateBotTurnRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();

        if ($tenantId !== (int) auth()->id() && ! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        try {
            $payload = $this->sandbox->runTurn(
                tenantId: $tenantId,
                waNumberId: (int) $request->input('wa_number_id'),
                phone: $request->customerPhone(),
                messageText: (string) $request->input('message'),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if ($request->includeTranscript()) {
            $payload['transcript'] = $this->sandbox->transcript(
                tenantId: $tenantId,
                waNumberId: (int) $request->input('wa_number_id'),
                phone: $request->customerPhone(),
            );
        }

        return response()->json($payload);
    }

    /**
     * Return the full transcript of the sandbox conversation for the given number + phone.
     */
    public function simulationTranscript(Request $request): JsonResponse
    {
        $request->validate([
            'wa_number_id'   => 'required|integer|min:1',
            'customer_phone' => 'nullable|string|max:30',
            'tenant_id'      => 'nullable|integer|min:1',
        ]);

        $tenantId = (int) ($request->input('tenant_id') ?? auth()->id());

        if ($tenantId !== (int) auth()->id() && ! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $phone = (string) ($request->input('customer_phone') ?: '+966500000001');

        $transcript = $this->sandbox->transcript(
            tenantId: $tenantId,
            waNumberId: (int) $request->input('wa_number_id'),
            phone: $phone,
        );

        return response()->json($transcript);
    }

    /**
     * Clear the sandbox conversation (messages, AI state, customer profile) so a
     * fresh multi-turn test can begin from scratch.
     */
    public function resetSimulation(ResetBotSandboxRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();

        if ($tenantId !== (int) auth()->id() && ! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $cleared = $this->sandbox->reset(
            tenantId: $tenantId,
            waNumberId: (int) $request->input('wa_number_id'),
            phone: $request->customerPhone(),
        );

        return response()->json([
            'success'  => true,
            'cleared'  => $cleared,
            'message'  => $cleared
                ? 'Sandbox conversation reset. You can start a fresh simulation.'
                : 'No sandbox conversation found — nothing to reset.',
        ]);
    }

    private function periodToDate(string $period): \Carbon\Carbon
    {
        return match ($period) {
            '7d'  => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
}
