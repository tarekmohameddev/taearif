<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class ChannelPricingNotConfiguredException extends RuntimeException
{
    public function __construct(string $channelType)
    {
        parent::__construct(
            "Channel pricing not configured for '{$channelType}'. " .
            "Please configure pricing in admin panel: /admin/credit-management"
        );
    }
}
