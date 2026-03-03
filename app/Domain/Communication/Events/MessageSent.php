<?php

namespace App\Domain\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable;
    use SerializesModels;

    /** @var int */
    public $messageId;

    /** @var int */
    public $conversationId;

    /** @var int */
    public $userId;

    /** @var string */
    public $channel;

    /** @var string */
    public $direction;

    /** @var string */
    public $content;

    /** @var array */
    public $meta;

    /** @var string */
    public $occurredAt;

    public function __construct(
        int $messageId,
        int $conversationId,
        int $userId,
        string $channel,
        string $direction,
        string $content,
        array $meta,
        string $occurredAt
    ) {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->direction = $direction;
        $this->content = $content;
        $this->meta = $meta;
        $this->occurredAt = $occurredAt;
    }
}
