<?php

namespace App\Domain\Communication\Email\Support;

final class EmailEndpoints
{
    public const SEND_CAMPAIGN = 'POST:/v1/email/campaigns/{id}/send';
    public const PAUSE_CAMPAIGN = 'POST:/v1/email/campaigns/{id}/pause';
    public const RESUME_CAMPAIGN = 'POST:/v1/email/campaigns/{id}/resume';
    public const SEND_SINGLE = 'POST:/v1/email/messages/send';
}

