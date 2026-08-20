<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\DTOs;

/**
 * A single tool invocation requested by the model.
 */
final class ToolCall
{
    public function __construct(
        public readonly string $id,       // provider-assigned call ID, e.g. "call_abc123"
        public readonly string $name,     // tool name
        public readonly array  $args,     // decoded JSON arguments
    ) {}

    public static function fromProviderArray(array $raw): self
    {
        return new self(
            id:   (string) ($raw['id'] ?? ''),
            name: (string) ($raw['function']['name'] ?? ''),
            args: json_decode((string) ($raw['function']['arguments'] ?? '{}'), true) ?? [],
        );
    }
}
