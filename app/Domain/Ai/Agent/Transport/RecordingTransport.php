<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Transport;

use App\Domain\Ai\Agent\Contracts\AgentTransport;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;
use Illuminate\Support\Facades\Log;

/**
 * Wraps a real transport and saves every request-response pair to a cassette file.
 *
 * Use during `ai:agent:record` to freeze LLM responses for deterministic replay.
 *
 * Cassette format: newline-delimited JSON, one record per line:
 *   {"key": "<hash>", "request": {...}, "result": {...}}
 */
final class RecordingTransport implements AgentTransport
{
    private string $cassettePath;

    public function __construct(
        private readonly AgentTransport $inner,
        string                          $cassetteDir,
        string                          $cassetteId,
    ) {
        if (!is_dir($cassetteDir)) {
            mkdir($cassetteDir, 0755, true);
        }
        $this->cassettePath = rtrim($cassetteDir, '/') . '/' . $cassetteId . '.ndjson';
    }

    public function step(AgentStepRequest $request): AgentStepResult
    {
        $result = $this->inner->step($request);
        $this->write($request, $result);
        return $result;
    }

    public static function hashRequest(AgentStepRequest $request): string
    {
        return hash('sha256', json_encode([
            'model'       => $request->model,
            'messages'    => array_map(fn ($m) => $m->toArray(), $request->messages),
            'tools'       => $request->tools,
            'final_schema'=> $request->finalSchema,
        ]) ?: '');
    }

    private function write(AgentStepRequest $request, AgentStepResult $result): void
    {
        $key    = self::hashRequest($request);
        $record = json_encode([
            'key'    => $key,
            'result' => $this->serializeResult($result),
        ], JSON_UNESCAPED_UNICODE);

        if ($record === false) {
            Log::warning('agent.recording_transport.serialize_failed', ['key' => $key]);
            return;
        }

        file_put_contents($this->cassettePath, $record . "\n", FILE_APPEND | LOCK_EX);
    }

    private function serializeResult(AgentStepResult $result): array
    {
        return [
            'tool_calls' => $result->toolCalls !== null
                ? array_map(fn ($tc) => ['id' => $tc->id, 'name' => $tc->name, 'args' => $tc->args], $result->toolCalls)
                : null,
            'final_reply' => $result->finalReply,
            'tokens_in'   => $result->tokensIn,
            'tokens_out'  => $result->tokensOut,
            'latency_ms'  => $result->latencyMs,
            'model'       => $result->model,
            'provider'    => $result->provider,
        ];
    }
}
