<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

use App\Domain\Ai\Agent\DTOs\AgentMessage;

/**
 * Collects numbers that are allowed to appear in the bot reply without a placeholder.
 *
 * A number is "allowed" when it comes from one of these trusted sources:
 *  1. The customer's own recent messages (repeating a number the customer supplied).
 *  2. A reference-number prefix immediately precedes it in the reply
 *     (رقم الإعلان، رخصة، ترخيص، القطعة، المخطط، الرقم الموحد، صك).
 *  3. Already exempted by CitationGuard: years (14xx–20xx) and phone patterns (05x, 966x).
 *
 * Sources 2 & 3 are handled in CitationGuard itself. This class handles source 1.
 *
 * NOTE: FactLedger values (prices, areas) are intentionally NOT in the allowed set.
 * The model must cite those via {{p:ID|field}} placeholders. Including ledger values
 * here lets the model bypass the placeholder system entirely, delivering bare numbers
 * that were never validated as placeholders.
 */
final class NumberProvenance
{
    /**
     * Build an allowed-number set from the customer's own messages only.
     *
     * @param  AgentMessage[]  $history
     * @return array<string>   Normalised digit strings that may appear in the reply.
     */
    public function buildAllowedSet(FactLedger $ledger, array $history): array
    {
        $allowed = [];

        // From customer messages in the history window
        foreach ($history as $msg) {
            if ($msg->role !== 'user') {
                continue;
            }
            $content = (string) ($msg->content ?? '');
            // Collect 4+ digit Arabic-Indic and Western digit sequences
            $normalised = $this->normaliseArabicIndic($content);
            if (preg_match_all('/\b(\d{4,})\b/', $normalised, $m)) {
                foreach ($m[1] as $num) {
                    $allowed[] = $num;
                }
            }
            // Comma-formatted large numbers from customer text (e.g., 400,000)
            if (preg_match_all('/\b(\d{1,3}(?:,\d{3})+)\b/', $normalised, $m)) {
                foreach ($m[1] as $num) {
                    $allowed[] = str_replace(',', '', $num);
                    $allowed[] = $num; // also accept with commas
                }
            }
        }

        return array_values(array_unique($allowed));
    }

    /**
     * Normalise Arabic-Indic (٠١٢٣٤٥٦٧٨٩) and Persian (۰-۹) digits to ASCII.
     */
    public function normaliseArabicIndic(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private function normaliseNumber(string $n): string
    {
        // Remove trailing .0 so "32000.0" → "32000"
        if (str_contains($n, '.')) {
            $n = rtrim(rtrim($n, '0'), '.');
        }
        return $n;
    }
}
