<?php

namespace App\Exceptions\Rms;

use App\Exceptions\Api\ApiException;
use App\Models\Api\Rms\RmRental;

/**
 * Rental Exception
 *
 * Domain-specific exceptions for rental management operations
 */
class RentalException extends ApiException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'RMS_RENTAL_ERROR';

    /**
     * Rental has active contract - cannot be deleted
     */
    public static function hasActiveContract(RmRental $rental, $contract = null): self
    {
        $contract = $contract ?? $rental->activeContract;

        return new self(
            message: "Rental ID {$rental->id} has an active contract and cannot be deleted",
            code: 'RMS_RENTAL_HAS_ACTIVE_CONTRACT',
            statusCode: 400,
            details: [
                'rental_id' => $rental->id,
                'contract_id' => $contract?->id,
                'contract_status' => $contract?->status,
                'tenant_name' => $rental->tenant_full_name
            ],
            safeMessage: 'Cannot delete rental with active or pending contract. Please terminate the contract first.'
        );
    }

    /**
     * Rental not found
     */
    public static function notFound($id): self
    {
        return new self(
            message: "Rental with ID {$id} not found or you don't have access",
            code: 'RMS_RENTAL_NOT_FOUND',
            statusCode: 404,
            details: ['rental_id' => $id],
            safeMessage: 'Rental not found'
        );
    }

    /**
     * Rental does not belong to user
     */
    public static function notOwned($rentalId, $userId): self
    {
        return new self(
            message: "Rental ID {$rentalId} does not belong to user {$userId}",
            code: 'RMS_RENTAL_NOT_OWNED',
            statusCode: 403,
            details: ['rental_id' => $rentalId],
            safeMessage: 'You do not have access to this rental'
        );
    }

    /**
     * Invalid rental status transition
     */
    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(
            message: "Cannot transition rental status from '{$from}' to '{$to}'",
            code: 'RMS_RENTAL_INVALID_STATUS_TRANSITION',
            statusCode: 400,
            details: [
                'current_status' => $from,
                'requested_status' => $to
            ],
            safeMessage: "Invalid status transition from '{$from}' to '{$to}'"
        );
    }

    /**
     * Property already rented
     */
    public static function propertyAlreadyRented($propertyId): self
    {
        return new self(
            message: "Property ID {$propertyId} is already rented",
            code: 'RMS_PROPERTY_ALREADY_RENTED',
            statusCode: 400,
            details: ['property_id' => $propertyId],
            safeMessage: 'This property is already rented'
        );
    }

    /**
     * Cannot modify ended rental
     */
    public static function cannotModifyEnded($rentalId): self
    {
        return new self(
            message: "Cannot modify rental ID {$rentalId} because it has ended",
            code: 'RMS_RENTAL_CANNOT_MODIFY_ENDED',
            statusCode: 400,
            details: ['rental_id' => $rentalId],
            safeMessage: 'Cannot modify ended rental'
        );
    }

    /**
     * Unit already has an active contract
     */
    public static function unitHasActiveContract($unitId): self
    {
        return new self(
            message: "Unit ID {$unitId} already has an active contract",
            code: 'RMS_UNIT_HAS_ACTIVE_CONTRACT',
            statusCode: 400,
            details: ['unit_id' => $unitId],
            safeMessage: 'This unit already has an active contract. Please end the existing contract before creating a new one.'
        );
    }

    /**
     * Unit is incomplete and cannot be used for a rental
     */
    public static function unitIncomplete($unitId, ?string $completionStatus = null, $missingFields = null): self
    {
        $details = [
            'unit_id' => $unitId,
            'completion_status' => $completionStatus,
        ];

        if ($missingFields !== null) {
            $details['missing_fields'] = $missingFields;
        }

        return new self(
            message: "Unit ID {$unitId} is incomplete and cannot be used for a rental",
            code: 'RMS_UNIT_INCOMPLETE',
            statusCode: 400,
            details: $details,
            safeMessage: 'This unit is incomplete and cannot be used for a rental. Please complete the unit first.'
        );
    }
}

