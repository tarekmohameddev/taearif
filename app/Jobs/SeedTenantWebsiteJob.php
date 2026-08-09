<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TenantWebsiteSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SeedTenantWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Seconds to wait before each retry.
     *
     * @var array<int, int>
     */
    public $backoff = [10, 30, 60];

    public function __construct(public int $userId)
    {
    }

    public function handle(TenantWebsiteSeeder $seeder): void
    {
        $tenant = User::find($this->userId);

        if (!$tenant) {
            Log::warning('SeedTenantWebsiteJob: tenant not found, skipping.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $seeder->seedIfEmpty($tenant);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SeedTenantWebsiteJob failed permanently', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
