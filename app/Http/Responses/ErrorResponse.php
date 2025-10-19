<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Error Response Builder
 *
 * Provides clean, fluent API for building error responses
 *
 * Usage:
 * return ErrorResponse::make('Something went wrong')
 *     ->withCode('RENTAL_DELETE_FAILED')
 *     ->withDetails(['rental_id' => 58])
 *     ->send();
 */
class ErrorResponse
{
    protected string $message;
    protected ?string $code = null;
    protected int $statusCode = 400;
    protected array $details = [];
    protected ?array $errors = null;

    /**
     * Create new error response
     */
    public function __construct(string $message, int $statusCode = 400)
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    /**
     * Static constructor for fluent API
     */
    public static function make(string $message, int $statusCode = 400): self
    {
        return new self($message, $statusCode);
    }

    /**
     * Set error code
     */
    public function withCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Set error details
     */
    public function withDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Set validation errors
     */
    public function withErrors(array $errors): self
    {
        $this->errors = $errors;
        $this->statusCode = 422;
        return $this;
    }

    /**
     * Build and send the response
     */
    public function send(): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $this->message,
        ];

        if ($this->code) {
            $response['code'] = $this->code;
        }

        if (!empty($this->details)) {
            $response['details'] = $this->details;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        $response['timestamp'] = now()->toIso8601String();

        return response()->json($response, $this->statusCode);
    }

    /**
     * Quick error responses
     */
    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return self::make("{$resource} not found", 404)
            ->withCode(strtoupper($resource) . '_NOT_FOUND')
            ->send();
    }

    public static function unauthorized(string $message = 'Authentication required'): JsonResponse
    {
        return self::make($message, 401)
            ->withCode('UNAUTHORIZED')
            ->send();
    }

    public static function forbidden(string $message = 'You do not have permission'): JsonResponse
    {
        return self::make($message, 403)
            ->withCode('FORBIDDEN')
            ->send();
    }

    public static function validation(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::make($message, 422)
            ->withCode('VALIDATION_FAILED')
            ->withErrors($errors)
            ->send();
    }

    public static function serverError(string $message = 'An unexpected error occurred'): JsonResponse
    {
        return self::make($message, 500)
            ->withCode('SERVER_ERROR')
            ->send();
    }
}

