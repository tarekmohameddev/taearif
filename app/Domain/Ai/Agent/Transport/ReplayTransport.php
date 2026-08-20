<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Transport;

use App\Domain\Ai\Agent\Contracts\AgentTransport;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;
use App\Domain\Ai\Agent\DTOs\ToolCall;
use App\Domain\Ai\Exceptions\LlmProviderException;

/**
 * Replays cassette files recorded by RecordingTransport.
 *
 * Indexed on construction so each lookup is O(1).
 * Throws when a request hash has no matching cassette entry, which means the
 * CI run has uncovered a turn not covered by the recorded corpus.
 */
final class ReplayTransport implements AgentTransport
{
    /** @var array<string, array> keyed by request hash */
    private array $index = [];

    /**
     * @param string[] $cassettePaths  Paths to .ndjson cassette files.
     */
    public function __construct(array $cassettePaths)
    {
        foreach ($cassettePaths as $path) {
            $this->loadCassette($path);
        }
    }

    public static function fromDirectory(string $dir): self
    {
        $files = glob(rtrim($dir, '/') . '/*.ndjson') ?: [];
        return new self($files);
    }

    public function step(AgentStepRequest $request): AgentStepResult
    {
        $key = RecordingTransport::hashRequest($request);

        if (!isset($this->index[$key])) {
            throw new LlmProviderException(
                "No cassette entry for request hash {$key}. Run ai:agent:record to update cassettes.",
                'replay',
                'cassette_miss',
            );
        }

        $raw = $this->index[$key];
        return $this->deserializeResult($raw);
    }

    private function loadCassette(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $record = json_decode($line, true);
            if (is_array($record) && isset($record['key'], $record['result'])) {
                $this->index[$record['key']] = $record['result'];
            }
        }
    }

    private function deserializeResult(array $raw): AgentStepResult
    {
        $toolCalls = null;
        if (is_array($raw['tool_calls'] ?? null)) {
            $toolCalls = array_map(
                fn (array $tc) => new ToolCall($tc['id'], $tc['name'], $tc['args']),
                $raw['tool_calls']
            );
        }

        return new AgentStepResult(
            toolCalls:  $toolCalls,
            finalReply: $raw['final_reply'] ?? null,
            tokensIn:   (int) ($raw['tokens_in'] ?? 0),
            tokensOut:  (int) ($raw['tokens_out'] ?? 0),
            latencyMs:  0, // replays are instant
            model:      (string) ($raw['model'] ?? 'replay'),
            provider:   'replay',
        );
    }
}
