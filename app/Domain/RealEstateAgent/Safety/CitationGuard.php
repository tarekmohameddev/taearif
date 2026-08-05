<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

/**
 * Validates the model's structured reply against the FactLedger.
 *
 * Violations result in one regeneration attempt; if the retry still violates,
 * ReplyRedactor surgically removes the offending clause and delivers what remains.
 * Escalation (handoff) is the last resort, not the first.
 *
 * Rules enforced:
 *  1. Every {{p:ID|field}} must reference a property ID present in the ledger.
 *     IDs must be numeric digits — {{p:ID|price}} with literal "ID" is a violation.
 *  2. Every property ID in `cited_properties` must be in the ledger.
 *  3. Every {{k:chunk_id}} must reference a chunk present in the ledger.
 *  4. `say` must not contain bare price-like numbers outside placeholders —
 *     UNLESS the number is in the allowedNumbers set from NumberProvenance
 *     (i.e. the customer typed it, or it comes from the ledger, or it has a
 *      reference-number prefix like رقم الإعلان).
 *     Exception: years (14xx–15xx, 20xx), phone patterns (05x, 966x).
 *  5. `say` must not assert availability when the ledger is empty after a search.
 */
final class CitationGuard
{
    private const AVAILABILITY_PHRASES = [
        'عندنا', 'عندي', 'لدينا', 'متوفر', 'متوفرة', 'خيارات ممتازة',
        'لدي خيارات', 'عندنا خيارات', 'يوجد لدينا', 'يوجد عندنا',
    ];

    // Reference-number prefixes exempt numbers that immediately follow them
    private const REF_NUMBER_PREFIXES = [
        'رقم الإعلان', 'رخصة', 'ترخيص', 'رقم الترخيص',
        'القطعة', 'المخطط', 'الرقم الموحد', 'صك', 'رقم الصك',
        'رقم العقد', 'رقم العقار', 'رقم البلاغ',
    ];

    private readonly NumberProvenance $provenance;

    public function __construct(?NumberProvenance $provenance = null)
    {
        $this->provenance = $provenance ?? new NumberProvenance();
    }

    /**
     * @param  array<string, mixed>  $reply          Decoded structured reply.
     * @param  FactLedger            $ledger
     * @param  array<string>         $allowedNumbers  From NumberProvenance; numbers exempt from the bare-number rule.
     * @return string[]  Violation messages; empty = valid.
     */
    public function check(array $reply, FactLedger $ledger, array $allowedNumbers = []): array
    {
        $violations = [];
        $say        = (string) ($reply['say'] ?? '');

        // Normalise Arabic-Indic digits before scanning
        $say = $this->provenance->normaliseArabicIndic($say);

        // 1. Property placeholder references — ID must be numeric digits only
        preg_match_all('/\{\{p:([^|}\s]+)\|[^}]+\}\}/', $say, $matches);
        foreach ($matches[1] as $idStr) {
            if (!ctype_digit($idStr)) {
                // Literal "ID" or non-numeric placeholder from uncorrected model output
                $violations[] = "Non-numeric placeholder ID '{{p:{$idStr}|...}}' — model must use a real property ID";
                continue;
            }
            $id = (int) $idStr;
            if (!$ledger->hasProperty($id)) {
                $violations[] = "Property ID {$id} cited in placeholder but not in FactLedger";
            }
        }

        // 2. cited_properties array
        foreach ((array) ($reply['cited_properties'] ?? []) as $id) {
            if (!$ledger->hasProperty((int) $id)) {
                $violations[] = "Property ID {$id} listed in cited_properties but not in FactLedger";
            }
        }

        // 3. Knowledge chunk placeholders
        preg_match_all('/\{\{k:([^}]+)\}\}/', $say, $km);
        foreach ($km[1] as $chunkId) {
            if (!$ledger->hasKnowledgeChunk($chunkId)) {
                $violations[] = "Knowledge chunk '{$chunkId}' cited but not in FactLedger";
            }
        }

        // 4. Bare numbers — strip placeholders and markdown before scanning
        $stripped = preg_replace('/\{\{[^}]+\}\}/', 'PLACEHOLDER', $say) ?? $say;
        $stripped = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $stripped) ?? $stripped;

        // Build a normalised set for quick lookup (strip .0 suffixes)
        $allowedSet = array_flip(
            array_map(fn ($n) => rtrim(rtrim($n, '0'), '.'), $allowedNumbers)
        );

        // (a) Consecutive bare digits ≥ 4
        preg_match_all('/\b(\d{4,})\b/', $stripped, $numMatches);
        foreach ($numMatches[1] as $numStr) {
            $num = (int) $numStr;

            // Years
            if (($num >= 1400 && $num <= 1500) || ($num >= 2000 && $num <= 2100)) {
                continue;
            }
            // Phone patterns
            if (str_starts_with($numStr, '05') || str_starts_with($numStr, '966')) {
                continue;
            }
            // Reference-number prefix immediately before this number in the original text
            if ($this->hasRefNumberPrefix($say, $numStr)) {
                continue;
            }
            // Allowed from NumberProvenance (ledger or customer supplied)
            if (isset($allowedSet[$numStr]) || isset($allowedSet[rtrim(rtrim($numStr, '0'), '.')])) {
                continue;
            }

            $violations[] = "bare_number:{$numStr}";
        }

        // (b) Comma-formatted large numbers (e.g. "7,000,000")
        preg_match_all('/\b(\d{1,3}(?:,\d{3})+)\b/', $stripped, $commaMatches);
        foreach ($commaMatches[1] as $commaNum) {
            $effective = (int) str_replace(',', '', $commaNum);
            if ($effective < 10_000) {
                continue;
            }
            // Allow if customer-supplied or ledger-sourced
            $bare = (string) $effective;
            if (isset($allowedSet[$commaNum]) || isset($allowedSet[$bare])) {
                continue;
            }

            $violations[] = "bare_number:{$commaNum}";
        }

        // 5. Availability claim with empty ledger after a search
        if ($ledger->searchWasRun() && $ledger->searchReturnedNoResults()) {
            $normalizedSay = mb_strtolower($say);
            foreach (self::AVAILABILITY_PHRASES as $phrase) {
                if (str_contains($normalizedSay, mb_strtolower($phrase))) {
                    $violations[] = "availability_claim:{$phrase}";
                    break;
                }
            }
        }

        return $violations;
    }

    private function hasRefNumberPrefix(string $text, string $numStr): bool
    {
        foreach (self::REF_NUMBER_PREFIXES as $prefix) {
            // Look for the prefix within ~30 characters before the number
            if (preg_match('/' . preg_quote($prefix, '/') . '\s*[:\s]*' . preg_quote($numStr, '/') . '/u', $text)) {
                return true;
            }
        }
        return false;
    }
}
