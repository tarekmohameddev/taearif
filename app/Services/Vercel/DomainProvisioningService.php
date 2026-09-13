<?php

namespace App\Services\Vercel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class DomainProvisioningService
{
    public const MODE_INITIAL = 'initial';

    public const MODE_SCHEDULED = 'scheduled';

    public const MODE_ADMIN_REPAIR = 'admin_repair';

    public const MODE_CLAIM_OWNERSHIP = 'claim_ownership';

    public const CLASS_CREATED = 'created';

    public const CLASS_ADOPTED = 'adopted';

    public const CLASS_PRE_EXISTING = 'pre_existing';

    public const CLASS_UNCERTAIN = 'uncertain';

    /** @var list<string> */
    private const MUTATION_MODES = [
        self::MODE_INITIAL,
        self::MODE_SCHEDULED,
        self::MODE_ADMIN_REPAIR,
        self::MODE_CLAIM_OWNERSHIP,
    ];

    public function __construct(
        private readonly VercelDomainClient $client,
        private readonly VercelDomainCache $cache,
        private readonly VercelDomainInventoryService $inventory,
        private readonly VercelMutationGuard $mutationGuard,
        private readonly DnsNameserverChecker $nameserverChecker
    ) {
    }

    /**
     * Run guarded domain provisioning or verification-only reconciliation.
     *
     * @return array{
     *     outcome: string,
     *     health: string,
     *     ssl: bool,
     *     retryable: bool,
     *     message: string,
     *     provisioning: array<string, mixed>,
     *     last_check: array<string, mixed>
     * }
     */
    public function run(string $apex, ?string $mode = null): array
    {
        $apex = $this->client->normalizeApex($apex);
        $mutating = $mode !== null && in_array($mode, self::MUTATION_MODES, true);

        if ($mutating) {
            return $this->cache->withMutationLock(function () use ($apex, $mode) {
                return $this->executeMutatingRun($apex, $mode);
            });
        }

        return $this->executeVerificationOnly($apex);
    }

    /**
     * @return array{
     *     outcome: string,
     *     health: string,
     *     ssl: bool,
     *     retryable: bool,
     *     message: string,
     *     provisioning: array<string, mixed>,
     *     last_check: array<string, mixed>
     * }
     */
    private function executeMutatingRun(string $apex, string $mode): array
    {
        try {
            $this->mutationGuard->assertCanMutate();
        } catch (VercelDomainException $exception) {
            return $this->failureResult(
                $this->guardFailureHealth($exception),
                $this->guardFailureMessage($exception),
                retryable: false,
                provisioning: [
                    'mode' => $mode,
                    'mutations_attempted' => false,
                    'internal_code' => $exception->internalCode,
                ]
            );
        }

        if ($mode === self::MODE_INITIAL
            || ($mode === self::MODE_ADMIN_REPAIR && $this->apexNeedsAttach($apex))) {
            $inventory = $this->cache->fresh();
            $capacity = $this->inventory->evaluateCapacityForApex($inventory, $apex);
            if (! $capacity['allowed']) {
                return $this->failureResult(
                    'provider_error',
                    $capacity['reason'] === 'capacity_reached'
                        ? 'Hosting capacity has been reached.'
                        : 'Hosting inventory could not be confirmed safely.',
                    retryable: $capacity['reason'] !== 'capacity_reached',
                    provisioning: [
                        'mode' => $mode,
                        'mutations_attempted' => false,
                        'capacity_reason' => $capacity['reason'],
                    ]
                );
            }
        }

        $ledger = $this->newLedger($mode);

        try {
            $this->performModeMutations($apex, $mode, $ledger);
        } catch (VercelDomainException $exception) {
            if ($this->isTransportAmbiguity($exception)) {
                $ledger['uncertain'] = true;
                $ledger['internal_code'] = $exception->internalCode;
            } else {
                $ledger['internal_code'] = $exception->internalCode;
                $this->rollbackCreatedResources($apex, $ledger);

                $health = $this->exceptionHealth($exception);

                return $this->failureResult(
                    $health,
                    $this->exceptionMessage($exception, $health),
                    retryable: $this->isRetryableException($exception),
                    provisioning: $this->buildProvisioningSummary($ledger),
                    reason: $health === 'invalid_domain' ? 'invalid_domain' : null,
                );
            }
        }

        $this->cache->invalidateAdminCaches();

        return $this->buildResultFromFreshState($apex, $ledger);
    }

