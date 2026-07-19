<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Exceptions;

use RuntimeException;

final class PipedriveNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'Pipedrive credentials are not configured.')
    {
        parent::__construct($message);
    }
}
