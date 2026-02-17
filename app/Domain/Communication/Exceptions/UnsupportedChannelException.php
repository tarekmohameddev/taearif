<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class UnsupportedChannelException extends RuntimeException
{
    public function __construct(string $channel = '')
    {
        parent::__construct('Only whatsapp channel is supported.' . ($channel ? " Got: {$channel}" : ''));
    }
}
