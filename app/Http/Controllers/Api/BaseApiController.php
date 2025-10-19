<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use App\Http\Responses\SuccessResponse;
use App\Exceptions\Api\ApiException;
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
 *             return $this->success($data);
 *         } catch (ApiException $e) {
 *             return $e->render();
 *         }
 *     }
 * }
 */
class BaseApiController extends Controller
{
    /**
     * Handle exceptions and return appropriate JSON response
     *
     * NEW: Uses ApiResponse helper for cleaner code
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
        // Use the new ApiResponse helper
        return ApiResponse::fromException($exception);
    }

    /**
     * Return success response
     *
     * NEW: Uses SuccessResponse builder
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
        $response = SuccessResponse::make($data, $statusCode);

        if ($message !== null) {
            $response->withMessage($message);
        }

        return $response->send();
    }

    /**
     * Alias for successResponse
     */
    protected function success($data = null, ?string $message = null, int $statusCode = 200)
    {
        return $this->successResponse($data, $message, $statusCode);
    }

    /**
     * Return error response
     *
     * NEW: Uses ErrorResponse builder
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
        $response = ErrorResponse::make($message, $statusCode);

        if ($errors !== null) {
            $response->withErrors($errors);
        }

        return $response->send();
    }

    /**
     * Alias for errorResponse
     */
    protected function error(string $message, int $statusCode = 400, ?array $errors = null)
    {
        return $this->errorResponse($message, $statusCode, $errors);
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
        return SuccessResponse::created($data, $message ?? 'Resource created successfully');
    }

    /**
     * Alias for createdResponse
     */
    protected function created($data = null, ?string $message = null)
    {
        return $this->createdResponse($data, $message);
    }

    /**
     * Return ok response with data (alias for success)
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ok($data = null, ?string $message = null, int $statusCode = 200)
    {
        return $this->successResponse($data, $message, $statusCode);
    }

    /**
     * Return fail response (alias for error)
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function fail(string $message, int $statusCode = 400, ?array $errors = null)
    {
        return $this->errorResponse($message, $statusCode, $errors);
    }

    /**
     * Return no content response (204)
     *
     * @return \Illuminate\Http\Response
     */
    protected function noContentResponse()
    {
        return SuccessResponse::noContent();
    }

    /**
     * Alias for noContentResponse
     */
    protected function noContent()
    {
        return $this->noContentResponse();
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
