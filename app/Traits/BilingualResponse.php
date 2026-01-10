<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Bilingual Response Trait
 * 
 * Provides helper methods for creating API responses with both English and Arabic messages
 */
trait BilingualResponse
{
    /**
     * Return a successful JSON response with bilingual messages
     *
     * @param mixed $data
     * @param string $message
     * @param string $messageAr
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        string $messageAr = 'نجح',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'message_ar' => $messageAr,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response with bilingual messages
     *
     * @param string $message
     * @param string $messageAr
     * @param string|null $errorCode
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        string $messageAr,
        ?string $errorCode = null,
        int $statusCode = 422,
        array $errors = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
            'message_ar' => $messageAr,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return validation error response with bilingual messages
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return JsonResponse
     */
    protected function validationErrorResponse($validator): JsonResponse
    {
        $errors = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] = $messages;
        }

        return $this->errorResponse(
            'Validation failed',
            'فشل التحقق من صحة البيانات',
            'VALIDATION_FAILED',
            422,
            $errors
        );
    }

    /**
     * Return not found error response with bilingual messages
     *
     * @param string $resource
     * @param string $resourceAr
     * @param string|null $errorCode
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $resource = 'Resource not found',
        string $resourceAr = 'المورد غير موجود',
        ?string $errorCode = 'NOT_FOUND'
    ): JsonResponse {
        return $this->errorResponse(
            $resource,
            $resourceAr,
            $errorCode,
            404
        );
    }

    /**
     * Return unauthorized error response with bilingual messages
     *
     * @param string $message
     * @param string $messageAr
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        string $message = 'Authentication required. Please login.',
        string $messageAr = 'يجب تسجيل الدخول للوصول إلى هذا المورد'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $messageAr,
            'AUTH_REQUIRED',
            401
        );
    }

    /**
     * Return forbidden error response with bilingual messages
     *
     * @param string $message
     * @param string $messageAr
     * @param string|null $errorCode
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        string $message = "You don't have permission to access this resource.",
        string $messageAr = 'ليس لديك الصلاحية للوصول إلى هذا المورد',
        ?string $errorCode = 'FORBIDDEN'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $messageAr,
            $errorCode,
            403
        );
    }
}
