<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for payment-related errors
 * Provides structured error messages for frontend handling
 */
class PaymentException extends Exception
{
    protected $errorCode;
    protected $errorData;

    public function __construct($message, $errorCode = 'PAYMENT_ERROR', $errorData = [], $statusCode = 422)
    {
        parent::__construct($message, $statusCode);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        return response()->json([
            'status' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'error_data' => $this->errorData,
        ], $this->getCode() ?: 422);
    }

    // Static factory methods for common payment errors

    public static function overpayment($installmentId, $installmentAmount, $alreadyPaid, $attemptedAmount)
    {
        $remaining = $installmentAmount - $alreadyPaid;
        $overpayment = ($alreadyPaid + $attemptedAmount) - $installmentAmount;

        return new self(
            "Payment would cause overpayment. The installment is {$installmentAmount} SAR, already paid {$alreadyPaid} SAR, remaining {$remaining} SAR. You are trying to pay {$attemptedAmount} SAR which exceeds by {$overpayment} SAR.",
            'OVERPAYMENT_ERROR',
            [
                'installment_id' => $installmentId,
                'installment_amount' => (float) $installmentAmount,
                'already_paid' => (float) $alreadyPaid,
                'remaining_due' => (float) $remaining,
                'attempted_amount' => (float) $attemptedAmount,
                'overpayment_amount' => (float) $overpayment,
                'max_allowed_payment' => (float) $remaining,
            ],
            422
        );
    }

    public static function installmentFullyPaid($installmentId, $installmentAmount)
    {
        return new self(
            "This installment is already fully paid. No additional payment is required.",
            'INSTALLMENT_FULLY_PAID',
            [
                'installment_id' => $installmentId,
                'installment_amount' => (float) $installmentAmount,
                'remaining_due' => 0,
            ],
            422
        );
    }

    public static function insufficientAmount($minimumAmount)
    {
        return new self(
            "Payment amount is too low. Minimum payment amount is {$minimumAmount} SAR.",
            'INSUFFICIENT_AMOUNT',
            [
                'minimum_amount' => (float) $minimumAmount,
            ],
            422
        );
    }

    public static function installmentNotFound($installmentId)
    {
        return new self(
            "Installment #{$installmentId} not found or does not belong to this rental.",
            'INSTALLMENT_NOT_FOUND',
            [
                'installment_id' => $installmentId,
            ],
            404
        );
    }

    public static function rentalNotActive($rentalId, $status)
    {
        return new self(
            "Cannot collect payment for rental #{$rentalId}. Rental status is '{$status}'. Only active rentals can receive payments.",
            'RENTAL_NOT_ACTIVE',
            [
                'rental_id' => $rentalId,
                'rental_status' => $status,
            ],
            422
        );
    }

    public static function noActiveContract($rentalId)
    {
        return new self(
            "Cannot collect payment. Rental #{$rentalId} has no active contract.",
            'NO_ACTIVE_CONTRACT',
            [
                'rental_id' => $rentalId,
            ],
            422
        );
    }

    public static function invalidPaymentAmount($amount)
    {
        return new self(
            "Invalid payment amount: {$amount} SAR. Amount must be greater than 0.",
            'INVALID_AMOUNT',
            [
                'amount' => (float) $amount,
            ],
            422
        );
    }

    public static function duplicatePayment($reference, $existingPaymentId)
    {
        return new self(
            "Duplicate payment detected. A payment with reference '{$reference}' already exists.",
            'DUPLICATE_PAYMENT',
            [
                'reference' => $reference,
                'existing_payment_id' => $existingPaymentId,
            ],
            422
        );
    }

    public static function bankNameRequired()
    {
        return new self(
            "Bank name is required when payment method is 'bank_transfer'.",
            'BANK_NAME_REQUIRED',
            [],
            422
        );
    }

    public static function installmentCancelled($installmentId)
    {
        return new self(
            "Cannot pay cancelled installment #{$installmentId}. This installment is no longer active.",
            'INSTALLMENT_CANCELLED',
            [
                'installment_id' => $installmentId,
            ],
            422
        );
    }
}

