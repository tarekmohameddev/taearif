<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Services;

use App\Domain\CRM\Pipedrive\Contracts\PipedriveClientInterface;
use App\Domain\CRM\Pipedrive\DTOs\PipedriveCredentialsDto;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PipedriveClient implements PipedriveClientInterface
{
    private const TIMEOUT_SECONDS = 20;
    private const RETRY_TIMES = 1;
    private const RETRY_SLEEP_MS = 500;

    public function __construct(
        private readonly PipedriveCredentialsDto $credentials,
    ) {}

    public function createPerson(array $data): array
    {
        $response = $this->post('/api/v2/persons', $data);

        return $this->parseResponse($response, 'createPerson');
    }

    public function createOrganization(array $data): array
    {
        $response = $this->post('/api/v2/organizations', $data);

        return $this->parseResponse($response, 'createOrganization');
    }

    public function createDeal(array $data): array
    {
        $response = $this->post('/api/v2/deals', $data);

        return $this->parseResponse($response, 'createDeal');
    }

    public function testConnection(): bool
    {
        try {
            // Users API is still v1-only; v2 has persons/orgs/deals but not /users/me.
            $response = $this->httpClient()->get($this->url('/api/v1/users/me'));

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Pipedrive connection test failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function post(string $path, array $data): Response
    {
        // Retry once on network-level exceptions (timeout, connection refused).
        // HTTP 4xx/5xx are returned as responses (not thrown) and handled in parseResponse().
        return $this->httpClient()
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS)
            ->post($this->url($path), $data);
    }

    private function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(self::TIMEOUT_SECONDS)
            ->withHeaders([
                'x-api-token' => $this->credentials->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
    }

    private function url(string $path): string
    {
        return rtrim($this->credentials->baseUrl, '/') . $path;
    }

    private function parseResponse(Response $response, string $operation): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $body = $response->json() ?? [];
        $errorDetail = $body['error'] ?? $body['message'] ?? 'Unknown Pipedrive API error';

        Log::error('Pipedrive API error', [
            'operation' => $operation,
            'status' => $response->status(),
            'error' => $errorDetail,
        ]);

        throw new PipedriveApiException(
            "Pipedrive {$operation} failed ({$response->status()}): {$errorDetail}",
            $response->status(),
            $body,
        );
    }
}
