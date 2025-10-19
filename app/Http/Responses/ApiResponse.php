<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use App\Exceptions\Api\ApiException;

/**
 * API Response Helper
 *
 * Static methods for quick response creation
 */
class ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return SuccessResponse::make($data, $code)
            ->withMessage($message ?? '')
            ->send();
    }

    /**
     * Error response
     */
    public static function error(
        string $message,
        ?string $errorCode = null,
        int $statusCode = 400,
        array $details = []
    ): JsonResponse {
        $response = ErrorResponse::make($message, $statusCode);

        if ($errorCode) {
            $response->withCode($errorCode);
        }

        if (!empty($details)) {
            $response->withDetails($details);
        }

        return $response->send();
    }

    /**
     * Handle exception and return appropriate response
     */
    public static function fromException(\Throwable $exception): JsonResponse
    {
        // If it's our custom ApiException, let it render itself
        if ($exception instanceof ApiException) {
            return $exception->render();
        }

        // Handle Laravel's validation exception
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return ErrorResponse::validation($exception->errors());
        }

        // Handle model not found
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ErrorResponse::notFound('Resource');
        }

        // Handle authentication exception
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return ErrorResponse::unauthorized();
        }

        // Generic exception - hide details in production
        $message = config('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred';

        return ErrorResponse::serverError($message);
    }

    /**
     * Quick responses
     */
    public static function created($data, string $message = 'Created successfully'): JsonResponse
    {
        return SuccessResponse::created($data, $message);
    }

    public static function noContent(): JsonResponse
    {
        return SuccessResponse::noContent();
    }

    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return ErrorResponse::notFound($resource);
    }

    public static function validation(array $errors): JsonResponse
    {
        return ErrorResponse::validation($errors);
    }

    public static function unauthorized(string $message = 'Authentication required'): JsonResponse
    {
        return ErrorResponse::unauthorized($message);
    }

    public static function forbidden(string $message = 'Permission denied'): JsonResponse
    {
        return ErrorResponse::forbidden($message);
    }
}

