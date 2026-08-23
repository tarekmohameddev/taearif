<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Handles bot pause, handoff-to-human, and automatic resume logic.
 *
 * The bot is paused when:
 * - Confidence < threshold
 * - Grounding verification fails
 * - Customer explicitly requests a human
 * - Regulated topic detected
 * - Agent sends a manual reply in the conversation (auto-pause)
 *
 * Pause duration for agent replies is controlled by the WaAiConfig.agent_reply_pause
 * setting: off | 24h | 48h | indefinite.
 */
final class HandoffService
{
    /** Default hours for non-agent-takeover pauses (compliance, media, etc.) */
    private const PAUSE_DURATION_HOURS = 24;

    /**
     * Far-future sentinel used when pause mode is "indefinite".
     * Reuses existing isBotPaused() logic without a schema change.
     *
     * MySQL TIMESTAMP max is 2038-01-19; we use 2038-01-01 to stay safely within range.
     */
    private const INDEFINITE_SENTINEL = '2038-01-01 00:00:00';

    /** @var callable[] */
    private array $notifiers = [];

    public function pauseBot(WaConversationAiState $state, string $reason, int $hours = self::PAUSE_DURATION_HOURS): void
    {
        $until = now()->addHours($hours);

        $state->update([
            'bot_paused_until' => $until,
            'handoff_reason'   => $reason,
        ]);

        Log::info('bot.handoff.paused', [
            'conversation_id' => $state->conversation_id,
            'reason'          => $reason,
            'until'           => $until->toIso8601String(),
        ]);

        $this->notifyAgents($state, $reason);
    }

    /**
     * Pause indefinitely (until manually resumed).
     * Sets bot_paused_until to a far-future date so isBotPaused() stays true.
     */
    public function pauseBotIndefinitely(WaConversationAiState $state, string $reason): void
    {
        $until = Carbon::parse(self::INDEFINITE_SENTINEL);

        $state->update([
            'bot_paused_until' => $until,
            'handoff_reason'   => $reason,
        ]);

        Log::info('bot.handoff.paused_indefinitely', [
            'conversation_id' => $state->conversation_id,
            'reason'          => $reason,
        ]);

        $this->notifyAgents($state, $reason);
    }

    public function resumeBot(WaConversationAiState $state): void
    {
        $state->update([
            'bot_paused_until' => null,
            'handoff_reason'   => null,
        ]);

        Log::info('bot.handoff.resumed', [
            'conversation_id' => $state->conversation_id,
        ]);
    }

    /**
     * Called when a human agent sends a manual reply.
     * Pause duration is controlled by the tenant's agent_reply_pause config:
     *   - off       → no pause
     *   - 24h       → pause 24 hours (refreshed on each subsequent agent reply)
     *   - 48h       → pause 48 hours (refreshed on each subsequent agent reply)
     *   - indefinite → pause until manual resume
     *
     * @param string $pauseMode one of: off|24h|48h|indefinite
     */
    public function handleAgentReply(WaConversationAiState $state, string $pauseMode = '48h'): void
    {
        match ($pauseMode) {
            'off'        => null,
            '24h'        => $this->pauseBot($state, 'agent_takeover', 24),
            '48h'        => $this->pauseBot($state, 'agent_takeover', 48),
            'indefinite' => $this->pauseBotIndefinitely($state, 'agent_takeover'),
            default      => $this->pauseBot($state, 'agent_takeover', 48),
        };
    }

    /**
     * Convenience method — looks up AI state and config, then pauses the bot
     * if the tenant's agent_reply_pause setting says so.
     *
     * Safe to call on any human send; no-ops if AI state doesn't exist yet.
     *
     * @param int      $conversationId  Communication conversation_id
     * @param int|null $waNumberId      WA number ID used to load tenant config
     * @param int      $tenantUserId    Tenant owner user_id
     */
    public function pauseAfterHumanSend(int $conversationId, ?int $waNumberId, int $tenantUserId): void
    {
        $aiState = WaConversationAiState::where('conversation_id', $conversationId)->first();
        if ($aiState === null) {
            return;
        }

        $pauseMode = '48h';
        if ($waNumberId !== null) {
            $config = WaAiConfig::where('user_id', $tenantUserId)
                ->where('wa_number_id', $waNumberId)
                ->first();
            if ($config !== null) {
                $pauseMode = (string) ($config->agent_reply_pause ?? '48h');
            }
        }

        $this->handleAgentReply($aiState, $pauseMode);
    }

    public function shouldHandoff(
        int $confidence,
        bool $groundingFailed,
        string $intent,
        int $failedTurnsWithoutResolution,
        int $confidenceThreshold = 40,
        ?array $escalationRules = null,
    ): bool {
        if ($confidence < $confidenceThreshold) { return true; }
        if ($groundingFailed) { return true; }
        if ($intent === 'complaint') { return true; }
        if ($failedTurnsWithoutResolution >= 3) { return true; }

        // Check tenant-configured escalation rules (e.g. {"on_intent": ["financing"]})
        if (! empty($escalationRules['on_intent']) && in_array($intent, $escalationRules['on_intent'], true)) {
            return true;
        }

        return false;
    }

    public function detectFrustration(string $messageText): bool
    {
        $frustrationKeywords = [
            'ما ساعد', 'ما ساعدت', 'ما فهم', 'بطيء', 'محبط',
            'متضايق', 'غلط', 'خطأ', 'مشكلة', 'زبالة', 'ridiculous',
        ];
        $lower = mb_strtolower($messageText);
        foreach ($frustrationKeywords as $kw) {
            if (str_contains($lower, $kw)) { return true; }
        }
        return false;
    }

    private function notifyAgents(WaConversationAiState $state, string $reason): void
    {
        // Dispatches a notification event — agents see it in their inbox
        try {
            event(new \App\Events\BotHandoffRequested(
                conversationId: $state->conversation_id,
                userId: $state->user_id,
                reason: $reason,
            ));
        } catch (\Throwable $e) {
            // Swallow: notification failure should not block bot flow
            Log::warning('bot.handoff.notify_failed', ['error' => $e->getMessage()]);
        }
    }
}
