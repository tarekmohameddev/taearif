<?php

namespace App\Console\Commands;

use App\Enums\InstallStatus;
use Illuminate\Console\Command;
use App\Models\Api\ApiInstallation;

class ExpireTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-trials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire trial installations and move them to PendingPayment status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for expired trial installations...');
        
        // Get count of trials to expire
        $expiredTrialsCount = ApiInstallation::query()
            ->where('status', InstallStatus::Trialing)
            ->where('trial_ends_at', '<', now())
            ->count();

        if ($expiredTrialsCount === 0) {
            $this->info('No trial installations to expire.');
            return self::SUCCESS;
        }

        $processedCount = 0;

        ApiInstallation::query()
            ->where('status', InstallStatus::Trialing)
            ->where('trial_ends_at', '<', now())
            ->chunkById(100, function ($installs) use (&$processedCount) {
                foreach ($installs as $i) {
                    $i->update([
                        'status'    => InstallStatus::PendingPayment,
                        'installed' => false,
                    ]);
                    $processedCount++;
                    
                    $this->info("Expired trial: Installation ID {$i->id} moved to PendingPayment status");
                }
            });

        $this->info('Trial expiration process completed.');
        $this->info("Summary: {$processedCount} trial installations expired.");
        
        return self::SUCCESS;
    }
}
