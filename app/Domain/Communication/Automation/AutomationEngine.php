<?php

namespace App\Domain\Communication\Automation;

use App\Domain\Communication\Services\AIResponderService;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AutomationEngine
{
    private const DEDUPE_TTL_SECONDS = 86400; // 24h

    public function __construct(
        private readonly AIResponderService $aiResponderService
    ) {
    }

    public function handleMessageReceived(Message $message): void
    {
        try {
            if (! config('communication.enabled', false)) {
                $this->logSkipped('communication_disabled', $message);
                return;
            }
            if (! config('communication.automation.enabled', false)) {
                $this->logSkipped('automation_disabled', $message);
                return;
            }

            $channel = strtolower((string) ($message->conversation->channel ?? ''));
            if ($channel !== 'whatsapp') {
                $this->logSkipped('unsupported_channel', $message);
                return;
            }

            $meta = is_array($message->meta) ? $message->meta : [];
            if (($meta['source'] ?? null) === 'ai') {
                $this->logSkipped('ai_loop_guard', $message);
                return;
            }

            $dedupeKey = 'ai-suggest-message:' . $message->id;
            if (Cache::has($dedupeKey)) {
                $this->logSkipped('message_already_processed', $message);
                return;
            }

            $conversationId = (int) $message->conversation_id;
            $throttleKey = 'ai-suggest-conversation:' . $conversationId;
            $attempts = (int) config('communication.automation.ai_rate_limit_attempts', 5);
            $windowSeconds = (int) config('communication.automation.ai_rate_limit_window_seconds', 60);
            if (RateLimiter::tooManyAttempts($throttleKey, $attempts)) {
                $this->logSkipped('rate_limited', $message);
                return;
            }

            if (! config('communication.ai.enabled', false)) {
                $this->logSkipped('ai_disabled', $message);
                return;
            }

            RateLimiter::hit($throttleKey, $windowSeconds);
            Cache::put($dedupeKey, true, self::DEDUPE_TTL_SECONDS);

            $suggestion = $this->aiResponderService->suggestReply($message, []);
            if ($suggestion === null || $suggestion === '') {
                Log::info('communication.automation.suggestion.skipped', [
                    'reason' => 'no_suggestion',
                    'user_id' => $message->user_id,
                    'conversation_id' => $message->conversation_id,
                    'message_id' => $message->id,
                    'channel' => $channel,
                ]);
                return;
            }

            Log::info('communication.automation.suggestion.generated', [
                'user_id' => $message->user_id,
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
                'channel' => $channel,
                'suggestion_length' => mb_strlen($suggestion),
                'suggestion_hash' => substr(md5($suggestion), 0, 8),
            ]);
        } catch (\Throwable $e) {
            Log::warning('communication.automation.suggestion.failed', [
                'user_id' => $message->user_id ?? null,
                'conversation_id' => $message->conversation_id ?? null,
                'message_id' => $message->id ?? null,
                'channel' => $message->conversation->channel ?? null,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    public function handleMessageSent(Message $message): void
    {
        try {
            // No-op for now; future: analytics or side effects.
        } catch (\Throwable $e) {
            Log::warning('communication.automation.suggestion.failed', [
                'handler' => 'handleMessageSent',
                'message_id' => $message->id ?? null,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    public function handleConversationOpened(Conversation $conversation, Message $firstMessage): void
    {
        try {
            // No-op for now; future: welcome automation or analytics.
        } catch (\Throwable $e) {
            Log::warning('communication.automation.suggestion.failed', [
                'handler' => 'handleConversationOpened',
                'conversation_id' => $conversation->id ?? null,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function logSkipped(string $reason, Message $message): void
    {
        Log::info('communication.automation.suggestion.skipped', [
            'reason' => $reason,
            'user_id' => $message->user_id,
            'conversation_id' => $message->conversation_id,
            'message_id' => $message->id,
            'channel' => $message->conversation->channel ?? 'unknown',
        ]);
    }
}
