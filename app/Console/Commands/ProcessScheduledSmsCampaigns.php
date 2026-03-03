<?php

namespace App\Console\Commands;

use App\Jobs\DispatchSmsCampaignJob;
use App\Models\SmsCampaign;
use Illuminate\Console\Command;

class ProcessScheduledSmsCampaigns extends Command
{
    protected $signature = 'sms:process-scheduled-campaigns';
    protected $description = 'Dispatch due scheduled SMS campaigns';

    public function handle(): int
    {
        SmsCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('dispatch_reference')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($campaigns): void {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => 'in_progress']);
                    DispatchSmsCampaignJob::dispatch((int) $campaign->id)
                        ->onQueue((string) config('communication.sms.queue', 'communication'));
                }
            });

        return self::SUCCESS;
    }
}

