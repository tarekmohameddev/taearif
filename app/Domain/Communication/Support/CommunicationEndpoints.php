<?php

namespace App\Domain\Communication\Support;

final class CommunicationEndpoints
{
    public const SEND_MESSAGE = 'POST:/v1/messages/send';

    public const WHATSAPP_SEND_MESSAGE = 'POST:/v1/whatsapp/conversations/{id}/messages';
    public const WHATSAPP_SEND_TEMPLATE = 'POST:/v1/whatsapp/conversations/{id}/messages/template';
}
