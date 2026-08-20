<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Runtime;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Domain\Ai\Agent\DTOs\ToolCall;
use Illuminate\Support\Facades\Log;

/**
 * Holds the set of tools available in an agent loop and dispatches tool calls.
 *
 * The registry is immutable after construction.  Pass different tool sets to the
 * loop constructor to scope what the model may call.
 */
final class ToolRegistry
{
    /** @var array<string, AgentTool> keyed by tool name */
    private array $tools;

    /**
     * @param AgentTool[] $tools
     */
    public function __construct(array $tools)
    {
        $this->tools = [];
        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /**
     * Execute one tool call and return the JSON-serializable result.
     *
     * Unknown tools return an error payload instead of throwing, so the model can
     * handle gracefully and avoid loop termination on a stray call.
     *
     * @return array<string, mixed>
     */
    public function dispatch(ToolCall $call, int $tenantId): array
    {
        $tool = $this->tools[$call->name] ?? null;

        if ($tool === null) {
            Log::warning('agent.tool_registry.unknown_tool', [
                'name'      => $call->name,
                'call_id'   => $call->id,
                'tenant_id' => $tenantId,
            ]);
            return ['error' => "Unknown tool: {$call->name}"];
        }

        try {
            return $tool->execute($call->args, $tenantId);
        } catch (\Throwable $e) {
            Log::error('agent.tool_registry.tool_exception', [
                'tool'      => $call->name,
                'call_id'   => $call->id,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
            return ['error' => "Tool {$call->name} failed: " . $e->getMessage()];
        }
    }

    /**
     * OpenAI function definitions for all registered tools.
     *
     * @return array<int, array{type: string, function: array<string, mixed>}>
     */
    public function definitions(): array
    {
        return array_values(array_map(
            fn (AgentTool $t) => ['type' => 'function', 'function' => $t->schema()],
            $this->tools
        ));
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }
}
