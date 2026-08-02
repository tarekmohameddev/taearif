<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Communication\WhatsApp\Bot\DTOs\BotTurnResult;
use App\Models\AiCustomerProfile;
use App\Models\BotUnansweredQuestion;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ShadowBotDraft;
use App\Models\WaConversationAiState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages sandbox (dry-run) conversations for the bot simulator.
 *
 * Each tenant + wa_number + phone triple gets an isolated Conversation with
 * channel = 'whatsapp_sandbox'. These conversations never appear in the
 * agent inbox (WhatsAppConversationService filters on channel = 'whatsapp'
 * and requires a wa_conversation_states row, which we never create).
 *
 * Messages are written via Message::create() directly so no MessageReceived
 * event is fired, no credits are deducted, and the AutomationEngine is not
 * triggered.
 */
final class SandboxService
{
    private const SANDBOX_CHANNEL = 'whatsapp_sandbox';
    private const SANDBOX_PHONE_DEFAULT = '+966500000001';

    public function __construct(
        private readonly BotOrchestrator $orchestrator,
    ) {}

    /**
     * Return the sandbox Conversation for the given tenant + number + phone,
     * creating it if it does not yet exist.
     */
    public function conversationFor(int $tenantId, int $waNumberId, string $phone): Conversation
    {
        $identifier = 'sandbox:' . $waNumberId . ':' . ltrim($phone, '+');

        return Conversation::firstOrCreate(
            [
                'user_id'                    => $tenantId,
                'channel'                    => self::SANDBOX_CHANNEL,
                'external_party_identifier'  => $identifier,
            ],
            ['last_message_at' => now()]
        );
    }

