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
        $due = WaCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('dispatch_reference')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->limit(100)
            ->pluck('id');

        foreach ($due as $campaignId) {
            $updated = WaCampaign::query()
                ->where('id', $campaignId)
                ->where('status', 'scheduled')
                ->update(['status' => 'in_progress']);

            if ($updated === 1) {
                DispatchWaCampaignJob::dispatch((int) $campaignId)
                    ->onQueue((string) config('communication.whatsapp.queue', 'communication'));
            }
        }

        return self::SUCCESS;
    }
}
