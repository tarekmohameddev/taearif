<?php

namespace App\Jobs;

use App\Domain\CRM\Pipedrive\Exceptions\PipedriveNotConfiguredException;
use App\Domain\CRM\Pipedrive\Services\PipedriveTenantSyncService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTenantToPipedriveJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds after which the job's unique lock is released.
     *
     * @var int
     */
    public $uniqueFor = 300;

    /**
     * Number of times the job may be attempted (handled by service retry logic).
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(
        public int $userId,
        public string $trigger = 'registration',
    ) {}

    public function uniqueId(): string
    {
        return 'pipedrive-sync-' . $this->userId;
    }

    public function handle(PipedriveTenantSyncService $syncService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::info('SyncTenantToPipedriveJob: user not found, skipping.', ['user_id' => $this->userId]);

            return;
        }

        if (($user->account_type ?? 'tenant') !== 'tenant') {
            Log::info('SyncTenantToPipedriveJob: user is not a tenant, skipping.', ['user_id' => $this->userId]);

            return;
        }

        try {
            $syncService->sync($user, $this->trigger);
        } catch (PipedriveNotConfiguredException $e) {
            // Credentials not set — not an error worth retrying, just log
            Log::info('SyncTenantToPipedriveJob: credentials not configured, skipping.', [
                'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            // Log and let Horizon/queue monitor pick it up; do NOT propagate so
            // registration flow is never blocked.
            Log::error('SyncTenantToPipedriveJob failed', [
                'user_id' => $this->userId,
                'trigger' => $this->trigger,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
