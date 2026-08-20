<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Contracts;

use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;

/**
 * Provider-agnostic interface for one step of the agent loop.
 *
 * A step sends the current message thread (including tool observations) to the
 * model and returns either a list of tool calls OR a validated final reply.
 */
interface AgentTransport
{
    public function step(AgentStepRequest $request): AgentStepResult;
}
