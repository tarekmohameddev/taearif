<?php

namespace App\Exceptions;

use Exception;

/**
 * Business Logic Exception
 *
 * Thrown when business rules are violated
 */
class BusinessLogicException extends Exception
{
    protected string $errorCode;

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param string $errorCode
     * @param int $httpCode
     */
    public function __construct(
        string $message = 'Business logic error',
        string $errorCode = 'BIZ_001',
        int $httpCode = 400
    ) {
        $this->errorCode = $errorCode;
        parent::__construct($message, $httpCode);
    }

    /**
     * Get the error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
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
        ], $this->getCode());
    }
}

