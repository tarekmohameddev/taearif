<?php

namespace App\Exceptions\Api;

/**
 * Business Logic Exception
 *
 * Thrown when business rules are violated
 * HTTP Status: 400 Bad Request
 */
class BusinessLogicException extends ApiException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'BUSINESS_LOGIC_ERROR';

    /**
     * Create business logic exception
     */
    public static function make(
        string $message,
        ?string $code = null,
        array $details = []
    ): self {
        return new self(
            message: $message,
            code: $code ?? 'BUSINESS_LOGIC_ERROR',
            statusCode: 400,
            details: $details,
            safeMessage: $message // Business logic errors are usually safe to show
        );
    }

    /**
     * Invalid state transition
     */
    public static function invalidStateTransition(
        string $entity,
        string $from,
        string $to
    ): self {
        return new self(
            message: "Cannot transition {$entity} from '{$from}' to '{$to}'",
            code: 'INVALID_STATE_TRANSITION',
            statusCode: 400,
            details: [
                'entity' => $entity,
                'current_state' => $from,
                'requested_state' => $to
            ]
        );
    }

    /**
     * Operation not allowed
     */
    public static function operationNotAllowed(string $operation, string $reason): self
    {
        return new self(
            message: "Operation '{$operation}' not allowed: {$reason}",
            code: 'OPERATION_NOT_ALLOWED',
            statusCode: 400,
            details: ['operation' => $operation, 'reason' => $reason],
            safeMessage: $reason // Usually safe
        );
    }
}

