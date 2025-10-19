<?php

namespace App\Exceptions\Api;

/**
 * Validation Exception
 *
 * Thrown when request data fails validation
 * HTTP Status: 422 Unprocessable Entity
 */
class ValidationException extends ApiException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_FAILED';

    /**
     * Validation errors from validator
     */
    protected array $errors = [];

    /**
     * Create validation exception from Laravel validator
     *
     * @param array $errors Validation errors
     * @param string|null $message Custom message
     */
    public static function fromValidator(array $errors, ?string $message = null): self
    {
        $exception = new self(
            message: $message ?? 'Validation failed',
            code: 'VALIDATION_FAILED',
            statusCode: 422
        );

        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Render validation exception
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => $this->getErrorCode(),
            'message' => $this->getSafeMessage(),
            'errors' => $this->errors,
            'timestamp' => now()->toIso8601String(),
        ], $this->getStatusCode());
    }
}

