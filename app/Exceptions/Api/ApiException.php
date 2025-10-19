<?php

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Base API Exception
 *
 * All API exceptions should extend this class for consistent error handling
 *
 * Features:
 * - Error codes for programmatic handling
 * - Safe error messages (hides internals in production)
 * - Structured error details
 * - Self-rendering to JSON
 * - Security-first design
 */
abstract class ApiException extends Exception
{
    /**
     * HTTP status code
     */
    protected int $statusCode = 500;

    /**
     * Error code for programmatic handling
     */
    protected string $errorCode = 'API_ERROR';

    /**
     * Safe message to show in production (when debug=false)
     */
    protected ?string $safeMessage = null;

    /**
     * Additional error details/context
     */
    protected array $details = [];

    /**
     * Request ID for tracing
     */
    protected ?string $requestId = null;

    /**
     * Create a new API exception
     *
     * @param string $message Developer message (shown only in debug mode)
     * @param string|null $code Error code for frontend
     * @param int|null $statusCode HTTP status code
     * @param array $details Additional context
     * @param string|null $safeMessage Safe message for production
     */
    public function __construct(
        string $message = '',
        ?string $code = null,
        ?int $statusCode = null,
        array $details = [],
        ?string $safeMessage = null
    ) {
        parent::__construct($message);

        if ($code !== null) {
            $this->errorCode = $code;
        }

        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->details = $details;
        $this->safeMessage = $safeMessage ?? $message;
        $this->requestId = $this->generateRequestId();
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get safe error message (production-ready)
     */
    public function getSafeMessage(): string
    {
        if (config('app.debug')) {
            return $this->getMessage();
        }

        return $this->safeMessage ?? 'An error occurred';
    }

    /**
     * Get error details (filtered for production)
     */
    public function getDetails(): array
    {
        if (config('app.debug')) {
            return $this->details;
        }

        // Filter sensitive data in production
        return $this->filterSensitiveData($this->details);
    }

    /**
     * Filter sensitive data from error details
     */
    protected function filterSensitiveData(array $details): array
    {
        $sensitiveKeys = [
            'password', 'token', 'secret', 'api_key', 'access_token',
            'refresh_token', 'ssn', 'credit_card', 'cvv'
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($details[$key])) {
                $details[$key] = '***';
            }
        }

        return $details;
    }

    /**
     * Generate unique request ID for error tracking
     */
    protected function generateRequestId(): string
    {
        return 'err_' . Str::random(16);
    }

    /**
     * Render the exception as JSON response
     */
    public function render(): JsonResponse
    {
        $response = [
            'status' => 'error',
            'code' => $this->getErrorCode(),
            'message' => $this->getSafeMessage(),
        ];

        // Add details if present
        $details = $this->getDetails();
        if (!empty($details)) {
            $response['details'] = $details;
        }

        // Add timestamp
        $response['timestamp'] = now()->toIso8601String();

        // Add request ID for debugging
        if (config('app.debug')) {
            $response['request_id'] = $this->requestId;
            $response['exception_class'] = class_basename($this);
        }

        // Log the error
        $this->logError();

        return response()->json($response, $this->getStatusCode());
    }

    /**
     * Log the error with context
     */
    protected function logError(): void
    {
        \Log::error($this->getErrorCode() . ': ' . $this->getMessage(), [
            'exception' => get_class($this),
            'code' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'message' => $this->getMessage(),
            'details' => $this->details,
            'request_id' => $this->requestId,
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Report the exception (for monitoring/alerting)
     */
    public function report(): bool
    {
        // Can be extended to send to Sentry, Rollbar, etc.
        return true;
    }
}

