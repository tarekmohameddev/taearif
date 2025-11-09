<?php

namespace App\Exceptions;

use Exception;

/**
 * Impersonation Exception
 *
 * Custom exception for impersonation-related errors
 */
class ImpersonationException extends Exception
{
    /**
     * @var string
     */
    public string $errorCode;

    /**
     * Create a new impersonation exception instance.
     *
     * @param string $message
     * @param string $errorCode
     * @param int $httpCode
     */
    public function __construct(string $message, string $errorCode = 'IMPERSONATION_ERROR', int $httpCode = 400)
    {
        parent::__construct($message, $httpCode);
        $this->errorCode = $errorCode;
    }
}

