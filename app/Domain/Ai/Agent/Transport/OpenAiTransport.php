<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Transport;

use App\Domain\Ai\Agent\Contracts\AgentTransport;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;
use App\Domain\Ai\Agent\DTOs\ToolCall;
use App\Domain\Ai\Agent\Schema\JsonSchema;
use App\Domain\Ai\Exceptions\LlmProviderException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI-compatible transport for the agent loop.
 *
 * Differences from the legacy OpenAiCompatibleDriver:
 *  - Uses AgentMessage (supports role=tool, assistant with tool_calls).
 *  - Passes tools + tool_choice when tools are present.
 *  - Uses response_format json_schema strict mode for the final reply.
 *  - Returns AgentStepResult with parsed tool calls OR decoded final JSON.
 */
final class OpenAiTransport implements AgentTransport
{
    private const MAX_RETRIES  = 2;
    private const RETRY_CODES  = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl       = 'https://api.openai.com/v1',
        private readonly string $providerLabel = 'openai',
    ) {}

    public function step(AgentStepRequest $request): AgentStepResult
    {
        $startMs = (int) round(microtime(true) * 1000);
        $payload = $this->buildPayload($request);
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                $http = new GuzzleClient(['timeout' => $request->timeoutSeconds]);
                $response = $http->post(
                    rtrim($this->baseUrl, '/') . '/chat/completions',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => $payload,
                    ]
                );

                $body    = json_decode((string) $response->getBody(), true) ?? [];
                $latency = (int) round(microtime(true) * 1000) - $startMs;

                $choice    = $body['choices'][0] ?? [];
                $message   = $choice['message'] ?? [];
                $content   = (string) ($message['content'] ?? '');
                $rawCalls  = $message['tool_calls'] ?? null;
                $tokensIn  = (int) ($body['usage']['prompt_tokens'] ?? 0);
                $tokensOut = (int) ($body['usage']['completion_tokens'] ?? 0);
                $model     = (string) ($body['model'] ?? $request->model);

                // Model wants to call tools
                if (is_array($rawCalls) && count($rawCalls) > 0) {
                    $toolCalls = array_map(
                        fn (array $raw) => ToolCall::fromProviderArray($raw),
                        $rawCalls
                    );
                    return new AgentStepResult(
                        toolCalls:  $toolCalls,
                        finalReply: null,
                        tokensIn:   $tokensIn,
                        tokensOut:  $tokensOut,
                        latencyMs:  $latency,
                        model:      $model,
                        provider:   $this->providerLabel,
                    );
                }

                // Model produced final reply
                $decoded = json_decode($content, true);
                if (!is_array($decoded)) {
                    Log::warning('agent.transport.invalid_json', [
                        'provider' => $this->providerLabel,
                        'raw'      => substr($content, 0, 300),
                    ]);
                    // Return empty result — loop will treat as failure
                    return new AgentStepResult(
                        toolCalls:  null,
                        finalReply: null,
                        tokensIn:   $tokensIn,
                        tokensOut:  $tokensOut,
                        latencyMs:  $latency,
                        model:      $model,
                        provider:   $this->providerLabel,
                    );
                }

                return new AgentStepResult(
                    toolCalls:  null,
                    finalReply: $decoded,
                    tokensIn:   $tokensIn,
                    tokensOut:  $tokensOut,
                    latencyMs:  $latency,
                    model:      $model,
                    provider:   $this->providerLabel,
                );

            } catch (RequestException $e) {
                $code    = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                $latency = (int) round(microtime(true) * 1000) - $startMs;

                if ($attempt < self::MAX_RETRIES && in_array($code, self::RETRY_CODES, true)) {
                    $delay = $attempt * 2;
                    Log::warning('agent.transport.retry', [
                        'provider' => $this->providerLabel,
                        'attempt'  => $attempt,
                        'status'   => $code,
                        'delay_s'  => $delay,
                    ]);
                    sleep($delay);
                    continue;
                }

                Log::error('agent.transport.failed', [
                    'provider' => $this->providerLabel,
                    'status'   => $code,
                    'model'    => $request->model,
                    'error'    => $e->getMessage(),
                ]);
                throw new LlmProviderException($e->getMessage(), $this->providerLabel, 'http_' . $code, $e);
            } catch (\Throwable $e) {
                throw new LlmProviderException($e->getMessage(), $this->providerLabel, 'unexpected', $e);
            }
        }
    }

    /** @return array<string, mixed> */
    private function buildPayload(AgentStepRequest $request): array
    {
        $isReasoning = (bool) preg_match('/^(gpt-5(?!-chat)|o1|o3|o4)/i', $request->model);

        $payload = [
            'model'    => $request->model,
            'messages' => array_map(fn ($m) => $m->toArray(), $request->messages),
        ];

        if ($isReasoning) {
            $payload['max_completion_tokens'] = $request->maxTokens;
        } else {
            $payload['max_tokens']  = $request->maxTokens;
            $payload['temperature'] = $request->temperature;
        }

        // Use strict JSON schema for the final reply output
        if ($request->finalSchema !== null && count($request->tools) === 0) {
            // No tools = we want the final reply in this step
            $payload['response_format'] = JsonSchema::responseFormat('agent_reply', $request->finalSchema);
        } elseif ($request->finalSchema !== null && count($request->tools) > 0) {
            // Both tools and schema: let the model decide whether to call a tool or produce final reply.
            // Use json_schema if supported; fall back to json_object on older models.
            // We set response_format so that when the model is done with tool calls it
            // formats the final reply correctly.
            $payload['response_format'] = JsonSchema::responseFormat('agent_reply', $request->finalSchema);
        }

        if (count($request->tools) > 0) {
            $payload['tools'] = $request->tools;
            // Honour explicit toolChoice override (e.g. 'none' for forced-finalize step)
            if ($request->toolChoice !== null) {
                $payload['tool_choice'] = $request->toolChoice;
            } else {
                $payload['tool_choice'] = 'auto';
            }
        } elseif ($request->toolChoice === 'none') {
            // No tools supplied AND caller wants tool_choice=none (finalize step)
            // — just omit tools; the model will produce a plain reply
        }

        return $payload;
    }
}