    /**
     * @return array{
     *     outcome: string,
     *     health: string,
     *     ssl: bool,
     *     retryable: bool,
     *     message: string,
     *     provisioning: array<string, mixed>,
     *     last_check: array<string, mixed>
     * }
     */
    private function executeVerificationOnly(string $apex): array
    {
        $ledger = $this->newLedger(null);

        return $this->buildResultFromFreshState($apex, $ledger);
    }

    /**
     * @param  array<string, mixed>  $ledger
     */
    private function performModeMutations(string $apex, string $mode, array &$ledger): void
    {
        $ledger['mutations_attempted'] = true;
        $accountDomain = $this->client->getAccountDomain($apex);

        if ($mode === self::MODE_INITIAL) {
            $this->mutateInitial($apex, $accountDomain, $ledger);

            return;
        }

        if ($mode === self::MODE_CLAIM_OWNERSHIP) {
            $this->mutateClaimOwnership($apex, $ledger);

            return;
        }

        $this->mutateRepair($apex, $accountDomain, $ledger, $mode);
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
     * }|null  $accountDomain
     * @param  array<string, mixed>  $ledger
     */
    private function mutateInitial(string $apex, ?array $accountDomain, array &$ledger): void
    {
        if ($accountDomain === null) {
            $result = $this->client->createAccountDomain($apex);
            $ledger['account_domain'] = $this->classifyMutationResult($result);
            $ledger['account_domain_created'] = ($result['was_created'] ?? false) === true;
            $ledger['zone'] = $this->classifyZoneResult($result);
            $ledger['zone_enabled'] = ($result['zone'] ?? false) === true && ($result['was_created'] ?? false) === true;
            $accountDomain = $result;
        } elseif (! $accountDomain['zone']) {
            $result = $this->client->enableAccountDomainZone($apex);
            $ledger['zone'] = $this->classifyMutationResult($result);
            $ledger['zone_enabled'] = ($result['was_created'] ?? false) === false
                && ($result['was_adopted'] ?? false) === false;
            $accountDomain = $result;
        } else {
            $ledger['account_domain'] = self::CLASS_PRE_EXISTING;
            $ledger['zone'] = self::CLASS_PRE_EXISTING;
        }

        $projectDomain = $this->client->getDomain($apex);
        if ($projectDomain === null) {
            $attach = $this->client->addDomain($apex);
            $ledger['apex_attachment'] = $this->classifyMutationResult($attach);
            $ledger['apex_attachment_created'] = ($attach['was_created'] ?? false) === true;
        } else {
            $ledger['apex_attachment'] = self::CLASS_PRE_EXISTING;
        }

        $this->maybeIssueApexCertificate($apex, $ledger);
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
     * }|null  $accountDomain
     * @param  array<string, mixed>  $ledger
     */
    /**
     * @param  array<string, mixed>  $ledger
     */
    private function mutateClaimOwnership(string $apex, array &$ledger): void
    {
        try {
            $this->client->claimDomainOwnership($apex);
            $ledger['ownership_claim'] = self::CLASS_CREATED;
        } catch (VercelDomainException $exception) {
            if ($this->isTransportAmbiguity($exception)) {
                $ledger['ownership_claim'] = self::CLASS_UNCERTAIN;
                $ledger['uncertain'] = true;
                $ledger['internal_code'] = $exception->internalCode;

                return;
            }

            if (in_array($exception->internalCode, [
                VercelDomainException::CODE_INVALID_DOMAIN,
                VercelDomainException::CODE_OWNERSHIP_REQUIRED,
            ], true)) {
                $ledger['ownership_claim'] = self::CLASS_PRE_EXISTING;
            } else {
                throw $exception;
            }
        }

        $accountDomain = $this->client->getAccountDomain($apex);
        $this->mutateRepair($apex, $accountDomain, $ledger, self::MODE_ADMIN_REPAIR);
    }

