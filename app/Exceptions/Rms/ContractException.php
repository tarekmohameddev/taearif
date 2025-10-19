<?php

namespace App\Exceptions\Rms;

use App\Exceptions\Api\ApiException;
use App\Models\Api\Rms\RmContract;

/**
 * Contract Exception
 *
 * Domain-specific exceptions for contract operations
 */
class ContractException extends ApiException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'RMS_CONTRACT_ERROR';

    /**
     * Contract not found
     */
    public static function notFound($id): self
    {
        return new self(
            message: "Contract with ID {$id} not found",
            code: 'RMS_CONTRACT_NOT_FOUND',
            statusCode: 404,
            details: ['contract_id' => $id],
            safeMessage: 'Contract not found'
        );
    }

    /**
     * Contract already active
     */
    public static function alreadyActive($rentalId): self
    {
        return new self(
            message: "Rental ID {$rentalId} already has an active contract",
            code: 'RMS_CONTRACT_ALREADY_ACTIVE',
            statusCode: 400,
            details: ['rental_id' => $rentalId],
            safeMessage: 'This rental already has an active contract'
        );
    }

    /**
     * Contract already terminated
     */
    public static function alreadyTerminated(RmContract $contract): self
    {
        return new self(
            message: "Contract ID {$contract->id} is already terminated",
            code: 'RMS_CONTRACT_ALREADY_TERMINATED',
            statusCode: 400,
            details: [
                'contract_id' => $contract->id,
                'status' => $contract->status,
                'termination_date' => $contract->end_date
            ],
            safeMessage: 'This contract is already terminated'
        );
    }

    /**
     * Contract cannot be modified
     */
    public static function cannotModify(RmContract $contract, string $reason): self
    {
        return new self(
            message: "Contract ID {$contract->id} cannot be modified: {$reason}",
            code: 'RMS_CONTRACT_CANNOT_MODIFY',
            statusCode: 400,
            details: [
                'contract_id' => $contract->id,
                'status' => $contract->status,
                'reason' => $reason
            ],
            safeMessage: "Cannot modify contract: {$reason}"
        );
    }

    /**
     * Invalid contract status
     */
    public static function invalidStatus(string $status): self
    {
        return new self(
            message: "Invalid contract status: {$status}",
            code: 'RMS_CONTRACT_INVALID_STATUS',
            statusCode: 400,
            details: [
                'provided_status' => $status,
                'valid_statuses' => ['pending', 'active', 'expired', 'terminated']
            ],
            safeMessage: 'Invalid contract status'
        );
    }

    /**
     * Grace period exceeded
     */
    public static function gracePeriodExceeded(RmContract $contract): self
    {
        return new self(
            message: "Contract ID {$contract->id} grace period has been exceeded",
            code: 'RMS_CONTRACT_GRACE_PERIOD_EXCEEDED',
            statusCode: 400,
            details: [
                'contract_id' => $contract->id,
                'end_date' => $contract->end_date,
                'grace_period_months' => $contract->grace_period_months
            ],
            safeMessage: 'Contract grace period has been exceeded'
        );
    }
}

