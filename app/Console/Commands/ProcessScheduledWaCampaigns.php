<?php

namespace App\Console\Commands;

use App\Jobs\DispatchWaCampaignJob;
use App\Models\WaCampaign;
use Illuminate\Console\Command;

class ProcessScheduledWaCampaigns extends Command
{
    protected $signature = 'wa:process-scheduled-campaigns';
    protected $description = 'Dispatch due scheduled WhatsApp campaigns';

    public function handle(): int
    {
        WaCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('dispatch_reference')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($campaigns): void {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => 'in_progress']);
                    DispatchWaCampaignJob::dispatch((int) $campaign->id)
                        ->onQueue((string) config('communication.whatsapp.queue', 'communication'));
                }
            });

        return self::SUCCESS;
    }
}
