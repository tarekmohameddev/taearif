<?php

declare(strict_types=1);

namespace App\Domain\Ai\DTOs;

final class LlmRequest
{
    /** @param LlmMessage[] $messages */
    public function __construct(
        public readonly array $messages,
        public readonly string $model,
        public readonly int $maxTokens = 500,
        public readonly float $temperature = 0.3,
        public readonly bool $jsonMode = false,
        public readonly ?array $tools = null,      // OpenAI tool definitions
        public readonly int $timeoutSeconds = 30,
    ) {}
}
