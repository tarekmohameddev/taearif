<?php

namespace App\Domain\Communication\Email\Contracts;

interface EmailDispatcher
{
    public function dispatchCampaign(int $campaignId): void;

    public function dispatchSingleLog(int $logId): void;
}

