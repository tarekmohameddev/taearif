<?php

namespace App\Exceptions\Api;

/**
 * Resource Not Found Exception
 *
 * Thrown when a requested resource doesn't exist
 * HTTP Status: 404 Not Found
 */
class ResourceNotFoundException extends ApiException
{
    protected int $statusCode = 404;
    protected string $errorCode = 'RESOURCE_NOT_FOUND';
    protected ?string $safeMessage = 'Resource not found';

    /**
     * Create exception for generic resource
     */
    public static function make(string $resourceType, $id): self
    {
        return new self(
            message: "{$resourceType} with ID {$id} not found",
            code: strtoupper($resourceType) . '_NOT_FOUND',
            statusCode: 404,
            details: [
                'resource_type' => $resourceType,
                'resource_id' => $id
            ],
            safeMessage: ucfirst($resourceType) . ' not found'
        );
    }

    /**
     * Create exception for rental
     */
    public static function rental($id): self
    {
        return self::make('rental', $id);
    }

    /**
     * Create exception for contract
     */
    public static function contract($id): self
    {
        return self::make('contract', $id);
    }

    /**
     * Create exception for property
     */
    public static function property($id): self
    {
        return self::make('property', $id);
    }

    /**
     * Create exception for customer
     */
    public static function customer($id): self
    {
        return self::make('customer', $id);
    }
}

