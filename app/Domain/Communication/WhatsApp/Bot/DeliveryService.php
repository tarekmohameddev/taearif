<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Communication\Services\CommunicationServiceImpl;
use App\Domain\Communication\WhatsApp\Bot\DTOs\BotReply;
use Illuminate\Support\Facades\Log;

/**
 * Handles human-feeling delivery of bot replies:
 * - Typing indicator (best-effort)
 * - Debounce delay
 * - Message splitting (long replies → multiple messages)
 * - WhatsApp formatting (bold, numbered lists)
 */
final class DeliveryService
{
    private const TYPING_DEBOUNCE_MS  = 8_000;  // 8s debounce
    private const MAX_MESSAGE_CHARS   = 800;     // split above this
    private const MIN_SEGMENT_CHARS   = 80;      // don't split very short trailing parts
    private const CHARS_PER_WORD      = 5;       // avg Arabic word length
    private const TYPING_SPEED_WPM    = 40;      // simulated typing speed
    private const MAX_TYPING_SLEEP_MS = 4_000;   // cap at 4s sleep

    public function __construct(
        private readonly CommunicationServiceImpl $commService,
    ) {}

    /**
     * Return the WhatsApp-formatted text segments for a reply without sending.
     * Useful for sandbox mode so the caller can persist or return them.
     *
     * @return string[]
     */
    public function prepareSegments(string $replyText): array
    {
        $segments = $this->splitMessage($replyText);
        return array_map(fn (string $s) => $this->formatForWhatsApp($s), $segments);
    }

    /**
     * Deliver the bot reply to the customer.
     * Splits long replies, simulates typing, and sends each part.
     *
     * @param array<string, mixed> $messageMeta Message meta (must contain wa_number_id, to, etc.)
     */
    public function deliver(
        int $tenantId,
        int $conversationId,
        int $waNumberId,
        string $toPhone,
        BotReply $reply,
        array $messageMeta,
    ): bool {
        if ($reply->needsHuman || trim($reply->reply) === '') {
            return false;
        }

        $segments = $this->splitMessage($reply->reply);

        foreach ($segments as $i => $segment) {
            // Simulate typing indicator via sleep
            $typingMs = min(
                (int) ((mb_strlen($segment) / self::CHARS_PER_WORD / self::TYPING_SPEED_WPM) * 60 * 1000),
                self::MAX_TYPING_SLEEP_MS
            );

            if ($i === 0) {
                // First message: debounce before sending
                usleep(self::TYPING_DEBOUNCE_MS * 1000);
            } else {
                usleep($typingMs * 1000);
            }

            try {
                $this->commService->sendMessage(
                    userId: $tenantId,
                    conversationId: $conversationId,
                    content: $this->formatForWhatsApp($segment),
                    meta: array_merge($messageMeta, [
                        'wa_number_id' => $waNumberId,
                        'source'       => 'ai',
                        'bot_segment'  => $i + 1,
                    ]),
                );
            } catch (\Throwable $e) {
                Log::error('bot.delivery.send_failed', [
                    'conversation_id' => $conversationId,
                    'segment' => $i + 1,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    /** @return string[] */
    private function splitMessage(string $text): array
    {
        $text = trim($text);
        if (mb_strlen($text) <= self::MAX_MESSAGE_CHARS) {
            return [$text];
        }

        // Split at double newlines (paragraph breaks) first
        $paragraphs = preg_split('/\n{2,}/u', $text) ?: [$text];
        $segments = [];
        $buffer = '';

        foreach ($paragraphs as $para) {
            $para = trim((string) $para);
            if ($para === '') { continue; }

            if (mb_strlen($buffer) + mb_strlen($para) + 2 <= self::MAX_MESSAGE_CHARS) {
                $buffer = $buffer !== '' ? $buffer . "\n\n" . $para : $para;
            } else {
                if (mb_strlen($buffer) >= self::MIN_SEGMENT_CHARS) {
                    $segments[] = $buffer;
                    $buffer = $para;
                } else {
                    $buffer = $buffer !== '' ? $buffer . "\n\n" . $para : $para;
                }
            }
        }
        if ($buffer !== '') { $segments[] = $buffer; }

        return $segments ?: [$text];
    }

    private function formatForWhatsApp(string $text): string
    {
        // Convert markdown-style ** bold to * (WhatsApp uses single asterisk)
        $text = (string) preg_replace('/\*\*(.+?)\*\*/u', '*$1*', $text);

        // Convert ## headings to bold text
        $text = (string) preg_replace('/^#{1,3}\s+(.+)$/mu', '*$1*', $text);

        // Convert dash bullets to numbered lines if there are many
        if (preg_match_all('/^- /m', $text) >= 3) {
            $counter = 1;
            $text = (string) preg_replace_callback('/^- (.+)$/mu', function ($m) use (&$counter) {
                return ($counter++) . '. ' . $m[1];
            }, $text);
        }

        return trim($text);
    }
}
