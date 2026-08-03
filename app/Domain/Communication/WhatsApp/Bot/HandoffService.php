<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Models\WaConversationAiState;
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
 * Auto-resume: if no agent reply for RESUME_AFTER_HOURS, the bot resumes.
 */
final class HandoffService
{
    private const PAUSE_DURATION_HOURS = 24;
    private const RESUME_AFTER_HOURS   = 48;

    /** @var callable[] */
    private array $notifiers = [];

    public function pauseBot(WaConversationAiState $state, string $reason, int $hours = self::PAUSE_DURATION_HOURS): void
    {
        $state->update([
            'bot_paused_until' => now()->addHours($hours),
            'handoff_reason'   => $reason,
        ]);

        Log::info('bot.handoff.paused', [
            'conversation_id' => $state->conversation_id,
            'reason'          => $reason,
            'until'           => now()->addHours($hours)->toIso8601String(),
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
     * Called when an agent sends a manual reply — pauses bot to avoid double-reply.
     */
    public function handleAgentReply(WaConversationAiState $state): void
    {
        // Pause bot for RESUME_AFTER_HOURS unless agent explicitly hands back
        if (! $state->isBotPaused()) {
            $this->pauseBot($state, 'agent_takeover', self::RESUME_AFTER_HOURS);
        }
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
