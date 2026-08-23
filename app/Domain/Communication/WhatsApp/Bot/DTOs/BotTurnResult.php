<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\DTOs;

use App\Domain\Communication\WhatsApp\Bot\StyleLintResult;
use App\Domain\Communication\WhatsApp\Bot\VerificationResult;

/**
 * Full result of a single bot turn, produced by BotOrchestrator::handleSandbox().
 *
 * Outcome values:
 *   delivered    – bot generated a reply (autonomous or sandbox dry-run)
 *   shadow_draft – reply stored as shadow draft (not yet sent)
 *   handoff      – bot paused and handoff message sent
 *   opt_out      – customer opted out
 *   skipped      – guard triggered (loop, paused, opted-out, no config, etc.)
 */
final class BotTurnResult
{
    /**
     * @param  string[]  $botSegments   WhatsApp-formatted text segments (as they would be sent)
     * @param  string[]  $trace         Pipeline decision log, one entry per step
     * @param  array<string, mixed>  $factsUpdated
     */
    public function __construct(
        public readonly string $outcome,
        public readonly ?string $replyText,
        public readonly ?BotReply $botReply,
        public readonly ?VerificationResult $groundingResult,
        public readonly ?StyleLintResult $styleResult,
        public readonly string $intent,
        public readonly string $difficulty,
        public readonly int $kbChunksUsed,
        public readonly int $propertiesFound,
        public readonly int $tokensIn,
        public readonly int $tokensOut,
        public readonly array $botSegments,
        public readonly array $trace,
        public readonly ?string $skipReason,
        public readonly array $factsUpdated,
    ) {}

    public static function skipped(string $reason, array $trace = []): self
    {
        return new self(
            outcome: 'skipped',
            replyText: null,
            botReply: null,
            groundingResult: null,
            styleResult: null,
            intent: 'unknown',
            difficulty: 'unknown',
            kbChunksUsed: 0,
            propertiesFound: 0,
            tokensIn: 0,
            tokensOut: 0,
            botSegments: [],
            trace: $trace,
            skipReason: $reason,
            factsUpdated: [],
        );
    }
}
