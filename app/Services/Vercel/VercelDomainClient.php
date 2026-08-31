<?php

namespace App\Services\Vercel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VercelDomainClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.vercel.token'))
            && filled(config('services.vercel.project_id'));
    }

    public function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new VercelDomainException(
                'Vercel domain integration is not configured (VERCEL_TOKEN / VERCEL_PROJECT_ID).'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function addDomain(string $name, ?string $redirect = null, ?int $redirectStatusCode = null): array
    {
        $this->assertConfigured();

        $payload = ['name' => $name];
        if ($redirect !== null) {
            $payload['redirect'] = $redirect;
            $payload['redirectStatusCode'] = $redirectStatusCode ?? 301;
        }

        $response = $this->http()->post(
            $this->projectUrl('/domains'),
            $payload
        );

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        // A project domain-limit rejection arrives as a 400, but it is never an
        // "already attached" case. Without this short-circuit the lookup below
        // replaces this response — and its error code — with whatever the
        // follow-up GET returns, so callers lose the reason for the failure.
        if ($response->json('error.code') === 'project_domain_limit_reached') {
            $this->throwFromResponse('Failed to add domain to Vercel', $response);
        }

        // Idempotent: domain already on project
        if (in_array($response->status(), [400, 409], true)) {
            $existing = $this->getDomain($name);
            if ($existing !== null) {
                return $existing;
            }
        }

        $this->throwFromResponse('Failed to add domain to Vercel', $response);
    }

    /**
     * Add apex and www (www redirects to apex).
     *
     * @return array{apex: array<string, mixed>, www: array<string, mixed>}
     */
    public function addApexWithWwwRedirect(string $apex): array
    {
        $apex = $this->normalizeApex($apex);
        $www = 'www.' . $apex;

        $apexResult = $this->addDomain($apex);
        $wwwResult = $this->addDomain($www, $apex, 301);

        return [
            'apex' => $apexResult,
            'www' => $wwwResult,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyDomain(string $name): array
    {
        $this->assertConfigured();
        $name = $this->normalizeApex($name);

        $response = $this->http()->post(
            $this->projectUrl('/domains/' . rawurlencode($name) . '/verify', 'v9')
        );

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        // Already verified or challenge not ready — fall back to get
        if (in_array($response->status(), [400, 403, 409], true)) {
            $existing = $this->getDomain($name);
            if ($existing !== null) {
                return $existing;
            }
        }

        $this->throwFromResponse('Failed to verify domain on Vercel', $response);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDomain(string $name): ?array
    {
        $this->assertConfigured();
        $name = $this->normalizeApex($name);

        $response = $this->http()->get(
            $this->projectUrl('/domains/' . rawurlencode($name), 'v9')
        );

        if ($response->status() === 404) {
            return null;
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwFromResponse('Failed to fetch domain from Vercel', $response);
    }

    public function removeDomain(string $name): void
    {
        $this->assertConfigured();
        $name = strtolower(trim($name));

        $response = $this->http()->delete(
            $this->projectUrl('/domains/' . rawurlencode($name), 'v9')
        );

        if ($response->successful() || $response->status() === 404) {
            return;
        }

        $this->throwFromResponse('Failed to remove domain from Vercel', $response);
    }

    public function removeApexAndWww(string $apex): void
    {
        $apex = $this->normalizeApex($apex);
        $this->removeDomain($apex);
        $this->removeDomain('www.' . $apex);
    }

    public function normalizeApex(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;

        return $domain;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.vercel.base_url'), '/'))
            ->withToken((string) config('services.vercel.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function projectUrl(string $path, string $version = 'v10'): string
    {
        $project = rawurlencode((string) config('services.vercel.project_id'));
        $url = '/' . $version . '/projects/' . $project . $path;

        $teamId = config('services.vercel.team_id');
        if (filled($teamId)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'teamId=' . rawurlencode((string) $teamId);
        }

        return $url;
    }

    /**
     * @return never
     */
    private function throwFromResponse(string $message, Response $response)
    {
        $body = $response->json() ?? $response->body();
        Log::warning($message, [
            'status' => $response->status(),
            'body' => $body,
        ]);

        $detail = is_array($body)
            ? ($body['error']['message'] ?? $body['message'] ?? json_encode($body))
            : (string) $body;

        throw new VercelDomainException(
            $message . ($detail ? ': ' . $detail : ''),
            $response->status(),
            $body
        );
    }
}
