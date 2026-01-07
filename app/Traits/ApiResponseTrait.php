<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API Response Trait
 *
 * Provides consistent response formatting across all API endpoints
 */
trait ApiResponseTrait
{
    /**
     * Return a successful JSON response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        $response['timestamp'] = now()->toIso8601String();

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param string|null $errorCode
     * @param int $statusCode
     * @param array $details
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        ?string $errorCode = null,
        int $statusCode = 422,
        array $details = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['code'] = $errorCode;
        }

        if (!empty($details)) {
            $response['details'] = $details;
        }

        $response['timestamp'] = now()->toIso8601String();

        if (config('app.debug')) {
            $response['request_id'] = 'req_' . \Illuminate\Support\Str::random(16);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     *
     * @param array $errors
     * @param string|null $message
     * @return JsonResponse
     */
    protected function validationError(
        array $errors,
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Validation failed',
            'VALIDATION_ERROR',
            422,
            ['errors' => $errors]
        );
    }

    /**
     * Return a not found response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Resource not found',
            'NOT_FOUND',
            404
        );
    }

    /**
     * Return an unauthorized response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Unauthorized',
            'UNAUTHORIZED',
            401
        );
    }

    /**
     * Return a forbidden response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function forbidden(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Forbidden',
            'FORBIDDEN',
            403
        );
    }
}

