<?php

namespace App\Exceptions;

use Exception;

/**
 * Admin Authentication Exception
 *
 * Thrown when admin authentication fails
 */
class AdminAuthException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param string $errorCode
     */
    public function __construct(
        string $message = 'Authentication failed',
        string $errorCode = 'AUTH_001'
    ) {
        $this->errorCode = $errorCode;
        $this->statusCode = 401;
        parent::__construct($message, $this->statusCode);
    }

    /**
     * Get the application-specific error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code for the exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Render the exception as an HTTP response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render()
    {
        return response()->json([
            'status' => 'error',
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ]
        ], 401);
    }
}

