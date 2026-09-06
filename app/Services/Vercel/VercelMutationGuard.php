<?php

namespace App\Services\Vercel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VercelMutationGuard
{
    public function __construct(
        private readonly VercelDomainClient $client
    ) {
    }

    public function assertCanMutate(
        ?Request $request = null,
        ?string $destructiveDomain = null,
        string $confirmationField = 'confirm_domain'
    ): void {
        $this->assertConfigured();
        $this->assertEnvironmentAllowsMutations();
        $this->assertProjectIdentity();

        if ($destructiveDomain !== null && $request !== null) {
            $this->assertDestructiveDomainConfirmation($request, $destructiveDomain, $confirmationField);
        }
    }

    public function assertConfigured(): void
    {
        if (! $this->client->isConfigured()) {
            $this->reject(VercelDomainException::CODE_NOT_CONFIGURED, 'domain_mutation.not_configured');
        }
    }

    public function assertEnvironmentAllowsMutations(): void
    {
        if ($this->environmentAllowsMutations()) {
            return;
        }

        $this->reject(
            VercelDomainException::CODE_MUTATION_BLOCKED,
            'domain_mutation.shared_project_blocked',
            [
                'environment' => (string) config('app.env', 'production'),
            ]
        );
    }

    public function assertProjectIdentity(): void
    {
        $expectedProjectId = (string) config('services.vercel.expected_project_id');
        $expectedTeamId = config('services.vercel.expected_team_id');
        $expectedTeamId = $expectedTeamId !== null && $expectedTeamId !== ''
            ? (string) $expectedTeamId
            : null;

        $identity = $this->client->getProjectIdentity();
        $actualProjectId = (string) ($identity['project_id'] ?? '');
        $actualTeamId = $identity['team_id'] ?? null;

        if ($actualProjectId !== $expectedProjectId) {
            $this->reject(
                VercelDomainException::CODE_PROJECT_IDENTITY_MISMATCH,
                'domain_mutation.project_identity_mismatch',
                [
                    'expected_project_id' => $expectedProjectId,
                    'actual_project_id' => $actualProjectId,
                ]
            );
        }

        if ($expectedTeamId !== null && $actualTeamId !== null && $actualTeamId !== $expectedTeamId) {
            $this->reject(
                VercelDomainException::CODE_PROJECT_IDENTITY_MISMATCH,
                'domain_mutation.team_identity_mismatch',
                [
                    'expected_team_id' => $expectedTeamId,
                    'actual_team_id' => $actualTeamId,
                ]
            );
        }
    }

    public function assertDestructiveDomainConfirmation(
        Request $request,
        string $expectedDomain,
        string $confirmationField = 'confirm_domain'
    ): void {
        $expected = $this->client->normalizeApex($expectedDomain);
        $provided = $request->input($confirmationField);

        if (! is_string($provided) || $this->client->normalizeApex($provided) !== $expected) {
            $this->reject(
                VercelDomainException::CODE_CONFIRMATION_REQUIRED,
                'domain_mutation.confirmation_required',
                ['domain' => $expected]
            );
        }
    }

    public function environmentAllowsMutations(): bool
    {
        if (app()->environment('production')) {
            return true;
        }

        return (bool) config('services.vercel.allow_shared_project_mutations', false);
    }

    public function isNonProductionSharedProject(): bool
    {
        return ! app()->environment('production')
            && $this->client->isConfigured()
            && (bool) config('services.vercel.allow_shared_project_mutations', false);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return never
     */
    private function reject(string $internalCode, string $translationKey, array $context = []): void
    {
        Log::warning('Vercel mutation rejected', array_merge([
            'internal_code' => $internalCode,
            'environment' => (string) config('app.env', 'production'),
            'project_id' => config('services.vercel.project_id'),
            'team_id' => config('services.vercel.team_id'),
        ], $context));

        throw new VercelDomainException(
            __($translationKey, $context),
            internalCode: $internalCode
        );
    }
}
