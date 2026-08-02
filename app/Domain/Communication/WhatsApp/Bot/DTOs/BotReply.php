<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\DTOs;

/**
 * Structured reply produced by the generation pass.
 * The model returns this shape as JSON; deterministic verification checks it before delivery.
 */
final class BotReply
{
    public function __construct(
        public readonly string $reply,
        /** @var string[] Source IDs / chunk references used */
        public readonly array $usedSources,
        public readonly int $confidence,        // 0–100
        public readonly bool $needsHuman,
        public readonly ?string $handoffReason,
        /** @var array<string, mixed> Fact updates extracted from this turn */
        public readonly array $factsUpdate,
        public readonly ?string $nextQuestion,  // proactive clarifying question if needed
        public readonly bool $isGrounded = true,
        public readonly ?string $unansweredReason = null,
    ) {}

    public static function handoff(string $reason): self
    {
        return new self(
            reply: '',
            usedSources: [],
            confidence: 0,
            needsHuman: true,
            handoffReason: $reason,
            factsUpdate: [],
            nextQuestion: null,
            isGrounded: true,
        );
    }

    public static function fromJson(string $json): ?self
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return null;
        }

        return new self(
            reply: (string) ($data['reply'] ?? ''),
            usedSources: (array) ($data['used_sources'] ?? []),
            confidence: (int) ($data['confidence'] ?? 50),
            needsHuman: (bool) ($data['needs_human'] ?? false),
            handoffReason: isset($data['handoff_reason']) ? (string) $data['handoff_reason'] : null,
            factsUpdate: (array) ($data['facts_update'] ?? []),
            nextQuestion: isset($data['next_question']) ? (string) $data['next_question'] : null,
        );
    }
}
