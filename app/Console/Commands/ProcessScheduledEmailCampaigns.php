<?php

namespace App\Console\Commands;

use App\Jobs\DispatchEmailCampaignJob;
use App\Models\EmailCampaign;
use Illuminate\Console\Command;

class ProcessScheduledEmailCampaigns extends Command
{
    protected $signature = 'email:process-scheduled-campaigns';
    protected $description = 'Dispatch due scheduled email campaigns';

    public function handle(): int
    {
        EmailCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('dispatch_reference')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($campaigns): void {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => 'in_progress']);
                    DispatchEmailCampaignJob::dispatch((int) $campaign->id)
                        ->onQueue((string) config('communication.email.queue', 'communication'));
                }
            });

        return self::SUCCESS;
    }
}
