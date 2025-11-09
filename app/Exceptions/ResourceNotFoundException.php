<?php

namespace App\Exceptions;

use Exception;

/**
 * Resource Not Found Exception
 *
 * Thrown when a requested resource cannot be found
 */
class ResourceNotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = 'Resource not found', int $code = 404)
    {
        parent::__construct($message, $code);
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
            'code' => 'NOT_FOUND',
            'message' => $this->getMessage(),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ]
        ], 404);
    }
}

