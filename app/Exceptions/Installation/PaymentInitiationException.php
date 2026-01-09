<?php

namespace App\Exceptions\Installation;

/**
 * Payment Initiation Exception
 *
 * Thrown when payment gateway fails to initialize
 */
class PaymentInitiationException extends InstallationException
{
    public function __construct(
        string $message = 'Failed to initiate payment',
        ?string $gateway = null,
        array $details = []
    ) {
        parent::__construct(
            $message,
            'PAYMENT_INITIATION_FAILED',
            500,
            array_merge($details, array_filter([
                'gateway' => $gateway,
            ])),
            'Unable to process payment. Please try again later'
        );
    }

    public static function noRedirectUrl(?string $gateway = null): self
    {
        return new self(
            'Payment process failed, no redirect URL provided',
            $gateway,
            ['reason' => 'missing_redirect_url']
        );
    }

    public static function gatewayError(string $gateway, string $error): self
    {
        return new self(
            "Payment gateway error: {$error}",
            $gateway,
            ['gateway_error' => $error]
        );
    }
}

