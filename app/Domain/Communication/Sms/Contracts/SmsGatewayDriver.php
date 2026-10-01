<?php

namespace App\Domain\Communication\Sms\Contracts;

/** A concrete provider adapter. Credentials and provider-specific request/response mapping stay in the adapter. */
interface SmsGatewayDriver extends SmsGatewayClient
{
    public function isConfigured(): bool;

    public function providerName(): string;
}
