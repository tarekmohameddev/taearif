<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Communication\WhatsApp\Bot\DTOs\BotReply;

/**
 * Deterministic grounding verification — Pass 3 of the bot pipeline.
 *
 * Extracts every number, price, area, date and property reference from the draft reply
 * and asserts each one appears in the retrieved context or tool output.
 * Catches invented prices and measurements without requiring another LLM call.
 */
final class GroundingVerifier
{
    // Arabic number words to ignore (these are filler, not data claims)
    private const IGNORE_NUMBERS = ['١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٠'];

    // Patterns for claims in the reply text
    private const PRICE_PATTERN   = '/[\d,،٠-٩]+\s*(?:ريال|SAR|SR|ألف|مليون|k|K)/u';
    private const AREA_PATTERN    = '/[\d,٠-٩]+\s*(?:م²|متر|sqm|m2)/u';
    private const DATE_PATTERN    = '/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/';
    private const DECIMAL_PATTERN = '/\b\d[\d,،.]+\d\b/'; // numbers like 850,000

    public function verify(BotReply $draft, string $contextText): VerificationResult
    {
        if ($draft->needsHuman || $draft->reply === '') {
            return VerificationResult::passed();
        }

        $claims = $this->extractClaims($draft->reply);
        if (empty($claims)) {
            return VerificationResult::passed();
        }

        $failures = [];
        $normalizedContext = $this->normalizeForSearch($contextText);

        foreach ($claims as $claim) {
            $normalizedClaim = $this->normalizeForSearch($claim);
            // Strip separators for comparison (850,000 vs 850000)
            $strippedClaim = preg_replace('/[,،\s]/u', '', $normalizedClaim) ?? $normalizedClaim;

            if (
                ! str_contains($normalizedContext, $normalizedClaim) &&
                ! str_contains(preg_replace('/[,،\s]/u', '', $normalizedContext) ?? $normalizedContext, $strippedClaim)
            ) {
                $failures[] = $claim;
            }
        }

        if (empty($failures)) {
            return VerificationResult::passed();
        }

        return VerificationResult::failed(
            'Claims not found in sources: ' . implode(', ', $failures),
            $failures
        );
    }

    public function applyStyleLint(string $replyText): StyleLintResult
    {
        $issues = [];

        if (mb_strlen($replyText) > 600) {
            $issues[] = 'reply_too_long';
        }

        // Detect markdown headings
        if (preg_match('/^#{1,3}\s/m', $replyText)) {
            $issues[] = 'markdown_headings_found';
        }

        // Detect bullet lists (more than 3 dashes)
        if (preg_match_all('/^- /m', $replyText) > 3) {
            $issues[] = 'excessive_bullets';
        }

        // Detect forbidden phrases
        $forbidden = ['I am an AI', 'As an AI', 'كذكاء اصطناعي'];
        foreach ($forbidden as $phrase) {
            if (str_contains($replyText, $phrase)) {
                $issues[] = 'forbidden_phrase:' . $phrase;
            }
        }

        return new StyleLintResult(empty($issues), $issues);
    }

    /** @return string[] */
    private function extractClaims(string $text): array
    {
        $claims = [];
        $patterns = [
            self::PRICE_PATTERN,
            self::AREA_PATTERN,
            self::DATE_PATTERN,
            self::DECIMAL_PATTERN,
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[0] as $m) {
                    $stripped = trim((string) $m);
                    // Filter out very small numbers that are just counts (e.g. "3 غرف")
                    if (preg_match('/^\d{1,2}$/', preg_replace('/[^\d]/u', '', $stripped) ?: '')) {
                        continue;
                    }
                    $claims[] = $stripped;
                }
            }
        }
        return array_unique($claims);
    }

    private function normalizeForSearch(string $text): string
    {
        // Convert Arabic-Indic digits to Western
        $text = strtr($text, [
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        return mb_strtolower($text);
    }
}

final class VerificationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly ?string $reason = null,
        /** @var string[] */
        public readonly array $failedClaims = [],
    ) {}

    public static function passed(): self { return new self(true); }

    public static function failed(string $reason, array $failedClaims = []): self
    {
        return new self(false, $reason, $failedClaims);
    }
}

final class StyleLintResult
{
    public function __construct(
        public readonly bool $passed,
        /** @var string[] */
        public readonly array $issues = [],
    ) {}
}
