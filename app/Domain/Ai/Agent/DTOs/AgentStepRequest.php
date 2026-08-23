<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\DTOs;

/**
 * Input to one step of the agent loop (one provider round-trip).
 */
final class AgentStepRequest
{
    /**
     * @param  AgentMessage[]              $messages
     * @param  array<string, mixed>[]      $tools          OpenAI function definitions ({"type":"function","function":{...}})
     * @param  array<string, mixed>|null   $finalSchema    JSON schema enforced when the model returns its final structured reply.
     *                                                      Pass null to use json_object mode instead.
     * @param  string|null                 $toolChoice     Override tool_choice: 'auto'|'none'|'required' or null (default 'auto').
     *                                                      Pass 'none' on the final forced step to make the model output a reply.
     *                                                      Pass the name of a tool to force a specific tool call.
     */
    public function __construct(
        public readonly array   $messages,
        public readonly string  $model,
        public readonly array   $tools          = [],
        public readonly ?array  $finalSchema    = null,
        public readonly int     $maxTokens      = 800,
        public readonly float   $temperature    = 0.3,
        public readonly int     $timeoutSeconds = 30,
        public readonly ?string $toolChoice     = null,
    ) {}
}