    /**
     * Persist an inbound customer message, run the full bot pipeline in sandbox
     * mode, persist the outbound reply segment(s), and return the full result.
     *
     * @return array<string, mixed>
     */
    public function runTurn(
        int $tenantId,
        int $waNumberId,
        string $phone,
        string $messageText,
    ): array {
        $conversation = $this->conversationFor($tenantId, $waNumberId, $phone);
        $conversationId = (int) $conversation->id;

        // Persist the inbound message directly (no events fired)
        $inbound = Message::create([
            'conversation_id' => $conversationId,
            'user_id'         => $tenantId,
            'content'         => $messageText,
            'direction'       => 'inbound',
            'status'          => 'received',
            'meta'            => [
                'source'      => 'sandbox',
                'wa_number_id'=> $waNumberId,
                'from'        => $phone,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Run the full orchestrator pipeline in sandbox/dry-run mode
        try {
            $result = $this->orchestrator->handleSandbox(
                tenantId: $tenantId,
                conversationId: $conversationId,
                waNumberId: $waNumberId,
                customerPhone: $phone,
                triggerMessage: $inbound,
            );
        } catch (\Throwable $e) {
            Log::error('sandbox.run_turn.orchestrator_error', [
                'conversation_id' => $conversationId,
                'error'           => $e->getMessage(),
            ]);
            return [
                'error'           => $e->getMessage(),
                'conversation_id' => $conversationId,
            ];
        }

        // Persist each outbound segment as a Message (no provider send)
        $outboundIds = [];
        foreach ($result->botSegments as $i => $segment) {
            $outbound = Message::create([
                'conversation_id' => $conversationId,
                'user_id'         => $tenantId,
                'content'         => $segment,
                'direction'       => 'outbound',
                'status'          => 'delivered',
                'meta'            => [
                    'source'       => 'sandbox',
                    'wa_number_id' => $waNumberId,
                    'bot_segment'  => $i + 1,
                    'outcome'      => $result->outcome,
                ],
            ]);
            $outboundIds[] = $outbound->id;
        }

        $conversation->update(['last_message_at' => now()]);

        // Count messages for turn index
        $turnIndex = (int) Message::where('conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->count();

        // Load fresh AI state for metadata
        $aiState = WaConversationAiState::where('conversation_id', $conversationId)->first();

        return $this->buildTurnResponse($result, $conversation, $turnIndex, $aiState);
    }

    /**
     * Return the full transcript for the sandbox conversation (messages + AI state snapshot).
     *
     * @return array<string, mixed>
     */
    public function transcript(int $tenantId, int $waNumberId, string $phone): array
    {
        $conversation = Conversation::where('user_id', $tenantId)
            ->where('channel', self::SANDBOX_CHANNEL)
            ->where('external_party_identifier', 'sandbox:' . $waNumberId . ':' . ltrim($phone, '+'))
            ->first();

        if ($conversation === null) {
            return [
                'conversation_id' => null,
                'messages'        => [],
                'ai_state'        => null,
                'turn_count'      => 0,
            ];
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->get(['id', 'direction', 'content', 'status', 'meta', 'created_at'])
            ->map(fn ($m) => [
                'id'         => $m->id,
                'direction'  => $m->direction,
                'content'    => $m->content,
                'status'     => $m->status,
                'segment'    => ($m->meta['bot_segment'] ?? null),
                'outcome'    => ($m->meta['outcome'] ?? null),
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->all();

        $aiState = WaConversationAiState::where('conversation_id', $conversation->id)->first();

        return [
            'conversation_id' => $conversation->id,
            'messages'        => $messages,
            'turn_count'      => (int) collect($messages)->where('direction', 'inbound')->count(),
            'ai_state'        => $aiState ? [
                'facts'               => $aiState->facts,
                'situation'           => $aiState->situation,
                'requirements'        => $aiState->requirements,
                'commitments'         => $aiState->commitments,
                'objections'          => $aiState->objections,
                'tone'                => $aiState->tone,
                'opt_out_status'      => $aiState->opt_out_status,
                'bot_paused_until'    => $aiState->bot_paused_until?->toIso8601String(),
                'handoff_reason'      => $aiState->handoff_reason,
                'disclosed_as_assistant' => $aiState->disclosed_as_assistant,
            ] : null,
        ];
    }

    /**
     * Delete every trace of the sandbox conversation so a fresh test can begin.
     *
     * Deleted: messages, wa_conversation_ai_states, ai_customer_profiles (sandbox phone),
     *          shadow_bot_drafts for conversation, bot_unanswered_questions for conversation,
     *          the Conversation row itself, and the loop-guard cache key.
     */
    public function reset(int $tenantId, int $waNumberId, string $phone): bool
    {
        $identifier = 'sandbox:' . $waNumberId . ':' . ltrim($phone, '+');

        $conversation = Conversation::where('user_id', $tenantId)
            ->where('channel', self::SANDBOX_CHANNEL)
            ->where('external_party_identifier', $identifier)
            ->first();

        if ($conversation === null) {
            return false; // Nothing to reset
        }

        $conversationId = (int) $conversation->id;

        DB::transaction(function () use ($conversationId, $tenantId, $phone, $conversation) {
            Message::where('conversation_id', $conversationId)->delete();

            $aiState = WaConversationAiState::where('conversation_id', $conversationId)->first();
            if ($aiState !== null) {
                $aiState->delete();
            }

            ShadowBotDraft::where('conversation_id', $conversationId)->delete();
            BotUnansweredQuestion::where('conversation_id', $conversationId)->delete();

            // Remove sandbox customer profile (identified by the sandbox phone)
            AiCustomerProfile::where('user_id', $tenantId)
                ->where('phone', $phone)
                ->delete();

            $conversation->delete();
        });

        // Clear loop-guard cache (best-effort after transaction)
        Cache::forget('bot.loop.conv.' . $conversationId);

        Log::info('sandbox.reset', [
            'tenant_id'       => $tenantId,
            'conversation_id' => $conversationId,
        ]);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function buildTurnResponse(
        BotTurnResult $result,
        Conversation $conversation,
        int $turnIndex,
        ?WaConversationAiState $aiState,
    ): array {
        return [
            // Backwards-compatible keys
            'reply'           => $result->replyText,
            'confidence'      => $result->botReply?->confidence,
            'needs_human'     => $result->botReply?->needsHuman,
            'handoff_reason'  => $result->botReply?->handoffReason,
            'facts_update'    => $result->botReply?->factsUpdate,
            'next_question'   => $result->botReply?->nextQuestion,
            'grounding'       => [
                'passed'       => $result->groundingResult?->passed,
                'failed_claims'=> $result->groundingResult?->failedClaims ?? [],
            ],
            'style'           => [
                'passed' => $result->styleResult?->passed,
                'issues' => $result->styleResult?->issues ?? [],
            ],
            'intent'          => $result->intent,
            'difficulty'      => $result->difficulty,
            'kb_chunks_used'  => $result->kbChunksUsed,
            'properties_found'=> $result->propertiesFound,
            'tokens'          => ['in' => $result->tokensIn, 'out' => $result->tokensOut],

            // New sandbox-specific keys
            'conversation_id' => $conversation->id,
            'turn_index'      => $turnIndex,
            'outcome'         => $result->outcome,
            'skip_reason'     => $result->skipReason,
            'bot_messages'    => $result->botSegments,
            'trace'           => $result->trace,
            'bot_paused_until'=> $aiState?->bot_paused_until?->toIso8601String(),
            'handoff_reason_state' => $aiState?->handoff_reason,
            'facts'           => $aiState?->facts,
            'opt_out_status'  => $aiState?->opt_out_status,
        ];
    }
}
