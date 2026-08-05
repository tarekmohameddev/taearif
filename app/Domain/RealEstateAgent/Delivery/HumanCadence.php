<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Delivery;

use App\Domain\Communication\DTOs\SendMessageDto;
use App\Domain\Communication\Services\CommunicationServiceImpl;
use App\Domain\Communication\Support\CommunicationEndpoints;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends a rendered reply to the customer with human-feeling timing.
 *
 * Wraps the existing delivery mechanics (split, format, debounce, typing sleep)
 * while integrating with the new trace-based delivery-status tracking.
 */
final class HumanCadence
{
    private const MAX_CHARS         = 800;
    private const MIN_SEGMENT_CHARS = 80;
    private const CHARS_PER_WORD    = 5;
    private const WPM               = 40;
    private const MAX_TYPING_MS     = 4_000;

    // Debounce: clamp(1.5s + inboundLength factor, 2s..7s) ± 25% jitter (RC6 fix)
    private const DEBOUNCE_BASE_MS  = 1_500;
    private const DEBOUNCE_MIN_MS   = 2_000;
    private const DEBOUNCE_MAX_MS   = 7_000;
    private const DEBOUNCE_JITTER   = 0.25;

    public function __construct(
        private readonly CommunicationServiceImpl $commService,
    ) {}

    /**
     * Prepare formatted segments without sending (sandbox / shadow use).
     *
     * @return string[]
     */
    public function prepareSegments(string $text): array
    {
        return array_map(
            fn (string $s) => $this->formatForWhatsApp($s),
            $this->split($text)
        );
    }

    /**
     * Send the reply to the customer and return true on success.
     *
     * @param  array<string, mixed> $meta  Must contain wa_number_id and to.
     */
    public function send(
        int    $tenantId,
        int    $conversationId,
        int    $waNumberId,
        string $toPhone,
        string $renderedReply,
        array  $meta,
    ): bool {
        if (trim($renderedReply) === '') {
            return false;
        }

        // Jittered debounce based on inbound message length
        $inboundLen = isset($meta['inbound_length']) ? (int) $meta['inbound_length'] : 40;
        $debounceMs = $this->computeDebounceMs($inboundLen);
        usleep($debounceMs * 1000);

        $segments = $this->prepareSegments($renderedReply);

        foreach ($segments as $i => $segment) {
            // Typing indicator + delay before every segment (including the first)
            $typingMs = min(
                (int) round(mb_strlen($segment) / self::CHARS_PER_WORD / self::WPM * 60 * 1000),
                self::MAX_TYPING_MS
            );
            if ($typingMs > 0) {
                usleep($typingMs * 1000);
            }

            if ($i > 0) {
                // Brief inter-segment pause (already covered by typing delay above)
            }

            $dto = new SendMessageDto(
                userId:            $tenantId,
                conversationId:    $conversationId,
                content:           $segment,
                channel:           'whatsapp',
                waNumberId:        $waNumberId > 0 ? $waNumberId : null,
                endpointSignature: CommunicationEndpoints::WHATSAPP_SEND_MESSAGE,
                templateId:        null,
                variables:         null,
                extraMeta:         array_merge($meta, [
                    'source'     => 'ai',
                    'bot_segment'=> $i + 1,
                    'to'         => $toPhone,
                ]),
            );

            try {
                $this->commService->sendMessage($dto, 'bot-deliver:' . $conversationId . ':' . $i . ':' . Str::uuid());
            } catch (\Throwable $e) {
                Log::error('agent.delivery.send_failed', [
                    'conversation_id' => $conversationId,
                    'segment'         => $i + 1,
                    'error'           => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    // ────────────────────────────────────────────────────────────
    // Timing helpers
    // ────────────────────────────────────────────────────────────

    private function computeDebounceMs(int $inboundLength): int
    {
        // Base: 1.5s + 30ms per character of inbound text (capped at 7s)
        $base = self::DEBOUNCE_BASE_MS + ($inboundLength * 30);
        $base = max(self::DEBOUNCE_MIN_MS, min(self::DEBOUNCE_MAX_MS, $base));

        // ±25% jitter
        $jitterRange = (int) round($base * self::DEBOUNCE_JITTER);
        $jitter      = random_int(-$jitterRange, $jitterRange);

        return max(self::DEBOUNCE_MIN_MS, $base + $jitter);
    }

    // ────────────────────────────────────────────────────────────
    // Formatting
    // ────────────────────────────────────────────────────────────

    private function formatForWhatsApp(string $text): string
    {
        // Convert markdown bold (**text**) to WhatsApp bold (*text*)
        $text = preg_replace('/\*\*(.+?)\*\*/u', '*$1*', $text) ?? $text;
        // Convert markdown headings to bold
        $text = preg_replace('/^#{1,6}\s+(.+)$/um', '*$1*', $text) ?? $text;
        // Convert bullet lists (3+ items) to numbered Arabic
        $text = $this->convertBulletList($text);
        return $text;
    }

    private function convertBulletList(string $text): string
    {
        $lines  = explode("\n", $text);
        $bullets = [];
        $result  = [];
        $flush   = function () use (&$bullets, &$result): void {
            if (count($bullets) >= 3) {
                foreach ($bullets as $i => $b) {
                    $result[] = ($i + 1) . '. ' . $b;
                }
            } else {
                foreach ($bullets as $b) {
                    $result[] = '- ' . $b;
                }
            }
            $bullets = [];
        };

        foreach ($lines as $line) {
            if (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
                $bullets[] = $m[1];
            } else {
                if ($bullets) {
                    $flush();
                }
                $result[] = $line;
            }
        }
        if ($bullets) {
            $flush();
        }

        return implode("\n", $result);
    }

    private function split(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_CHARS) {
            return [$text];
        }

        $segments   = [];
        $paragraphs = preg_split('/\n{2,}/', $text) ?: [$text];
        $current    = '';

        foreach ($paragraphs as $paragraph) {
            $candidate = ($current === '' ? '' : $current . "\n\n") . $paragraph;

            if (mb_strlen($candidate) > self::MAX_CHARS && $current !== '') {
                $segments[] = $current;
                $current    = $paragraph;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            // If last segment is tiny, merge with previous
            if (
                count($segments) > 0 &&
                mb_strlen($current) < self::MIN_SEGMENT_CHARS
            ) {
                $segments[count($segments) - 1] .= "\n\n" . $current;
            } else {
                $segments[] = $current;
            }
        }

        return $segments ?: [$text];
    }
}
