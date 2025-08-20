<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function ok($data = [], string $message = 'OK'): JsonResponse
    {
        return response()->json(['status' => 'success', 'message' => $message, 'data' => $data]);
    }

    protected function fail(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message, 'errors' => $errors], $code);
    }
}
