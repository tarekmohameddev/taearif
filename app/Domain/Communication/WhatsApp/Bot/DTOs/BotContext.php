<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\DTOs;

use App\Models\Message;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;

/**
 * Assembled context passed to the generation service for a single bot turn.
 */
final class BotContext
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $conversationId,
        public readonly int $waNumberId,
        public readonly string $customerPhone,
        public readonly WaAiConfig $config,
        public readonly ?WaConversationAiState $aiState,
        /** @var Message[] Last N verbatim messages after summary watermark */
        public readonly array $recentMessages,
        /** @var array<string, mixed>|null */
        public readonly ?array $customerProfile,
        /** @var array{content: string, source: string, score: float}[] Retrieved KB chunks */
        public readonly array $kbChunks,
        /** @var array<string, mixed>|null Tool result from property search */
        public readonly ?array $propertySearchResult,
        public readonly string $standaloneQuery,  // rewritten query from pass 1
        public readonly string $intent,           // general|property_search|pricing|viewing|complaint|off_topic
        public readonly string $difficulty,       // easy|medium|hard
        public readonly string $inboundContent,   // original message text
    ) {}
}
