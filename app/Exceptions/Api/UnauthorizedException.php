<?php

namespace App\Exceptions\Api;

/**
 * Unauthorized Exception
 *
 * Thrown when user is not authenticated
 * HTTP Status: 401 Unauthorized
 */
class UnauthorizedException extends ApiException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';
    protected ?string $safeMessage = 'Authentication required';

    /**
     * Create unauthorized exception
     */
    public static function make(?string $message = null): self
    {
        return new self(
            message: $message ?? 'User is not authenticated',
            code: 'UNAUTHORIZED',
            statusCode: 401,
            safeMessage: 'Authentication required'
        );
    }

    /**
     * Invalid token
     */
    public static function invalidToken(): self
    {
        return new self(
            message: 'Invalid or expired authentication token',
            code: 'INVALID_TOKEN',
            statusCode: 401,
            safeMessage: 'Invalid or expired token'
        );
    }
}

