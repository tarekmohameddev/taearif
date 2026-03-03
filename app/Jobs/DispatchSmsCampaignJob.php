<?php

namespace App\Jobs;

use App\Domain\Communication\Sms\Contracts\SmsDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue((string) config('communication.sms.queue', 'communication'));
    }

    public function handle(SmsDispatcher $dispatcher): void
    {
        $dispatcher->dispatchCampaign($this->campaignId);
    }
}