    private function mutateRepair(string $apex, ?array $accountDomain, array &$ledger, string $mode): void
    {
        $projectDomain = $this->client->getDomain($apex);

        if ($projectDomain === null && $mode === self::MODE_ADMIN_REPAIR) {
            $this->mutateInitial($apex, $accountDomain, $ledger);

            return;
        }

        if ($accountDomain !== null) {
            $ledger['account_domain'] = self::CLASS_PRE_EXISTING;
        }

        if ($accountDomain !== null && ! $accountDomain['zone']) {
            if ($this->publicNameserversMatch($apex)) {
                $result = $this->client->enableAccountDomainZone($apex);
                $ledger['zone'] = $this->classifyMutationResult($result);
                $ledger['zone_enabled'] = ($result['was_created'] ?? false) === false
                    && ($result['was_adopted'] ?? false) === false;
            } else {
                $ledger['zone'] = self::CLASS_PRE_EXISTING;
            }
        } elseif ($accountDomain !== null && $accountDomain['zone']) {
            $ledger['zone'] = self::CLASS_PRE_EXISTING;
        }

        if (empty($projectDomain['verified'])) {
            try {
                $this->client->verifyDomain($apex);
            } catch (VercelDomainException $exception) {
                if (! $this->isTransportAmbiguity($exception)) {
                    throw $exception;
                }

                $ledger['uncertain'] = true;
                $ledger['internal_code'] = $exception->internalCode;
            }
        }

        $ledger['apex_attachment'] = self::CLASS_PRE_EXISTING;

        $this->maybeIssueApexCertificate($apex, $ledger);
    }

