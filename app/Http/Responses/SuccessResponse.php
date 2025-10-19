<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Success Response Builder
 *
 * Provides clean, fluent API for building success responses
 *
 * Usage:
 * return SuccessResponse::make($data)
 *     ->withMessage('Rental created successfully')
 *     ->withCode(201)
 *     ->send();
 */
class SuccessResponse
{
    protected $data;
    protected ?string $message = null;
    protected int $statusCode = 200;
    protected array $meta = [];

    /**
     * Create new success response
     */
    public function __construct($data = null, int $statusCode = 200)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    /**
     * Static constructor for fluent API
     */
    public static function make($data = null, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    /**
     * Set success message
     */
    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set status code
     */
    public function withCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Set meta data
     */
    public function withMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Build and send the response
     */
    public function send(): JsonResponse
    {
        $response = ['status' => true];

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Quick success responses
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::make($data, 201)
            ->withMessage($message)
            ->send();
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    public static function accepted($message = 'Request accepted for processing'): JsonResponse
    {
        return self::make(null, 202)
            ->withMessage($message)
            ->send();
    }
}

