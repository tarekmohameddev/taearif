<?php

namespace App\Services\Vercel;

use Illuminate\Http\Client\ConnectionException;
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
                __('domain_mutation.not_configured'),
                internalCode: VercelDomainException::CODE_NOT_CONFIGURED
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function addDomain(string $name, ?string $redirect = null, ?int $redirectStatusCode = null): array
    {
        $this->assertConfigured();
        $name = strtolower(trim($name));

        $payload = ['name' => $name];
        if ($redirect !== null) {
            $payload['redirect'] = $this->normalizeApex($redirect);
            $payload['redirectStatusCode'] = $redirectStatusCode ?? 301;
        }

        $response = $this->sendRequest(fn () => $this->http()->post(
            $this->projectUrl('/domains'),
            $payload
        ));

        if ($response->successful()) {
            $body = $response->json() ?? [];

            return $this->attachMutationMeta($body, wasCreated: true, wasAdopted: false);
        }

        if ($response->json('error.code') === 'project_domain_limit_reached') {
            $this->throwFromResponse('Failed to add domain to Vercel', $response);
        }

        if (in_array($response->status(), [400, 409], true)) {
            $existing = $this->getDomain($name);
            if ($existing !== null) {
                if ($redirect !== null) {
                    $this->assertAdoptedRedirectMatches(
                        $existing,
                        $payload['redirect'],
                        (int) $payload['redirectStatusCode']
                    );
                }

                return $this->attachMutationMeta($existing, wasCreated: false, wasAdopted: true);
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

        $response = $this->sendRequest(fn () => $this->http()->post(
            $this->projectUrl('/domains/' . rawurlencode($name) . '/verify', 'v9')
        ));

        if ($response->successful()) {
            return $response->json() ?? [];
        }

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
        $name = strtolower(trim($name));

        $response = $this->sendRequest(fn () => $this->http()->get(
            $this->projectUrl('/domains/' . rawurlencode($name), 'v9')
        ));

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

        $response = $this->sendRequest(fn () => $this->http()->delete(
            $this->projectUrl('/domains/' . rawurlencode($name), 'v9')
        ));

        if ($response->successful() || $response->status() === 404) {
            return;
        }

        $this->throwFromResponse('Failed to remove domain from Vercel', $response);
    }

    public function clearDomainRedirect(string $name): void
    {
        $this->assertConfigured();
        $name = strtolower(trim($name));

        $response = $this->sendRequest(fn () => $this->http()->patch(
            $this->projectUrl('/domains/' . rawurlencode($name), 'v9'),
            [
                'redirect' => null,
                'redirectStatusCode' => null,
            ]
        ));

        if ($response->successful()) {
            return;
        }

        $this->throwFromResponse('Failed to clear domain redirect on Vercel', $response);
    }

    public function removeWwwHostname(string $apex): void
    {
        $apex = $this->normalizeApex($apex);
        $www = 'www.' . $apex;

        $apexRecord = $this->getDomain($apex);
        if ($apexRecord !== null) {
            $redirectTarget = isset($apexRecord['redirect'])
                ? strtolower((string) $apexRecord['redirect'])
                : null;

            if ($redirectTarget === $www) {
                $this->clearDomainRedirect($apex);
            }
        }

        $this->removeDomain($www);
    }

    public function removeDomainWithRedirects(string $apex): void
    {
        $this->assertConfigured();
        $apex = $this->normalizeApex($apex);
        $www = 'www.' . $apex;

        $response = $this->sendRequest(fn () => $this->http()->delete(
            $this->projectUrl('/domains/' . rawurlencode($apex) . '?removeRedirects=true', 'v9')
        ));

        if (! $response->successful() && $response->status() !== 404) {
            $this->throwFromResponse('Failed to remove domain from Vercel', $response);
        }

        $wwwRecord = $this->getDomain($www);
        if ($wwwRecord !== null) {
            $this->removeDomain($www);
        }
    }

    public function removeApexAndWww(string $apex): void
    {
        $apex = $this->normalizeApex($apex);
        $this->removeDomain($apex);
        $this->removeDomain('www.' . $apex);
    }

    /**
     * @return array{names: list<string>, count: int, is_lower_bound: bool, domains: list<array<string, mixed>>}
     */
    public function listProjectDomains(): array
    {
        $this->assertConfigured();

        $result = $this->fetchProjectDomainPages();

        return [
            'names' => $result['names'],
            'count' => count($result['names']),
            'is_lower_bound' => $result['is_lower_bound'],
            'domains' => $result['domains'],
        ];
    }

    /**
     * @return array{count: int, is_lower_bound: bool}
     */
    public function countProjectDomains(): array
    {
        $this->assertConfigured();

        $result = $this->fetchProjectDomainPages();

        return [
            'count' => count($result['names']),
            'is_lower_bound' => $result['is_lower_bound'],
        ];
    }

    /**
     * @return list<string> lowercased domain names attached to the project
     */
    public function listProjectDomainNames(): array
    {
        $this->assertConfigured();

        return $this->fetchProjectDomainPages()['names'];
    }

    /**
     * @return array{project_id: string, team_id: ?string, project_name: ?string}
     */
    public function getProjectIdentity(): array
    {
        $this->assertConfigured();

        $response = $this->sendRequest(fn () => $this->http()->get(
            $this->projectUrl('', 'v9')
        ));

        if (! $response->successful()) {
            $this->throwFromResponse('Failed to fetch Vercel project identity', $response);
        }

        $body = $response->json() ?? [];

        return [
            'project_id' => (string) ($body['id'] ?? config('services.vercel.project_id')),
            'team_id' => $this->normalizeNullableString($body['accountId'] ?? $body['teamId'] ?? config('services.vercel.team_id')),
            'project_name' => $this->normalizeNullableString($body['name'] ?? null),
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     raw: array<string, mixed>
     * }|null
     */
    public function getAccountDomain(string $apex): ?array
    {
        $this->assertConfigured();
        $apex = $this->normalizeApex($apex);

        $response = $this->sendRequest(fn () => $this->http()->get(
            $this->accountDomainUrl($apex, 'v5')
        ));

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            $this->throwFromResponse('Failed to fetch account domain from Vercel', $response);
        }

        return $this->normalizeAccountDomain($response->json() ?? []);
    }

    /**
     * Create an account-level apex domain with Vercel DNS zone enabled.
     *
     * @return array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     was_created: bool,
     *     was_adopted: bool,
     *     raw: array<string, mixed>
     * }
     */
    public function createAccountDomain(string $apex): array
    {
        $this->assertConfigured();
        $apex = $this->normalizeApex($apex);

        $response = $this->sendRequest(fn () => $this->http()->post(
            $this->accountDomainsCollectionUrl('/v7/domains'),
            [
                'method' => 'add',
                'name' => $apex,
                'zone' => true,
            ]
        ));

        if ($response->successful()) {
            $normalized = $this->normalizeAccountDomain($response->json() ?? []);

            return $this->attachAccountDomainMutationMeta($normalized, wasCreated: true, wasAdopted: false);
        }

        if (in_array($response->status(), [400, 409], true)) {
            $existing = $this->getAccountDomain($apex);
            if ($existing !== null) {
                if (! $existing['zone']) {
                    return $this->enableAccountDomainZone($apex, adoptExisting: true);
                }

                return $this->attachAccountDomainMutationMeta($existing, wasCreated: false, wasAdopted: true);
            }
        }

        $this->throwFromResponse('Failed to create account domain on Vercel', $response);
    }

    /**
     * Enable the Vercel DNS zone for an existing account apex domain.
     *
     * @return array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     was_created: bool,
     *     was_adopted: bool,
     *     raw: array<string, mixed>
     * }
     */
    public function enableAccountDomainZone(string $apex, bool $adoptExisting = false): array
    {
        $this->assertConfigured();
        $apex = $this->normalizeApex($apex);

        if (! $adoptExisting) {
            $existing = $this->getAccountDomain($apex);
            if ($existing !== null && $existing['zone']) {
                return $this->attachAccountDomainMutationMeta($existing, wasCreated: false, wasAdopted: true);
            }
        }

        $response = $this->sendRequest(fn () => $this->http()->patch(
            $this->accountDomainUrl($apex, 'v3'),
            [
                'op' => 'update',
                'zone' => true,
            ]
        ));

        if ($response->successful()) {
            $refreshed = $this->getAccountDomain($apex);
            $normalized = $refreshed ?? $this->normalizeAccountDomain([
                'domain' => ['name' => $apex, 'zone' => true],
            ]);

            return $this->attachAccountDomainMutationMeta($normalized, wasCreated: false, wasAdopted: false);
        }

        if (in_array($response->status(), [400, 409], true)) {
            $existing = $this->getAccountDomain($apex);
            if ($existing !== null && $existing['zone']) {
                return $this->attachAccountDomainMutationMeta($existing, wasCreated: false, wasAdopted: true);
            }
        }

        $this->throwFromResponse('Failed to enable account domain zone on Vercel', $response);
    }

    /**
     * @return array{
     *     certificates: list<array{
     *         id: string,
     *         cns: list<string>,
     *         createdAt: ?int,
     *         expiresAt: ?int,
     *         autoRenew: bool,
     *         readiness: string,
     *         raw: array<string, mixed>
     *     }>,
     *     is_lower_bound: bool
     * }
     */
    public function listCertificates(): array
    {
        $this->assertConfigured();

        $certificates = [];
        $until = null;
        $maxPages = 10;
        $isLowerBound = false;

        for ($page = 0; $page < $maxPages; $page++) {
            $path = '/v8/certs?limit=100';
            if ($until !== null) {
                $path .= '&until=' . rawurlencode((string) $until);
            }
            $path = $this->appendTeamId($path);

            $response = $this->sendRequest(fn () => $this->http()->get($path));

            if (! $response->successful()) {
                $this->throwFromResponse('Failed to list certificates from Vercel', $response);
            }

            $body = $response->json() ?? [];
            foreach ($body['certs'] ?? [] as $cert) {
                if (! is_array($cert)) {
                    continue;
                }

                $certificates[] = $this->normalizeCertificate($cert);
            }

            $next = $body['pagination']['next'] ?? null;
            if ($next === null) {
                return [
                    'certificates' => $certificates,
                    'is_lower_bound' => false,
                ];
            }

            $until = $next;
            if ($page === $maxPages - 1) {
                $isLowerBound = true;
            }
        }

        return [
            'certificates' => $certificates,
            'is_lower_bound' => $isLowerBound,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     raw: array<string, mixed>
     * }
     */
    public function getCertificate(string $certId): array
    {
        $this->assertConfigured();
        $certId = trim($certId);

        $response = $this->sendRequest(fn () => $this->http()->get(
            $this->appendTeamId('/v8/certs/' . rawurlencode($certId))
        ));

        if ($response->status() === 404) {
            throw new VercelDomainException(
                'Certificate not found on Vercel',
                404,
                $response->json(),
                VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        if (! $response->successful()) {
            $this->throwFromResponse('Failed to fetch certificate from Vercel', $response);
        }

        return $this->normalizeCertificate($response->json() ?? []);
    }

    /**
     * @param  list<string>|string  $cns
     * @return array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     was_created: bool,
     *     was_adopted: bool,
     *     raw: array<string, mixed>
     * }
     */
    public function issueCertificate(array|string $cns): array
    {
        $this->assertConfigured();
        $normalizedCns = $this->normalizeCertificateNames($cns);

        foreach ($normalizedCns as $cn) {
            $existing = $this->findCoveringCertificate($cn);
            if ($existing !== null && $this->isCertificateReady($existing)) {
                return $this->attachCertificateMutationMeta($existing, wasCreated: false, wasAdopted: true);
            }
        }

        $response = $this->sendRequest(fn () => $this->http()->post(
            $this->accountDomainsCollectionUrl('/v8/certs'),
            ['cns' => $normalizedCns]
        ));

        if ($response->successful()) {
            $normalized = $this->normalizeCertificate($response->json() ?? []);

            return $this->attachCertificateMutationMeta($normalized, wasCreated: true, wasAdopted: false);
        }

        if (in_array($response->status(), [400, 409], true)) {
            foreach ($normalizedCns as $cn) {
                $existing = $this->findCoveringCertificate($cn);
                if ($existing !== null) {
                    return $this->attachCertificateMutationMeta($existing, wasCreated: false, wasAdopted: true);
                }
            }
        }

        $this->throwFromResponse('Failed to issue certificate on Vercel', $response);
    }

    /**
     * @param  array{
     *     cns?: list<string>|mixed,
     *     expiresAt?: int|null,
     *     readiness?: string
     * }  $certificate
     */
    public function certificateCoversHost(string $host, array $certificate): bool
    {
        $host = strtolower(trim($host));
        $cns = $certificate['cns'] ?? [];
        if (! is_array($cns) || $cns === []) {
            return false;
        }

        if ($this->isCertificateExpired($certificate)) {
            return false;
        }

        foreach ($cns as $san) {
            if (! is_string($san)) {
                continue;
            }

            if ($this->sanCoversHost($san, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{
     *     certificates?: list<array<string, mixed>>
     * }|null  $inventory
     * @return array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     raw: array<string, mixed>
     * }|null
     */
    public function findCoveringCertificate(string $host, ?array $inventory = null): ?array
    {
        $host = strtolower(trim($host));
        $certificates = $inventory['certificates'] ?? null;
        if ($certificates === null) {
            $certificates = $this->listCertificates()['certificates'];
        }

        foreach ($certificates as $certificate) {
            if (! is_array($certificate)) {
                continue;
            }

            if ($this->certificateCoversHost($host, $certificate)) {
                return $certificate;
            }
        }

        return null;
    }

    public function isCertificateReady(array $certificate): bool
    {
        return ($certificate['readiness'] ?? '') === 'issued';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDomainConfig(string $domain): array
    {
        $this->assertConfigured();
        $domain = $this->normalizeApex($domain);

        $response = $this->sendRequest(fn () => $this->http()->get(
            $this->domainConfigUrl($domain)
        ));

        if ($response->status() === 404) {
            throw new VercelDomainException(
                __('domain_health.sync.not_attached'),
                404,
                $response->json(),
                VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        if (! $response->successful()) {
            $this->throwFromResponse('Failed to fetch domain DNS config from Vercel', $response);
        }

        $body = $response->json() ?? [];

        return [
            'misconfigured' => (bool) ($body['misconfigured'] ?? false),
            'configuredBy' => $body['configuredBy'] ?? null,
            'acceptedChallenges' => $body['acceptedChallenges'] ?? [],
            'recommendedIPv4' => $body['recommendedIPv4'] ?? ($body['aValues'] ?? []),
            'recommendedCNAME' => $body['recommendedCNAME'] ?? ($body['cnames'] ?? []),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getDomainVerification(string $domain): array
    {
        $record = $this->getDomain(strtolower(trim($domain)));
        if ($record === null) {
            throw new VercelDomainException(
                __('domain_health.sync.not_attached'),
                404,
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        return $this->sanitizeVerification($record['verification'] ?? []);
    }

    public function normalizeApex(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;

        return $domain;
    }

    /**
     * @return array{names: list<string>, domains: list<array<string, mixed>>, is_lower_bound: bool}
     */
    private function fetchProjectDomainPages(): array
    {
        $names = [];
        $domains = [];
        $until = null;
        $maxPages = 10;

        for ($page = 0; $page < $maxPages; $page++) {
            $path = '/domains?limit=100';
            if ($until !== null) {
                $path .= '&until=' . rawurlencode((string) $until);
            }

            $response = $this->sendRequest(fn () => $this->http()->get(
                $this->projectUrl($path, 'v9')
            ));

            if (! $response->successful()) {
                $this->throwFromResponse('Failed to list project domains from Vercel', $response);
            }

            $body = $response->json() ?? [];
            $pageDomains = $body['domains'] ?? [];

            foreach ($pageDomains as $domain) {
                if (! is_array($domain) || ! isset($domain['name'])) {
                    continue;
                }

                $metadata = $this->extractDomainMetadata($domain);
                if ($metadata['name'] === '') {
                    continue;
                }

                $domains[] = $metadata;
                $names[] = $metadata['name'];
            }

            $next = $body['pagination']['next'] ?? null;
            if ($next === null) {
                return [
                    'names' => $names,
                    'domains' => $domains,
                    'is_lower_bound' => false,
                ];
            }

            $until = $next;
        }

        return [
            'names' => $names,
            'domains' => $domains,
            'is_lower_bound' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $domain
     * @return array<string, mixed>
     */
    private function extractDomainMetadata(array $domain): array
    {
        $name = strtolower((string) ($domain['name'] ?? ''));

        return [
            'name' => $name,
            'apexName' => isset($domain['apexName'])
                ? strtolower((string) $domain['apexName'])
                : $this->normalizeApex($name),
            'verified' => (bool) ($domain['verified'] ?? false),
            'redirect' => isset($domain['redirect']) && $domain['redirect'] !== null
                ? strtolower((string) $domain['redirect'])
                : null,
            'redirectStatusCode' => isset($domain['redirectStatusCode'])
                ? (int) $domain['redirectStatusCode']
                : null,
            'createdAt' => $domain['createdAt'] ?? null,
            'verification' => $this->sanitizeVerification($domain['verification'] ?? []),
        ];
    }

    /**
     * @param  mixed  $verification
     * @return list<array<string, mixed>>
     */
    private function sanitizeVerification(mixed $verification): array
    {
        if (! is_array($verification)) {
            return [];
        }

        $items = array_is_list($verification) ? $verification : [$verification];
        $sanitized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $sanitized[] = array_filter([
                'type' => $item['type'] ?? null,
                'domain' => isset($item['domain']) ? strtolower((string) $item['domain']) : null,
                'value' => $item['value'] ?? null,
                'reason' => $item['reason'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $existing
     */
    private function assertAdoptedRedirectMatches(array $existing, string $expectedTarget, int $expectedStatusCode): void
    {
        $actualTarget = isset($existing['redirect']) ? strtolower((string) $existing['redirect']) : null;
        $actualStatusCode = isset($existing['redirectStatusCode']) ? (int) $existing['redirectStatusCode'] : null;

        $targetMatches = $actualTarget === $this->normalizeApex($expectedTarget);
        $statusMatches = $actualStatusCode === null
            || $actualStatusCode === $expectedStatusCode
            || ($expectedStatusCode === 301 && $actualStatusCode === 308)
            || ($expectedStatusCode === 308 && $actualStatusCode === 301);

        if ($targetMatches && $statusMatches) {
            return;
        }

        throw new VercelDomainException(
            __('domain_mutation.redirect_mismatch', [
                'domain' => (string) ($existing['name'] ?? $expectedTarget),
                'expected_target' => $expectedTarget,
                'expected_status' => (string) $expectedStatusCode,
            ]),
            internalCode: VercelDomainException::CODE_REDIRECT_MISMATCH
        );
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function attachMutationMeta(array $body, bool $wasCreated, bool $wasAdopted): array
    {
        $body['was_created'] = $wasCreated;
        $body['was_adopted'] = $wasAdopted;

        return $body;
    }

    private function sendRequest(callable $callback): Response
    {
        $maxAttempts = max(1, (int) config('services.vercel.retry_max_attempts', 3));
        $baseDelayMs = max(100, (int) config('services.vercel.retry_base_delay_ms', 500));
        $lastConnectionException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                /** @var Response $response */
                $response = $callback();

                if ($this->shouldRetryResponse($response) && $attempt < $maxAttempts) {
                    usleep($baseDelayMs * 1000 * $attempt);

                    continue;
                }

                return $response;
            } catch (ConnectionException $exception) {
                $lastConnectionException = $exception;

                if ($attempt < $maxAttempts) {
                    usleep($baseDelayMs * 1000 * $attempt);

                    continue;
                }
            }
        }

        throw new VercelDomainException(
            __('domain_mutation.provider_unavailable'),
            internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE,
            previous: $lastConnectionException
        );
    }

    private function shouldRetryResponse(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        return $response->status() >= 500;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.vercel.base_url'), '/'))
            ->withToken((string) config('services.vercel.token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.vercel.http_timeout', 30));
    }

    private function projectUrl(string $path, string $version = 'v10'): string
    {
        $project = rawurlencode((string) config('services.vercel.project_id'));
        $url = '/' . $version . '/projects/' . $project . $path;

        return $this->appendTeamId($url);
    }

    private function domainConfigUrl(string $domain): string
    {
        return $this->appendTeamId('/v6/domains/' . rawurlencode($domain) . '/config');
    }

    private function accountDomainUrl(string $domain, string $version): string
    {
        return $this->appendTeamId('/' . $version . '/domains/' . rawurlencode($domain));
    }

    private function accountDomainsCollectionUrl(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $this->appendTeamId($path);
        }

        return $this->appendTeamId('/' . $path);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     raw: array<string, mixed>
     * }
     */
    private function normalizeAccountDomain(array $body): array
    {
        $domain = is_array($body['domain'] ?? null) ? $body['domain'] : $body;
        $name = strtolower((string) ($domain['name'] ?? ''));
        $serviceType = $this->normalizeNullableString($domain['serviceType'] ?? null);
        $zone = $this->resolveAccountDomainZone($domain, $serviceType);

        return [
            'name' => $name,
            'zone' => $zone,
            'verified' => (bool) ($domain['verified'] ?? false),
            'serviceType' => $serviceType,
            'nameservers' => $this->normalizeStringList($domain['nameservers'] ?? []),
            'intendedNameservers' => $this->normalizeStringList($domain['intendedNameservers'] ?? []),
            'id' => $this->normalizeNullableString($domain['id'] ?? null),
            'raw' => $domain,
        ];
    }

    /**
     * @param  array<string, mixed>  $domain
     */
    private function resolveAccountDomainZone(array $domain, ?string $serviceType): bool
    {
        if (array_key_exists('zone', $domain)) {
            return (bool) $domain['zone'];
        }

        return $serviceType === 'zeit.world';
    }

    /**
     * @param  array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     raw: array<string, mixed>
     * }  $normalized
     * @return array{
     *     name: string,
     *     zone: bool,
     *     verified: bool,
     *     serviceType: ?string,
     *     nameservers: list<string>,
     *     intendedNameservers: list<string>,
     *     id: ?string,
     *     was_created: bool,
     *     was_adopted: bool,
     *     raw: array<string, mixed>
     * }
     */
    private function attachAccountDomainMutationMeta(array $normalized, bool $wasCreated, bool $wasAdopted): array
    {
        $normalized['was_created'] = $wasCreated;
        $normalized['was_adopted'] = $wasAdopted;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     raw: array<string, mixed>
     * }
     */
    private function normalizeCertificate(array $body): array
    {
        $id = (string) ($body['id'] ?? '');
        $normalized = [
            'id' => $id,
            'cns' => $this->normalizeCertificateNames($body['cns'] ?? []),
            'createdAt' => isset($body['createdAt']) ? (int) $body['createdAt'] : null,
            'expiresAt' => isset($body['expiresAt']) ? (int) $body['expiresAt'] : null,
            'autoRenew' => (bool) ($body['autoRenew'] ?? false),
            'raw' => $body,
        ];
        $normalized['readiness'] = $this->classifyCertificateReadiness($normalized);

        return $normalized;
    }

    /**
     * @param  array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     raw: array<string, mixed>
     * }  $normalized
     * @return array{
     *     id: string,
     *     cns: list<string>,
     *     createdAt: ?int,
     *     expiresAt: ?int,
     *     autoRenew: bool,
     *     readiness: string,
     *     was_created: bool,
     *     was_adopted: bool,
     *     raw: array<string, mixed>
     * }
     */
    private function attachCertificateMutationMeta(array $normalized, bool $wasCreated, bool $wasAdopted): array
    {
        $normalized['was_created'] = $wasCreated;
        $normalized['was_adopted'] = $wasAdopted;

        return $normalized;
    }

    /**
     * @param  array{expiresAt?: ?int, cns?: list<string>}  $certificate
     */
    private function classifyCertificateReadiness(array $certificate): string
    {
        if ($this->isCertificateExpired($certificate)) {
            return 'certificate_error';
        }

        $expiresAt = $certificate['expiresAt'] ?? null;
        $cns = $certificate['cns'] ?? [];

        if ($expiresAt !== null && $expiresAt > 0 && $cns !== []) {
            return 'issued';
        }

        if ($cns !== []) {
            return 'pending';
        }

        return 'pending';
    }

    /**
     * @param  array{expiresAt?: ?int}  $certificate
     */
    private function isCertificateExpired(array $certificate): bool
    {
        $expiresAt = $certificate['expiresAt'] ?? null;
        if ($expiresAt === null || $expiresAt <= 0) {
            return false;
        }

        return $expiresAt <= (int) (microtime(true) * 1000);
    }

    private function sanCoversHost(string $san, string $host): bool
    {
        $san = strtolower(trim($san));
        $host = strtolower(trim($host));

        if ($san === $host) {
            return true;
        }

        if (! str_starts_with($san, '*.')) {
            return false;
        }

        $base = substr($san, 2);
        if ($base === '' || $host === $base) {
            return false;
        }

        if (! str_ends_with($host, '.' . $base)) {
            return false;
        }

        $prefix = substr($host, 0, -(strlen($base) + 1));

        return $prefix !== '' && ! str_contains($prefix, '.');
    }

    /**
     * @param  list<string>|string|mixed  $cns
     * @return list<string>
     */
    private function normalizeCertificateNames(mixed $cns): array
    {
        if (is_string($cns)) {
            $cns = [$cns];
        }

        if (! is_array($cns)) {
            return [];
        }

        $normalized = [];
        foreach ($cns as $cn) {
            if (! is_string($cn)) {
                continue;
            }

            $cn = strtolower(trim($cn));
            if ($cn !== '') {
                $normalized[] = $cn;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = strtolower(trim($value));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    private function appendTeamId(string $url): string
    {
        $teamId = config('services.vercel.team_id');
        if (filled($teamId)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'teamId=' . rawurlencode((string) $teamId);
        }

        return $url;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
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

        $providerCode = is_array($body) ? ($body['error']['code'] ?? null) : null;
        $internalCode = VercelDomainException::mapProviderCode(
            is_string($providerCode) ? $providerCode : null,
            $response->status()
        );

        $detail = is_array($body)
            ? ($body['error']['message'] ?? $body['message'] ?? json_encode($body))
            : (string) $body;

        throw new VercelDomainException(
            $message . ($detail ? ': ' . $detail : ''),
            $response->status(),
            $body,
            $internalCode
        );
    }
}
