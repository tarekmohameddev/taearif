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
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ApiInstallation::query()
            ->where('status', InstallStatus::Trialing)
            ->where('trial_ends_at', '<', now())
            ->chunkById(100, function ($installs) {
                foreach ($installs as $i) {
                    $i->update([
                        'status'    => InstallStatus::PendingPayment,
                        'installed' => false,
                    ]);
                }
            });
    }
}
