<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class ConversationNotFoundException extends RuntimeException
{
    public function __construct(int $conversationId, int $userId)
    {
        parent::__construct("Conversation {$conversationId} not found or not owned by user {$userId}.");
    }
}
