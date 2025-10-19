<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

/**
 * Base API Controller
 *
 * Provides reusable error handling methods for all API controllers
 *
 * Usage:
 * class YourController extends BaseApiController
 * {
 *     public function yourMethod()
 *     {
 *         try {
 *             // Your logic
 *             return $this->successResponse($data);
 *         } catch (\Exception $e) {
 *             return $this->handleException($e);
 *         }
 *     }
 * }
 */
class BaseApiController extends Controller
{
    /**
     * Handle exceptions and return appropriate JSON response
     *
     * @param Throwable $exception
     * @param string|null $defaultMessage
     * @param int|null $defaultStatusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(
        Throwable $exception,
        ?string $defaultMessage = null,
        ?int $defaultStatusCode = null
    ) {
        // Validation errors
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $exception->errors()
            ], 422);
        }

        // Model/Resource not found
        if ($exception instanceof ModelNotFoundException) {
            $message = $defaultMessage ?? 'Resource not found';
            return response()->json([
                'status' => 'error',
                'message' => $message
            ], 404);
        }

        // Authentication errors
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Authorization errors
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Invalid argument (business logic errors)
        if ($exception instanceof \InvalidArgumentException) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], 400);
        }

        // Generic exception - show actual error message
        $message = $exception->getMessage() ?: ($defaultMessage ?? 'An unexpected error occurred');
        $statusCode = $defaultStatusCode ?? 500;

        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }

    /**
     * Return success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200
    ) {
        $response = ['status' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        ?array $errors = null
    ) {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return created response (201)
     *
     * @param mixed $data
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createdResponse($data = null, ?string $message = null)
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return no content response (204)
     *
     * @return \Illuminate\Http\Response
     */
    protected function noContentResponse()
    {
        return response()->noContent();
    }

    /**
     * Return not found error (404)
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(?string $message = null)
    {
        return $this->errorResponse(
            $message ?? 'Resource not found',
            404
        );
    }

    /**
     * Return validation error (422)
     *
     * @param array $errors
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(
        array $errors,
        ?string $message = null
    ) {
        return $this->errorResponse(
            $message ?? 'Validation failed',
            422,
            $errors
        );
    }

    /**
     * Return unauthorized error (401)
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(?string $message = null)
    {
        return $this->errorResponse(
            $message ?? 'Unauthenticated',
            401
        );
    }

    /**
     * Return forbidden error (403)
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(?string $message = null)
    {
        return $this->errorResponse(
            $message ?? 'Forbidden',
            403
        );
    }
}
