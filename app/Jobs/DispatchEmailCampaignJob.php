<?php

namespace App\Jobs;

use App\Domain\Communication\Email\Contracts\EmailDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue((string) config('communication.email.queue', 'communication'));
    }

    public function handle(EmailDispatcher $dispatcher): void
    {
        $dispatcher->dispatchCampaign($this->campaignId);
    }
}
