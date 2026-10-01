<?php

namespace App\Domain\Communication\Sms\Contracts;

interface SmsGatewayReadiness
{
    public function isReady(): bool;

    public function configuredProvider(): ?string;
}