    /**
     * @param  array<string, mixed>  $ledger
     */
    private function maybeIssueApexCertificate(string $apex, array &$ledger): void
    {
        if (($ledger['certificate'] ?? null) !== null) {
            return;
        }

        $inventory = $this->client->listCertificates();
        $existing = $this->client->findCoveringCertificate($apex, $inventory);
        if ($existing !== null) {
            // A covering certificate already exists (issued or still validating);
            // never reissue. Treat it as pre-existing and let status resolution
            // classify readiness from certificate_readiness.
            $ledger['certificate'] = self::CLASS_PRE_EXISTING;

            return;
        }

        $projectDomain = $this->client->getDomain($apex);
        if ($projectDomain === null || empty($projectDomain['verified'])) {
            return;
        }

        $accountDomain = $this->client->getAccountDomain($apex);
        if ($accountDomain === null || ! $accountDomain['zone']) {
            return;
        }

        try {
            $result = $this->client->issueCertificate($apex);
            $ledger['certificate'] = $this->classifyMutationResult($result);
            $ledger['certificate_created'] = ($result['was_created'] ?? false) === true;
        } catch (VercelDomainException $exception) {
            if ($this->isTransportAmbiguity($exception)) {
                $ledger['certificate'] = self::CLASS_UNCERTAIN;
                $ledger['uncertain'] = true;
                $ledger['internal_code'] = $exception->internalCode;

                return;
            }

            if ($this->isCertificatePendingException($exception)) {
                $ledger['certificate'] = self::CLASS_PRE_EXISTING;

                return;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $ledger
     * @return array{
     *     outcome: string,
     *     health: string,
     *     ssl: bool,
     *     retryable: bool,
     *     message: string,
     *     provisioning: array<string, mixed>,
     *     last_check: array<string, mixed>
     * }
     */
    private function buildResultFromFreshState(string $apex, array $ledger): array
    {
        $this->cache->invalidateAdminCaches();
        $projectInventory = $this->cache->fresh();
        $state = $this->collectProviderState($apex, $projectInventory, refreshDirectReads: true);
        $resolved = $this->resolveOutcome($state);

        $provisioning = $this->buildProvisioningSummary($ledger, $state);
        $lastCheck = $this->buildLastCheck($state, $resolved, $provisioning);

        if (($ledger['uncertain'] ?? false) === true && $resolved['outcome'] !== 'active') {
            $resolved['outcome'] = 'pending';
            $resolved['retryable'] = true;
            $resolved['message'] = 'Hosting provider state is uncertain. Retry verification shortly.';
            $resolved['health'] = 'provider_error';
        }

        return [
            'outcome' => $resolved['outcome'],
            'health' => $resolved['health'],
            'ssl' => $resolved['ssl'],
            'retryable' => $resolved['retryable'],
            'message' => $resolved['message'],
            'provisioning' => $provisioning,
            'last_check' => $lastCheck,
        ];
    }

    /**
     * @param  array<string, mixed>  $projectInventory
     * @return array<string, mixed>
     */
    private function collectProviderState(string $apex, array $projectInventory, bool $refreshDirectReads): array
    {
        $www = 'www.' . $apex;
        $indexed = $this->indexProjectDomains($projectInventory['domains'] ?? []);
        $apexInventory = $indexed[$apex] ?? null;
        $wwwInventory = $indexed[$www] ?? null;

        $accountDomain = $refreshDirectReads
            ? $this->safeGetAccountDomain($apex)
            : null;
        $projectDomain = $refreshDirectReads
            ? $this->safeGetProjectDomain($apex)
            : null;

        if ($projectDomain === null && $apexInventory !== null) {
            $projectDomain = $apexInventory;
        }

        $domainConfig = [
            'misconfigured' => false,
            'configuredBy' => null,
        ];
        $ownershipChallenge = null;
        $providerError = false;

        if ($refreshDirectReads && ($projectDomain !== null || $apexInventory !== null)) {
            try {
                $verification = $this->client->getDomainVerification($apex);
                $ownershipChallenge = $this->extractOwnershipChallenge($verification);
            } catch (VercelDomainException $exception) {
                if ($this->isTransportAmbiguity($exception)) {
                    $providerError = true;
                }
            }

            if (! $providerError) {
                try {
                    $domainConfig = $this->client->getDomainConfig($apex);
                } catch (VercelDomainException $exception) {
                    if ($this->isTransportAmbiguity($exception)) {
                        $providerError = true;
                    }
                }
            }
        }

        $certificateInventory = $refreshDirectReads
            ? $this->safeListCertificates()
            : ['certificates' => [], 'is_lower_bound' => false];
        $apexCertificate = $this->client->findCoveringCertificate($apex, $certificateInventory);
        $sslReady = $apexCertificate !== null && $this->client->isCertificateReady($apexCertificate);

        $expectedNs = (array) config('services.vercel.nameservers', []);
        $checkNameservers = (bool) config('services.vercel.check_nameservers', true);
        $observedNameservers = [];
        $nameserversOk = ! $checkNameservers;

        if ($checkNameservers) {
            try {
                $observedNameservers = $this->nameserverChecker->getObservedNameservers($apex);
                $nameserversOk = $this->nameserverChecker->hasExpectedNameservers($apex, $expectedNs);
            } catch (\Throwable $exception) {
                $providerError = true;
            }
        }

        $apexAttached = $projectDomain !== null || $apexInventory !== null;
        $apexVerified = $apexAttached && ! empty(($projectDomain ?? $apexInventory)['verified']);

        return [
            'apex' => $apex,
            'account_domain' => $accountDomain,
            'zone_enabled' => ($accountDomain['zone'] ?? false) === true,
            'apex_attached' => $apexAttached,
            'apex_verified' => $apexVerified,
            'www_present' => $wwwInventory !== null,
            'www_redirect_correct' => $this->isWwwRedirectCorrect($wwwInventory, $apex),
            'ownership_challenge' => $ownershipChallenge,
            'dns_misconfigured' => (bool) ($domainConfig['misconfigured'] ?? false),
            'configured_by' => $domainConfig['configuredBy'] ?? null,
            'observed_nameservers' => $observedNameservers,
            'nameservers_ok' => $nameserversOk,
            'nameserver_check_enabled' => $checkNameservers,
            'auto_attach_custom_domain' => (bool) config('services.vercel.auto_attach_custom_domain', true),
            'apex_certificate' => $apexCertificate,
            'ssl_ready' => $sslReady,
            'certificate_readiness' => $apexCertificate['readiness'] ?? null,
            'provider_error' => $providerError,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{outcome: string, health: string, ssl: bool, retryable: bool, message: string}
     */
    private function resolveOutcome(array $state): array
    {
        if ($state['provider_error'] ?? false) {
            return [
                'outcome' => 'pending',
                'health' => 'provider_error',
                'ssl' => false,
                'retryable' => true,
                'message' => 'Could not reach the hosting provider to check this domain.',
            ];
        }

        $health = $this->resolveHealthFromState($state);
        $ssl = ($state['ssl_ready'] ?? false) === true;
        $message = $this->messageForHealth($health, $state);

        if ($this->isActiveState($state, $health, $ssl)) {
            return [
                'outcome' => 'active',
                'health' => $health,
                'ssl' => $ssl,
                'retryable' => false,
                'message' => $message,
            ];
        }

        if ($health === 'certificate_error') {
            return [
                'outcome' => 'failed',
                'health' => $health,
                'ssl' => false,
                'retryable' => false,
                'message' => $message,
            ];
        }

        return [
            'outcome' => 'pending',
            'health' => $health,
            'ssl' => $ssl,
            'retryable' => in_array($health, ['provider_error', 'certificate_pending', 'unverified', 'ns_not_pointing', 'zone_disabled'], true),
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function isActiveState(array $state, string $health, bool $sslReady): bool
    {
        if (! in_array($health, ['linked', 'apex_only'], true)) {
            return false;
        }

        if (($state['zone_enabled'] ?? false) !== true) {
            return false;
        }

        if (($state['apex_verified'] ?? false) !== true) {
            return false;
        }

        if (($state['dns_misconfigured'] ?? false) === true) {
            return false;
        }

        if (($state['nameserver_check_enabled'] ?? true) && ($state['nameservers_ok'] ?? false) !== true) {
            return false;
        }

        return $sslReady;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function messageForHealth(string $health, array $state): string
    {
        return match ($health) {
            'linked' => 'Domain is verified, DNS is configured, and nameservers are correct.',
            'apex_only' => 'Apex domain is linked; optional www redirect is not configured.',
            'ownership_required' => 'Add the ownership TXT record at your DNS provider to verify this domain.',
            'dns_misconfigured' => 'DNS records are misconfigured according to the hosting provider.',
            'ns_not_pointing' => 'Nameservers are not pointing to Vercel yet.',
            'not_on_vercel' => 'Domain is not attached to the Vercel project.',
            'unverified' => 'Domain is on Vercel but not verified yet. Ensure nameservers have propagated.',
            'zone_disabled' => 'The account domain exists but its DNS zone is disabled.',
            'certificate_pending' => 'Certificate issuance or validation is still in progress.',
            'certificate_error' => 'Certificate coverage is invalid or expired.',
            'invalid_domain' => 'The hosting provider rejected this domain name as invalid or unsupported.',
            'provider_error' => 'Could not reach the hosting provider to check this domain.',
            default => 'Domain verification is still pending.',
        };
    }

    /**
     * @param  array<string, mixed>  $ledger
     */
    private function rollbackCreatedResources(string $apex, array $ledger): void
    {
        if (($ledger['apex_attachment_created'] ?? false) !== true) {
            return;
        }

        try {
            $this->client->removeDomain($apex);
        } catch (VercelDomainException|ConnectionException $exception) {
            Log::warning('Could not rollback Vercel apex attachment created during provisioning', [
                'domain' => $apex,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function publicNameserversMatch(string $apex): bool
    {
        if (! (bool) config('services.vercel.check_nameservers', true)) {
            return true;
        }

        $expected = (array) config('services.vercel.nameservers', []);

        return $this->nameserverChecker->hasExpectedNameservers($apex, $expected);
    }

    private function apexNeedsAttach(string $apex): bool
    {
        if ($this->client->getDomain($apex) !== null) {
            return false;
        }

        $inventory = $this->cache->cached();

        return $inventory === null
            || ! $this->inventory->apexPresentInSnapshot($inventory, $apex);
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @return array<string, array<string, mixed>>
     */
    private function indexProjectDomains(array $domains): array
    {
        $indexed = [];
        foreach ($domains as $domain) {
            if (! is_array($domain)) {
                continue;
            }

            $name = strtolower((string) ($domain['name'] ?? ''));
            if ($name !== '') {
                $indexed[$name] = $domain;
            }
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>|null  $wwwInventory
     */
    private function isWwwRedirectCorrect(?array $wwwInventory, string $apex): bool
    {
        if ($wwwInventory === null) {
            return false;
        }

        $redirect = isset($wwwInventory['redirect']) ? strtolower((string) $wwwInventory['redirect']) : null;
        $statusCode = isset($wwwInventory['redirectStatusCode']) ? (int) $wwwInventory['redirectStatusCode'] : null;

        if ($redirect !== $apex) {
            return false;
        }

        return $statusCode === null || in_array($statusCode, [301, 308], true);
    }

    /**
     * @param  list<array<string, mixed>>  $verification
     * @return array{type: string, domain: string, value: string, reason?: string}|null
     */
    private function extractOwnershipChallenge(array $verification): ?array
    {
        foreach ($verification as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (strtolower((string) ($item['type'] ?? '')) !== 'txt') {
                continue;
            }

            $domain = (string) ($item['domain'] ?? '');
            $value = (string) ($item['value'] ?? '');
            if ($domain === '' || $value === '') {
                continue;
            }

            return array_filter([
                'type' => 'txt',
                'domain' => strtolower($domain),
                'value' => $value,
                'reason' => $item['reason'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function classifyMutationResult(array $result): string
    {
        if (($result['was_created'] ?? false) === true) {
            return self::CLASS_CREATED;
        }

        if (($result['was_adopted'] ?? false) === true) {
            return self::CLASS_ADOPTED;
        }

        return self::CLASS_PRE_EXISTING;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function classifyZoneResult(array $result): string
    {
        if (($result['was_created'] ?? false) === true && ($result['zone'] ?? false) === true) {
            return self::CLASS_CREATED;
        }

        return $this->classifyMutationResult($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function newLedger(?string $mode): array
    {
        return [
            'mode' => $mode,
            'mutations_attempted' => false,
            'account_domain' => null,
            'zone' => null,
            'apex_attachment' => null,
            'certificate' => null,
            'account_domain_created' => false,
            'zone_enabled' => false,
            'apex_attachment_created' => false,
            'certificate_created' => false,
            'uncertain' => false,
            'internal_code' => null,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $ledger
     * @param  array<string, mixed>|null  $state
     * @return array<string, mixed>
     */
    private function buildProvisioningSummary(array $ledger, ?array $state = null): array
    {
        $summary = [
            'mode' => $ledger['mode'] ?? null,
            'mutations_attempted' => (bool) ($ledger['mutations_attempted'] ?? false),
            'account_domain' => $ledger['account_domain'] ?? null,
            'zone' => $ledger['zone'] ?? null,
            'apex_attachment' => $ledger['apex_attachment'] ?? null,
            'certificate' => $ledger['certificate'] ?? null,
            'state' => ($ledger['uncertain'] ?? false) ? self::CLASS_UNCERTAIN : null,
            'internal_code' => $ledger['internal_code'] ?? null,
            'checked_at' => $ledger['checked_at'] ?? now()->toIso8601String(),
        ];

        if ($state !== null) {
            if ($summary['account_domain'] === null && ($state['account_domain'] ?? null) !== null) {
                $summary['account_domain'] = self::CLASS_PRE_EXISTING;
            }
            if ($summary['zone'] === null) {
                $summary['zone'] = ($state['zone_enabled'] ?? false) ? self::CLASS_PRE_EXISTING : null;
            }
            if ($summary['apex_attachment'] === null) {
                $summary['apex_attachment'] = ($state['apex_attached'] ?? false)
                    ? self::CLASS_PRE_EXISTING
                    : null;
            }
            if ($summary['certificate'] === null && ($state['apex_certificate'] ?? null) !== null) {
                $summary['certificate'] = self::CLASS_PRE_EXISTING;
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array{outcome: string, health: string, ssl: bool, retryable: bool, message: string}  $resolved
     * @param  array<string, mixed>  $provisioning
     * @return array<string, mixed>
     */
    private function buildLastCheck(array $state, array $resolved, array $provisioning): array
    {
        return [
            'last_check_at' => now()->toIso8601String(),
            'health_code' => $resolved['health'],
            'message' => $resolved['message'],
            'provider_reachable' => ! ($state['provider_error'] ?? false),
            'apex_attached' => (bool) ($state['apex_attached'] ?? false),
            'apex_verified' => (bool) ($state['apex_verified'] ?? false),
            'vercel_attached' => (bool) ($state['apex_attached'] ?? false),
            'vercel_verified' => (bool) ($state['apex_verified'] ?? false),
            'zone_enabled' => (bool) ($state['zone_enabled'] ?? false),
            'www_present' => (bool) ($state['www_present'] ?? false),
            'www_redirect_correct' => (bool) ($state['www_redirect_correct'] ?? false),
            'ownership_challenge' => $state['ownership_challenge'] ?? null,
            'observed_nameservers' => $state['observed_nameservers'] ?? [],
            'nameservers_ok' => (bool) ($state['nameservers_ok'] ?? false),
            'nameserver_check_enabled' => (bool) ($state['nameserver_check_enabled'] ?? true),
            'dns_misconfigured' => (bool) ($state['dns_misconfigured'] ?? false),
            'configured_by' => $state['configured_by'] ?? null,
            'certificate_readiness' => $state['certificate_readiness'] ?? null,
            'ssl_ready' => (bool) ($state['ssl_ready'] ?? false),
            'auto_attach_custom_domain' => (bool) ($state['auto_attach_custom_domain'] ?? true),
            'provisioning' => $provisioning,
            'outcome' => $resolved['outcome'],
            'retryable' => $resolved['retryable'],
        ];
    }

    /**
     * @param  array<string, mixed>  $provisioning
     * @return array{
     *     outcome: string,
     *     health: string,
     *     ssl: bool,
     *     retryable: bool,
     *     message: string,
     *     provisioning: array<string, mixed>,
     *     last_check: array<string, mixed>
     * }
     */
    private function failureResult(
        string $health,
        string $message,
        bool $retryable,
        array $provisioning,
        ?string $reason = null
    ): array {
        $provisioning['checked_at'] = now()->toIso8601String();
        $lastCheck = [
            'last_check_at' => now()->toIso8601String(),
            'health_code' => $health,
            'message' => $message,
            // invalid_domain means Vercel responded (it was reachable) and rejected
            // the name; only genuine transport failures are unreachable.
            'provider_reachable' => $reason === 'invalid_domain',
            'reason' => $reason,
            'provisioning' => $provisioning,
            'outcome' => 'failed',
            'retryable' => $retryable,
        ];

        return [
            'outcome' => 'failed',
            'health' => $health,
            'ssl' => false,
            'retryable' => $retryable,
            'message' => $message,
            'provisioning' => $provisioning,
            'last_check' => $lastCheck,
        ];
    }

    private function guardFailureHealth(VercelDomainException $exception): string
    {
        return match ($exception->internalCode) {
            VercelDomainException::CODE_MUTATION_BLOCKED,
            VercelDomainException::CODE_NOT_CONFIGURED,
            VercelDomainException::CODE_PROJECT_IDENTITY_MISMATCH => 'provider_error',
            default => 'provider_error',
        };
    }

    private function guardFailureMessage(VercelDomainException $exception): string
    {
        return $exception->getMessage() !== ''
            ? $exception->getMessage()
            : 'Domain hosting is not available in this environment.';
    }

    private function exceptionHealth(VercelDomainException $exception): string
    {
        return match ($exception->internalCode) {
            VercelDomainException::CODE_OWNERSHIP_REQUIRED => 'ownership_required',
            VercelDomainException::CODE_INVALID_DOMAIN => 'invalid_domain',
            default => 'provider_error',
        };
    }

    private function exceptionMessage(VercelDomainException $exception, string $health): string
    {
        // A raw "invalid domain name" provider string is confusing to admins; give
        // a clear, localized explanation instead. Other errors keep their message.
        if ($health === 'invalid_domain') {
            return __('domain_health.invalid_domain_message');
        }

        return $exception->getMessage();
    }

    private function isRetryableException(VercelDomainException $exception): bool
    {
        return in_array($exception->internalCode, [
            VercelDomainException::CODE_RATE_LIMITED,
            VercelDomainException::CODE_PROVIDER_UNAVAILABLE,
        ], true);
    }

    private function isTransportAmbiguity(VercelDomainException $exception): bool
    {
        if ($exception->internalCode === VercelDomainException::CODE_RATE_LIMITED) {
            return true;
        }

        return $exception->internalCode === VercelDomainException::CODE_PROVIDER_UNAVAILABLE
            && $exception->getPrevious() instanceof ConnectionException;
    }

    private function isCertificatePendingException(VercelDomainException $exception): bool
    {
        if ($this->isTransportAmbiguity($exception)) {
            return true;
        }

        return $exception->internalCode === VercelDomainException::CODE_VERIFICATION_PENDING;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeGetAccountDomain(string $apex): ?array
    {
        try {
            return $this->client->getAccountDomain($apex);
        } catch (VercelDomainException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeGetProjectDomain(string $apex): ?array
    {
        try {
            return $this->client->getDomain($apex);
        } catch (VercelDomainException) {
            return null;
        }
    }

    /**
     * @return array{certificates: list<array<string, mixed>>, is_lower_bound: bool}
     */
    private function safeListCertificates(): array
    {
        try {
            return $this->client->listCertificates();
        } catch (VercelDomainException) {
            return [
                'certificates' => [],
                'is_lower_bound' => false,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function resolveHealthFromState(array $state): string
    {
        if (($state['provider_error'] ?? false) === true) {
            return 'provider_error';
        }

        if (($state['apex_attached'] ?? false) !== true) {
            return 'not_on_vercel';
        }

        if (is_array($state['ownership_challenge'] ?? null)
            && ($state['ownership_challenge'] ?? []) !== []
            && ($state['apex_verified'] ?? false) !== true) {
            return 'ownership_required';
        }

        if (($state['account_domain'] ?? null) !== null && ($state['zone_enabled'] ?? false) !== true) {
            return 'zone_disabled';
        }

        if (($state['dns_misconfigured'] ?? false) === true) {
            return 'dns_misconfigured';
        }

        if (($state['nameserver_check_enabled'] ?? true) && ($state['nameservers_ok'] ?? false) !== true) {
            return 'ns_not_pointing';
        }

        if (($state['apex_verified'] ?? false) !== true) {
            return 'unverified';
        }

        $readiness = (string) ($state['certificate_readiness'] ?? '');
        if ($readiness === 'certificate_error') {
            return 'certificate_error';
        }

        if (($state['ssl_ready'] ?? false) !== true) {
            return 'certificate_pending';
        }

        if (($state['www_present'] ?? false) !== true || ($state['www_redirect_correct'] ?? false) !== true) {
            return 'apex_only';
        }

        return 'linked';
    }
}
