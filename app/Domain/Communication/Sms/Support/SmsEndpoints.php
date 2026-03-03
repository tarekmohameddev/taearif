<?php

namespace App\Domain\Communication\Sms\Support;

final class SmsEndpoints
{
    public const SEND_CAMPAIGN = 'POST:/v1/sms/campaigns/{id}/send';
    public const PAUSE_CAMPAIGN = 'POST:/v1/sms/campaigns/{id}/pause';
    public const RESUME_CAMPAIGN = 'POST:/v1/sms/campaigns/{id}/resume';
    public const SEND_SINGLE = 'POST:/v1/sms/messages/send';
}

