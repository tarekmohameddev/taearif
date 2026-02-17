<?php

namespace App\Domain\Communication\Sms\Contracts;

interface SmsDispatcher
{
    public function dispatchCampaign(int $campaignId): void;

    public function dispatchSingleLog(int $logId): void;
}

