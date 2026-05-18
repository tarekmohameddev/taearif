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

    public function sendMessage(SendMessageDto $dto, string $idempotencyKey): Message;
}
