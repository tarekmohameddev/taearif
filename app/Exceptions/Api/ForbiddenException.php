<?php

namespace App\Exceptions\Api;

/**
 * Forbidden Exception
 *
 * Thrown when user doesn't have permission to perform action
 * HTTP Status: 403 Forbidden
 */
class ForbiddenException extends ApiException
{
    protected int $statusCode = 403;
    protected string $errorCode = 'FORBIDDEN';
    protected ?string $safeMessage = 'You do not have permission to perform this action';

    /**
     * Create forbidden exception
     */
    public static function make(?string $message = null, ?string $permission = null): self
    {
        $details = [];
        if ($permission) {
            $details['required_permission'] = $permission;
        }

        return new self(
            message: $message ?? 'User does not have required permission',
            code: 'FORBIDDEN',
            statusCode: 403,
            details: $details,
            safeMessage: 'You do not have permission to perform this action'
        );
    }

    /**
     * Resource not owned by user
     */
    public static function notOwned(string $resourceType): self
    {
        return new self(
            message: "User does not own this {$resourceType}",
            code: strtoupper($resourceType) . '_NOT_OWNED',
            statusCode: 403,
            safeMessage: "You do not have access to this {$resourceType}"
        );
    }
}

