<?php

namespace App\Domain\Communication\Contracts;

use App\Domain\Communication\DTOs\SendMessageDto;
use App\Models\Message;
use DateTimeInterface;

interface CommunicationService
{
    public function recordInboundMessage(
        int $userId,
        string $externalPartyIdentifier,
        string $content,
        string $channel = 'whatsapp',
        ?string $providerMessageId = null,
        array $meta = [],
        ?DateTimeInterface $createdAt = null,
        bool $incrementUnread = true,
    ): ?Message;

    /**
     * Record an outbound message from an echo webhook (message sent from WhatsApp Business App).
     * This creates a record of a message that was already sent and delivered via the mobile app.
     */
    public function recordOutboundFromEcho(
        int $userId,
        string $externalPartyIdentifier,
        string $content,
        string $channel = 'whatsapp',
        ?string $providerMessageId = null,
        array $meta = [],
        ?DateTimeInterface $createdAt = null,
    ): ?Message;

    public function sendMessage(SendMessageDto $dto, string $idempotencyKey): Message;
}
