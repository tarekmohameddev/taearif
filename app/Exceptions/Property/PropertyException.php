<?php

namespace App\Exceptions\Property;

use App\Exceptions\Api\ApiException;

/**
 * Property Exception
 *
 * Domain-specific exceptions for property operations
 */
class PropertyException extends ApiException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'PROPERTY_ERROR';

    /**
     * Property not found
     */
    public static function notFound($id): self
    {
        return new self(
            message: "Property with ID {$id} not found",
            code: 'PROPERTY_NOT_FOUND',
            statusCode: 404,
            details: ['property_id' => $id],
            safeMessage: 'Property not found'
        );
    }

    /**
     * Property limit reached
     */
    public static function limitReached(int $limit, int $current): self
    {
        return new self(
            message: "Property listing limit reached ({$current}/{$limit})",
            code: 'PROPERTY_LIMIT_REACHED',
            statusCode: 403,
            details: [
                'limit' => $limit,
                'current_count' => $current
            ],
            safeMessage: "You have reached your property listing limit of {$limit}"
        );
    }

    /**
     * Property already rented
     */
    public static function alreadyRented($propertyId): self
    {
        return new self(
            message: "Property ID {$propertyId} is already rented",
            code: 'PROPERTY_ALREADY_RENTED',
            statusCode: 400,
            details: ['property_id' => $propertyId],
            safeMessage: 'This property is already rented'
        );
    }

    /**
     * Property not owned by user
     */
    public static function notOwned($propertyId, $userId): self
    {
        return new self(
            message: "Property ID {$propertyId} does not belong to user {$userId}",
            code: 'PROPERTY_NOT_OWNED',
            statusCode: 403,
            details: ['property_id' => $propertyId],
            safeMessage: 'You do not have access to this property'
        );
    }

    /**
     * Invalid property type
     */
    public static function invalidType(string $type): self
    {
        return new self(
            message: "Invalid property type: {$type}",
            code: 'PROPERTY_INVALID_TYPE',
            statusCode: 400,
            details: ['provided_type' => $type],
            safeMessage: 'Invalid property type'
        );
    }
}

