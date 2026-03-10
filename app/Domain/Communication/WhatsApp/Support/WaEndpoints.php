<?php

namespace App\Domain\Communication\WhatsApp\Support;

final class WaEndpoints
{
    public const SEND_CAMPAIGN = 'POST:/v1/whatsapp/campaigns/{id}/send';
    public const PAUSE_CAMPAIGN = 'POST:/v1/whatsapp/campaigns/{id}/pause';
    public const RESUME_CAMPAIGN = 'POST:/v1/whatsapp/campaigns/{id}/resume';
}
