<?php

namespace App\Traits;

use App\Exceptions\Api\ApiException;
use App\Exceptions\PaymentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trait HandlesApiExceptions
 *
 * Provides centralized exception handling for API controllers.
 * This eliminates the need for duplicate try-catch blocks in every method.
 *
 * Usage:
 * ```php
 * class YourController extends BaseApiController
 * {
 *     use HandlesApiExceptions;
 *
 *     public function index(Request $request)
 *     {
 *         return $this->executeWithExceptionHandling(function () use ($request) {
 *             $data = $this->service->getData($this->getUserId());
 *             return $this->success($data);
 *         }, 'retrieve data');
 *     }
 * }
 * ```
 */
trait HandlesApiExceptions
{
    /**
     * Handle exceptions and return appropriate response
     *
     * Automatically handles:
     * - ApiException: Custom API exceptions with render() method
     * - ValidationException: Laravel validation failures
     * - ModelNotFoundException: Eloquent model not found
     * - PaymentException: Custom payment-related exceptions
     * - Generic Exceptions: Any other throwable
     *
     * @param Throwable $e The exception to handle
     * @param string $action Description of the action being performed (for logging)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Throwable $e, string $action = 'perform action')
    {
        // Handle custom API exceptions
        if ($e instanceof ApiException) {
            return $e->render();
        }

        // Handle custom payment exceptions
        if ($e instanceof PaymentException) {
            Log::warning("Payment validation failed during {$action}", [
                'user_id' => auth()->id(),
                'error_code' => $e->getErrorCode(),
                'error_message' => $e->getMessage(),
                'error_data' => method_exists($e, 'getErrorData') ? $e->getErrorData() : null,
            ]);
            return $e->render(request());
        }

        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return $this->validationError($e->errors(), 'Validation failed');
        }

        // Handle not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return $this->notFound('Resource not found');
        }

        // Log unexpected errors
        Log::error("Failed to {$action}", [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        // Return generic error with conditional message exposure
        $message = config('app.debug')
            ? $e->getMessage()
            : "An unexpected error occurred while trying to {$action}";

        return $this->serverError($message);
    }

    /**
     * Execute action with automatic exception handling
     *
     * This is the main method you should use in controllers.
     * It wraps your business logic and handles all exceptions automatically.
     *
     * @param callable $action The action to execute (typically a closure)
     * @param string $actionName Human-readable description of the action
     * @return \Illuminate\Http\JsonResponse
     *
     * @example
     * ```php
     * return $this->executeWithExceptionHandling(function () use ($request, $id) {
     *     $rental = $this->rentalService->create($this->getUserId(), $request->validated());
     *     return $this->created(RentalResource::make($rental), 'Rental created successfully');
     * }, 'create rental');
     * ```
     */
    protected function executeWithExceptionHandling(
        callable $action,
        string $actionName = 'perform action'
    ) {
        try {
            return $action();
        } catch (Throwable $e) {
            return $this->handleApiException($e, $actionName);
        }
    }

    /**
     * Execute action with custom exception handlers
     *
     * Use this when you need specific handling for certain exception types
     * before falling back to the default handler.
     *
     * @param callable $action The action to execute
     * @param array $customHandlers Map of exception class => handler callable
     * @param string $actionName Human-readable description of the action
     * @return \Illuminate\Http\JsonResponse
     *
     * @example
     * ```php
     * return $this->executeWithCustomHandlers(
     *     fn() => $this->service->doSomething(),
     *     [
     *         CustomException::class => fn($e) => $this->error($e->getMessage(), 400),
     *         AnotherException::class => fn($e) => $this->forbidden('Access denied'),
     *     ],
     *     'perform custom action'
     * );
     * ```
     */
    protected function executeWithCustomHandlers(
        callable $action,
        array $customHandlers,
        string $actionName = 'perform action'
    ) {
        try {
            return $action();
        } catch (Throwable $e) {
            // Check if there's a custom handler for this exception type
            foreach ($customHandlers as $exceptionClass => $handler) {
                if ($e instanceof $exceptionClass) {
                    return $handler($e);
                }
            }

            // Fall back to default handler
            return $this->handleApiException($e, $actionName);
        }
    }

    /**
     * Log an action for debugging/auditing
     *
     * @param string $action The action being performed
     * @param array $context Additional context data
     * @param string $level Log level (info, debug, warning, error)
     * @return void
     */
    protected function logAction(string $action, array $context = [], string $level = 'info'): void
    {
        $contextData = array_merge([
            'user_id' => auth()->id(),
            'action' => $action,
            'timestamp' => now()->toISOString(),
        ], $context);

        Log::{$level}($action, $contextData);
    }

    /**
     * Log successful action
     *
     * @param string $action Action description
     * @param array $context Additional context
     * @return void
     */
    protected function logSuccess(string $action, array $context = []): void
    {
        $this->logAction("Successfully {$action}", $context, 'info');
    }

    /**
     * Log failed action
     *
     * @param string $action Action description
     * @param Throwable $exception The exception that occurred
     * @param array $context Additional context
     * @return void
     */
    protected function logFailure(string $action, Throwable $exception, array $context = []): void
    {
        $this->logAction("Failed to {$action}", array_merge([
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $context), 'error');
    }
}

