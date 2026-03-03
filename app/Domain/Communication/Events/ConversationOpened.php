<?php

namespace App\Domain\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationOpened
{
    use Dispatchable;
    use SerializesModels;

    /** @var int */
    public $conversationId;

    /** @var int */
    public $firstMessageId;

    /** @var int */
    public $userId;

    /** @var string */
    public $channel;

    /** @var string */
    public $occurredAt;

    public function __construct(
        int $conversationId,
        int $firstMessageId,
        int $userId,
        string $channel,
        string $occurredAt
    ) {
        $this->conversationId = $conversationId;
        $this->firstMessageId = $firstMessageId;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->occurredAt = $occurredAt;
    }
}
