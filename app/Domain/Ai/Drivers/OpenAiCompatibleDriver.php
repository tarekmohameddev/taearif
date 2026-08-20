<?php

declare(strict_types=1);

namespace App\Domain\Ai\Drivers;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Exceptions\LlmProviderException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

final class OpenAiCompatibleDriver implements LlmClient
{
    private const DEFAULT_BASE_URL = 'https://api.openai.com/v1';
    private const MAX_RETRIES = 2;
    private const RETRY_CODES = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly string $providerLabel = 'openai',
    ) {}

    public function complete(LlmRequest $request): LlmResponse
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

                $content   = (string) ($body['choices'][0]['message']['content'] ?? '');
                $toolCalls = $body['choices'][0]['message']['tool_calls'] ?? null;
                $tokensIn  = (int) ($body['usage']['prompt_tokens'] ?? 0);
                $tokensOut = (int) ($body['usage']['completion_tokens'] ?? 0);

                return new LlmResponse(
                    content: $content,
                    tokensIn: $tokensIn,
                    tokensOut: $tokensOut,
                    latencyMs: $latency,
                    model: $request->model,
                    provider: $this->providerLabel,
                    toolCalls: $toolCalls,
                    success: true,
                );
            } catch (RequestException $e) {
                $code    = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                $latency = (int) round(microtime(true) * 1000) - $startMs;

                if ($attempt < self::MAX_RETRIES && in_array($code, self::RETRY_CODES, true)) {
                    $delay = $attempt * 2; // 2s, 4s
                    Log::warning('ai.llm.retry', [
                        'provider' => $this->providerLabel,
                        'attempt'  => $attempt,
                        'status'   => $code,
                        'delay_s'  => $delay,
                    ]);
                    sleep($delay);
                    continue;
                }

                Log::error('ai.llm.failed', [
                    'provider' => $this->providerLabel,
                    'status'   => $code,
                    'model'    => $request->model,
                    'error'    => $e->getMessage(),
                ]);
                throw new LlmProviderException(
                    $e->getMessage(),
                    $this->providerLabel,
                    'http_' . $code,
                    $e
                );
            } catch (\Throwable $e) {
                throw new LlmProviderException($e->getMessage(), $this->providerLabel, 'unexpected', $e);
            }
        }
    }

    /**
     * Reasoning-family models (gpt-5*, o1*, o3*, o4*) reject the legacy
     * `max_tokens`/`temperature` parameters used by GPT-4o and earlier models.
     */
    private function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^(gpt-5(?!-chat)|o1|o3|o4)/i', $model);
    }

    private function buildPayload(LlmRequest $request): array
    {
        $payload = [
            'model'    => $request->model,
            'messages' => array_map(fn ($m) => $m->toArray(), $request->messages),
        ];

        if ($this->isReasoningModel($request->model)) {
            $payload['max_completion_tokens'] = $request->maxTokens;
        } else {
            $payload['max_tokens']  = $request->maxTokens;
            $payload['temperature'] = $request->temperature;
        }

        if ($request->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        if ($request->tools !== null) {
            $payload['tools']       = $request->tools;
            $payload['tool_choice'] = 'auto';
        }
        return $payload;
    }
}
