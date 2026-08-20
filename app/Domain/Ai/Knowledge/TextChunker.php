<?php

declare(strict_types=1);

namespace App\Domain\Ai\Knowledge;

final class TextChunker
{
    public function __construct(
        private readonly int $chunkSize = 400,    // chars, not tokens
        private readonly int $overlapSize = 80,
    ) {}

    /**
     * Split text into overlapping chunks, respecting paragraph and sentence boundaries.
     *
     * @return string[]
     */
    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Split into paragraphs first
        $paragraphs = (array) preg_split('/\n{2,}/u', $text);
        $chunks     = [];
        $buffer     = '';

        foreach ($paragraphs as $para) {
            $para = trim((string) $para);
            if ($para === '') {
                continue;
            }

            if (mb_strlen($buffer) + mb_strlen($para) + 1 <= $this->chunkSize) {
                $buffer = $buffer !== '' ? $buffer . "\n\n" . $para : $para;
            } else {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                    // Overlap: take last N chars of buffer
                    $buffer = mb_strlen($buffer) > $this->overlapSize
                        ? mb_substr($buffer, -$this->overlapSize) . "\n\n" . $para
                        : $buffer . "\n\n" . $para;
                } else {
                    // Para itself longer than chunkSize — split by sentence
                    $sentences = $this->splitSentences($para);
                    foreach ($sentences as $sent) {
                        if (mb_strlen($buffer) + mb_strlen($sent) + 1 <= $this->chunkSize) {
                            $buffer = $buffer !== '' ? $buffer . ' ' . $sent : $sent;
                        } else {
                            if ($buffer !== '') {
                                $chunks[] = $buffer;
                            }
                            $buffer = $sent;
                        }
                    }
                }
            }
        }
        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return array_values(array_filter($chunks, fn ($c) => mb_strlen(trim($c)) > 20));
    }

    /**
     * @return string[]
     */
    private function splitSentences(string $text): array
    {
        // Arabic sentence enders: period, Arabic full stop, exclamation, question
        $parts = (array) preg_split('/(?<=[.!?؟۔])\s+/u', $text);
        return array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
    }
}
