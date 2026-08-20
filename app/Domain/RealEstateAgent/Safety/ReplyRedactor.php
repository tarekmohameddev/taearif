<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

/**
 * Last-resort repair for replies that still have citation violations after one retry.
 *
 * Instead of always escalating to a human (which was the previous behaviour),
 * we attempt to surgically remove the offending clause and deliver what remains.
 * A handoff only happens when the redacted reply is empty.
 *
 * Strategy:
 *  1. Split the reply into sentences (Arabic sentence endings: . ؟ ! \n).
 *  2. For each violation (bare number / unresolved placeholder), find the sentence
 *     that contains it and drop that sentence.
 *  3. If any text remains, deliver it. Otherwise handoff.
 */
final class ReplyRedactor
{
    private const SENTENCE_PATTERN = '/(?<=[.؟!؟\n])\s+|(?<!\d)\.(?!\d)|\n+/u';

    /**
     * @param  string  $reply       The raw `say` value from the model.
     * @param  array<string>  $violations  Each violation string from CitationGuard.
     * @return array{redacted: string, was_emptied: bool}
     */
    public function redact(string $reply, array $violations): array
    {
        if (empty($violations)) {
            return ['redacted' => $reply, 'was_emptied' => false];
        }

        $sentences = $this->splitSentences($reply);
        $survivors = [];

        foreach ($sentences as $sentence) {
            $drop = false;
            foreach ($violations as $v) {
                // Violation strings are like "bare_number:500000" or "unresolved_placeholder:{{p:X|price}}"
                $offending = $this->extractOffendingFragment($v);
                if ($offending !== '' && str_contains($sentence, $offending)) {
                    $drop = true;
                    break;
                }
            }
            if (!$drop && trim($sentence) !== '') {
                $survivors[] = $sentence;
            }
        }

        $redacted    = trim(implode(' ', $survivors));
        $wasEmptied  = $redacted === '';

        return ['redacted' => $redacted, 'was_emptied' => $wasEmptied];
    }

    private function splitSentences(string $text): array
    {
        // Split on sentence terminators, keeping delimiters attached
        $parts = preg_split('/(?<=[.؟!،\n])/u', $text) ?: [$text];
        return array_filter(array_map('trim', $parts), fn ($s) => $s !== '');
    }

    private function extractOffendingFragment(string $violation): string
    {
        // Violation format: "type:value" or just "value"
        if (str_contains($violation, ':')) {
            [, $value] = explode(':', $violation, 2);
            return $value;
        }
        return $violation;
    }
}
