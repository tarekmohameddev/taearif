<?php

declare(strict_types=1);

namespace App\Domain\Ai\DTOs;

final class LlmResponse
{
    public function __construct(
        public readonly string $content,
        public readonly int $tokensIn,
        public readonly int $tokensOut,
        public readonly int $latencyMs,
        public readonly string $model,
        public readonly string $provider,
        public readonly ?array $toolCalls = null,
        public readonly bool $success = true,
        public readonly ?string $errorCode = null,
    ) {}

    public static function failure(string $errorCode, string $model, string $provider, int $latencyMs): self
    {
        return new self('', 0, 0, $latencyMs, $model, $provider, null, false, $errorCode);
    }
}
