<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Base Controller for Admin Dashboard API
 *
 * Provides common functionality for all API controllers
 * Following Clean Architecture principles
 */
abstract class BaseController extends Controller
{
    /**
     * Return success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $response['meta'] = [
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param string $code
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int|string $code = 'ERROR',
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $response['meta'] = [
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return no content response
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 'NOT_FOUND', 404);
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 'AUTH_001', 401);
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 'AUTH_003', 403);
    }
}

