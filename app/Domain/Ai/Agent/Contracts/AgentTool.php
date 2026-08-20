<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Contracts;

/**
 * A tool the agent loop can call. Every implementation must be stateless;
 * the loop passes all context via the $args array.
 */
interface AgentTool
{
    /**
     * Machine-readable tool name, e.g. "search_inventory".
     * Must match the name returned by schema().
     */
    public function name(): string;

    /**
     * OpenAI function-call schema (the "function" object, excluding the outer
     * {"type":"function","function":...} wrapper — the transport adds that).
     *
     * @return array{name: string, description: string, parameters: array<string, mixed>}
     */
    public function schema(): array;

    /**
     * Execute the tool.
     *
     * @param  array<string, mixed> $args  Decoded JSON arguments from the model.
     * @param  int                  $tenantId
     * @return array<string, mixed>  Result to serialize and return to the model as a
     *                               tool-role message.  Must be JSON-serializable.
     */
    public function execute(array $args, int $tenantId): array;
}
