<?php

namespace App\Jobs;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\WhatsApp\Contracts\WaDispatcher;
use App\Models\WaCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DispatchWaCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue((string) config('communication.whatsapp.queue'));
    }

    public function handle(WaDispatcher $dispatcher): void
    {
        $dispatcher->dispatchCampaign($this->campaignId);
    }

    public function failed(Throwable $e): void
    {
        $campaign = WaCampaign::query()->find($this->campaignId);
        if (! $campaign || $campaign->status !== 'in_progress') {
            return;
        }

        $reserved = (int) $campaign->reserved_credits;
        if ($reserved > 0) {
            app(CreditService::class)->releaseReserved(
                (int) $campaign->user_id,
                $reserved,
                'wa_campaign_job_failed',
                (string) $campaign->id
            );
        }

        $campaign->update([
            'status' => 'failed',
            'reserved_credits' => 0,
        ]);
    }
}
