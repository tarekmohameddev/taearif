<?php

namespace App\Jobs;

use App\Domain\Communication\WhatsApp\Contracts\WaDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWaCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue((string) config('communication.whatsapp.queue', 'communication'));
    }

    public function handle(WaDispatcher $dispatcher): void
    {
        $dispatcher->dispatchCampaign($this->campaignId);
    }
}
