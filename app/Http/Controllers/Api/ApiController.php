<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasApiPaginationResponse;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    use HasApiPaginationResponse;

    /**
     * Standard success response with your envelope.
     * $payload will be merged under "data".
     */
    protected function success(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $payload,
        ], $status);
    }

    /**
     * Standard error response.
     */
    protected function error(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors ?: null,
        ], $status);
    }
}
