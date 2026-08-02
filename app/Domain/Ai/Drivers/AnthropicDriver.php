<?php

declare(strict_types=1);

namespace App\Domain\Ai\Drivers;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Exceptions\LlmProviderException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

final class AnthropicDriver implements LlmClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const MAX_RETRIES = 2;
    private const RETRY_CODES = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function complete(LlmRequest $request): LlmResponse
    {
        $startMs = (int) round(microtime(true) * 1000);
        [$systemPrompt, $userMessages] = $this->extractSystem($request->messages);
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                $http    = new GuzzleClient(['timeout' => $request->timeoutSeconds]);
                $payload = [
                    'model'      => $request->model,
                    'max_tokens' => $request->maxTokens,
                    'messages'   => $userMessages,
                ];
                if ($systemPrompt !== '') {
                    $payload['system'] = $systemPrompt;
                }

                $response = $http->post(self::API_URL, [
                    'headers' => [
                        'x-api-key'         => $this->apiKey,
                        'anthropic-version' => self::API_VERSION,
                        'Content-Type'      => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                $body      = json_decode((string) $response->getBody(), true) ?? [];
                $latency   = (int) round(microtime(true) * 1000) - $startMs;
                $content   = (string) ($body['content'][0]['text'] ?? '');
                $tokensIn  = (int) ($body['usage']['input_tokens'] ?? 0);
                $tokensOut = (int) ($body['usage']['output_tokens'] ?? 0);

                return new LlmResponse(
                    content: $content,
                    tokensIn: $tokensIn,
                    tokensOut: $tokensOut,
                    latencyMs: $latency,
                    model: $request->model,
                    provider: 'anthropic',
                    success: true,
                );
            } catch (RequestException $e) {
                $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                if ($attempt < self::MAX_RETRIES && in_array($code, self::RETRY_CODES, true)) {
                    sleep($attempt * 2);
                    continue;
                }
                throw new LlmProviderException($e->getMessage(), 'anthropic', 'http_' . $code, $e);
            } catch (\Throwable $e) {
                throw new LlmProviderException($e->getMessage(), 'anthropic', 'unexpected', $e);
            }
        }
    }

    /**
     * @param  LlmMessage[] $messages
     * @return array{0: string, 1: array}
     */
    private function extractSystem(array $messages): array
    {
        $system = '';
        $rest   = [];
        foreach ($messages as $msg) {
            if ($msg->role === 'system') {
                $system = $system !== '' ? $system . "\n\n" . $msg->content : $msg->content;
            } else {
                $rest[] = $msg->toArray();
            }
        }
        return [$system, $rest];
    }
}
