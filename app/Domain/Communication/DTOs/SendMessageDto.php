<?php

namespace App\Domain\Communication\DTOs;

final class SendMessageDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $conversationId,
        public readonly string $content,
        public readonly string $channel = 'whatsapp',
        public readonly ?int $waNumberId = null,
        public readonly ?string $endpointSignature = null,
        public readonly ?int $templateId = null,
        public readonly ?array $variables = null,
        /** @var array<string, mixed>|null Extra keys merged into the outbound message meta (e.g. source=ai). */
        public readonly ?array $extraMeta = null,
    ) {}
}
