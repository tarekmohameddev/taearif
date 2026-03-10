<?php

namespace App\Domain\Communication\WhatsApp\Contracts;

interface WaDispatcher
{
    public function dispatchCampaign(int $campaignId): void;

    public function dispatchSingleLog(int $logId): void;
}
