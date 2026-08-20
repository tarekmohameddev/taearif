<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

use App\Domain\Ai\Agent\DTOs\AgentMessage;

/**
 * Detects when the current reply is too similar to one of the last N bot replies.
 *
 * RC6 fix: Conversations #5 and #49 sent identical sentences 4 turns in a row.
 * Any normalised-similarity above the threshold triggers a rephrase step.
 */
final class RepetitionGuard
{
    private const SIMILARITY_THRESHOLD = 0.82;
    private const LOOK_BACK_COUNT      = 5;

    // Boilerplate closing phrases to strip before comparison
    private const BOILERPLATE_PATTERNS = [
        '/أنا هنا للمساعدة[!.]*\s*/u',
        '/لا تتردد في طرح أي سؤال[!.]*\s*/u',
        '/لا تتردد في السؤال[!.]*\s*/u',
        '/في خدمتك دائماً?[!.]*\s*/u',
        '/في خدمتك[!.]*\s*/u',
        '/كن بخير[!.]*\s*/u',
        '/بالتوفيق[!.]*\s*/u',
    ];

    /**
     * Check whether the rendered reply is too similar to a recent bot reply.
     *
     * @param  AgentMessage[]  $history
     */
    public function isTooSimilar(string $renderedReply, array $history): bool
    {
        $recentBotReplies = $this->extractRecentBotReplies($history);
        if (empty($recentBotReplies)) {
            return false;
        }

        $normed = $this->normalise($renderedReply);
        if (mb_strlen($normed) < 20) {
            return false; // Very short replies — skip
        }

        foreach ($recentBotReplies as $prior) {
            $priorNormed = $this->normalise($prior);
            if (mb_strlen($priorNormed) < 20) {
                continue;
            }
            $sim = $this->similarity($normed, $priorNormed);
            if ($sim >= self::SIMILARITY_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip trailing boilerplate from a reply (used before delivery).
     */
    public function stripBoilerplate(string $text): string
    {
        foreach (self::BOILERPLATE_PATTERNS as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }
        return trim($text);
    }

    /**
     * @param AgentMessage[] $history
     * @return string[]
     */
    private function extractRecentBotReplies(array $history): array
    {
        $replies = [];
        foreach (array_reverse($history) as $msg) {
            if ($msg->role === 'assistant' && $msg->content !== null) {
                $replies[] = $msg->content;
                if (count($replies) >= self::LOOK_BACK_COUNT) {
                    break;
                }
            }
        }
        return $replies;
    }

    private function normalise(string $text): string
    {
        // Strip boilerplate, whitespace, punctuation for comparison
        $text = $this->stripBoilerplate($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[،,.\-!؟?]/u', '', $text) ?? $text;
        return mb_strtolower(trim($text));
    }

    /**
     * Token-overlap Jaccard similarity.
     */
    private function similarity(string $a, string $b): float
    {
        $tokA = array_filter(explode(' ', $a));
        $tokB = array_filter(explode(' ', $b));

        if (empty($tokA) || empty($tokB)) {
            return 0.0;
        }

        $intersection = count(array_intersect($tokA, $tokB));
        $union        = count(array_unique(array_merge($tokA, $tokB)));

        return $union > 0 ? $intersection / $union : 0.0;
    }
}
