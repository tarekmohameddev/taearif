<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\DTOs;

/**
 * A message in the agent conversation thread.
 *
 * Supports all roles required for the tool-calling protocol:
 *   system | user | assistant | tool
 *
 * For assistant messages that carry tool calls, $toolCalls is populated and
 * $content may be null.  For tool-role messages (observations), $toolCallId
 * is required.
 */
final class AgentMessage
{
    /**
     * @param  ToolCall[]|null $toolCalls  Populated on assistant messages from the model.
     */
    public function __construct(
        public readonly string  $role,
        public readonly ?string $content,
        public readonly ?string $toolCallId = null,  // required for role="tool"
        public readonly ?array  $toolCalls  = null,  // populated on assistant tool-call messages
        public readonly ?string $name       = null,
    ) {}

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /**
     * Assistant message that contains only tool calls (content is null in the wire format).
     *
     * @param ToolCall[] $toolCalls
     */
    public static function assistantToolCalls(array $toolCalls): self
    {
        return new self('assistant', null, null, $toolCalls);
    }

    /**
     * Tool-role observation returned to the model after executing a tool call.
     */
    public static function toolResult(string $toolCallId, string $content): self
    {
        return new self('tool', $content, $toolCallId);
    }

    /**
     * Serialize to the wire format expected by OpenAI-compatible APIs.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->role === 'tool') {
            return [
                'role'         => 'tool',
                'tool_call_id' => $this->toolCallId,
                'content'      => (string) $this->content,
            ];
        }

        if ($this->role === 'assistant' && $this->toolCalls !== null) {
            return [
                'role'       => 'assistant',
                'content'    => null,
                'tool_calls' => array_map(function (ToolCall $tc) {
                    return [
                        'id'       => $tc->id,
                        'type'     => 'function',
                        'function' => [
                            'name'      => $tc->name,
                            'arguments' => json_encode($tc->args),
                        ],
                    ];
                }, $this->toolCalls),
            ];
        }

        $arr = ['role' => $this->role, 'content' => $this->content ?? ''];
        if ($this->name !== null) {
            $arr['name'] = $this->name;
        }
        return $arr;
    }

    /**
     * Build from the existing LlmMessage format for backward-compat history loading.
     */
    public static function fromLegacyArray(string $role, string $content, ?string $name = null): self
    {
        return new self($role, $content, null, null, $name);
    }
}
