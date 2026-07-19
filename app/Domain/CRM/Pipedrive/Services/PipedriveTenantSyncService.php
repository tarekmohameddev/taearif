<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Services;

use App\Domain\CRM\Pipedrive\Contracts\PipedriveClientInterface;
use App\Domain\CRM\Pipedrive\DTOs\PipedriveCredentialsDto;
use App\Domain\CRM\Pipedrive\DTOs\PipedriveSyncResultDto;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveApiException;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveNotConfiguredException;
use App\Domain\CRM\Pipedrive\Models\PipedriveSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PipedriveTenantSyncService
{
    public function __construct(
        private readonly PipedriveSettingsService $settingsService,
    ) {}

    /**
     * Sync a tenant user to Pipedrive.
     *
     * @param  string  $trigger  registration|manual|bulk
     * @param  bool  $force  Re-sync even if a deal already exists
     */
    public function sync(User $user, string $trigger, bool $force = false): PipedriveSyncResultDto
    {
        $credentials = $this->settingsService->getCredentials();

        // Auto-sync gate: skip if disabled (manual triggers always proceed if configured)
        if ($trigger === 'registration' && !$credentials->enabled) {
            $result = PipedriveSyncResultDto::skipped('Pipedrive auto-sync is disabled.');
            $this->writeLog($user, $trigger, $result, $credentials, []);

            return $result;
        }

        // Credentials must be present for any trigger
        if (!$credentials->isConfigured()) {
            throw new PipedriveNotConfiguredException();
        }

        // Idempotency: skip if already synced unless forced
        if (!$force && $user->pipedrive_deal_id) {
            $result = PipedriveSyncResultDto::skipped('User already synced to Pipedrive (deal_id=' . $user->pipedrive_deal_id . ').');
            $this->writeLog($user, $trigger, $result, $credentials, []);

            return $result;
        }

        $client = $this->buildClient($credentials);

        try {
            $result = $this->performSync($user, $client, $credentials);
        } catch (PipedriveApiException $e) {
            Log::error('Pipedrive sync failed', [
                'user_id' => $user->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
                'http_status' => $e->getHttpStatusCode(),
            ]);

            $result = PipedriveSyncResultDto::failed($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Pipedrive sync unexpected error', [
                'user_id' => $user->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);

            $result = PipedriveSyncResultDto::failed($e->getMessage());
        }

        $this->writeLog($user, $trigger, $result, $credentials, []);

        if ($result->success) {
            $user->pipedrive_person_id = $result->personId;
            $user->pipedrive_deal_id = $result->dealId;
            $user->pipedrive_synced_at = now();
            $user->save();
        }

        return $result;
    }

    /**
     * Factory method — overridable in tests to inject a mock client.
     * In production this builds a real PipedriveClient; in tests the container
     * binding for PipedriveClientInterface can be swapped via $this->mock().
     */
    protected function buildClient(PipedriveCredentialsDto $credentials): PipedriveClientInterface
    {
        if (app()->bound(PipedriveClientInterface::class)) {
            return app(PipedriveClientInterface::class);
        }

        return new PipedriveClient($credentials);
    }

    private function performSync(User $user, PipedriveClientInterface $client, PipedriveCredentialsDto $credentials): PipedriveSyncResultDto
    {
        $displayName = $this->resolveDisplayName($user);

        // Step 1: Optionally create organization
        $orgId = null;
        if ($this->shouldCreateOrg($user)) {
            $orgResponse = $client->createOrganization(['name' => $user->company_name]);
            $orgId = $orgResponse['data']['id'] ?? null;
        }

        // Step 2: Create person
        $personPayload = $this->buildPersonPayload($user, $displayName, $orgId);
        $personResponse = $client->createPerson($personPayload);
        $personId = $personResponse['data']['id'];

        // Step 3: Create deal
        $dealPayload = $this->buildDealPayload($user, $displayName, $personId, $orgId, $credentials);
        $dealResponse = $client->createDeal($dealPayload);
        $dealId = $dealResponse['data']['id'];

        return PipedriveSyncResultDto::succeeded($personId, $orgId, $dealId);
    }

    private function resolveDisplayName(User $user): string
    {
        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return trim("{$firstName} {$lastName}");
        }

        $company = trim((string) ($user->company_name ?? ''));
        if ($company !== '' && strtolower($company) !== 'n/a') {
            return $company;
        }

        $username = trim((string) ($user->username ?? ''));
        if ($username !== '') {
            return $username;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            return $email;
        }

        return "Tenant #{$user->id}";
    }

    private function shouldCreateOrg(User $user): bool
    {
        $company = trim((string) ($user->company_name ?? ''));

        return $company !== '' && strtolower($company) !== 'n/a';
    }

    private function buildPersonPayload(User $user, string $displayName, ?int $orgId): array
    {
        $payload = ['name' => $displayName];

        if (!empty($user->email)) {
            $payload['emails'] = [
                ['value' => $user->email, 'primary' => true, 'label' => 'work'],
            ];
        }

        if (!empty($user->phone)) {
            $payload['phones'] = [
                ['value' => $user->phone, 'primary' => true, 'label' => 'work'],
            ];
        }

        if ($orgId !== null) {
            $payload['org_id'] = $orgId;
        }

        return $payload;
    }

    private function buildDealPayload(User $user, string $displayName, int $personId, ?int $orgId, PipedriveCredentialsDto $credentials): array
    {
        $prefix = $credentials->dealTitlePrefix ?? 'New Website Lead - ';
        $title = $prefix . $displayName;

        $payload = [
            'title' => $title,
            'person_id' => $personId,
            'pipeline_id' => $credentials->pipelineId ?? 2,
            'stage_id' => $credentials->stageId ?? 8,
        ];

        if ($orgId !== null) {
            $payload['org_id'] = $orgId;
        }

        return $payload;
    }

    private function writeLog(
        User $user,
        string $trigger,
        PipedriveSyncResultDto $result,
        PipedriveCredentialsDto $credentials,
        array $requestPayload
    ): void {
        try {
            PipedriveSyncLog::create([
                'user_id' => $user->id,
                'status' => $result->status,
                'trigger' => $trigger,
                'person_id' => $result->personId,
                'org_id' => $result->orgId,
                'deal_id' => $result->dealId,
                'request_payload' => $requestPayload ?: null,
                'error_message' => $result->errorMessage,
                'synced_at' => $result->success ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write Pipedrive sync log', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
