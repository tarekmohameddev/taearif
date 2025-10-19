<?php

namespace App\Exceptions\Crm;

use App\Exceptions\Api\ApiException;

/**
 * Customer Exception
 *
 * Domain-specific exceptions for CRM customer operations
 */
class CustomerException extends ApiException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'CRM_CUSTOMER_ERROR';

    /**
     * Customer not found
     */
    public static function notFound($id): self
    {
        return new self(
            message: "Customer with ID {$id} not found",
            code: 'CRM_CUSTOMER_NOT_FOUND',
            statusCode: 404,
            details: ['customer_id' => $id],
            safeMessage: 'Customer not found'
        );
    }

    /**
     * Duplicate customer (email/phone)
     */
    public static function duplicate(string $field, string $value): self
    {
        return new self(
            message: "Customer with {$field} '{$value}' already exists",
            code: 'CRM_CUSTOMER_DUPLICATE',
            statusCode: 409,
            details: [
                'field' => $field,
                'value' => $field === 'email' ? $value : '***' // Hide phone in logs
            ],
            safeMessage: "A customer with this {$field} already exists"
        );
    }

    /**
     * Customer not owned by user
     */
    public static function notOwned($customerId, $userId): self
    {
        return new self(
            message: "Customer ID {$customerId} does not belong to user {$userId}",
            code: 'CRM_CUSTOMER_NOT_OWNED',
            statusCode: 403,
            details: ['customer_id' => $customerId],
            safeMessage: 'You do not have access to this customer'
        );
    }

    /**
     * Invalid stage transition
     */
    public static function invalidStageTransition(string $from, string $to): self
    {
        return new self(
            message: "Cannot transition customer from stage '{$from}' to '{$to}'",
            code: 'CRM_CUSTOMER_INVALID_STAGE_TRANSITION',
            statusCode: 400,
            details: [
                'current_stage' => $from,
                'requested_stage' => $to
            ],
            safeMessage: "Invalid stage transition"
        );
    }
}

