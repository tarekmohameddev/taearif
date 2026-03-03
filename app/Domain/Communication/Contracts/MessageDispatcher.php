<?php

namespace App\Domain\Communication\Contracts;

use App\Models\Message;

interface MessageDispatcher
{
    public function dispatch(Message $message): void;
}
