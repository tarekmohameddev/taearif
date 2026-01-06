<?php

namespace App\Exceptions\Marketplace;

use App\Exceptions\BusinessLogicException;

/**
 * App Has Installations Exception
 *
 * Thrown when attempting to delete an app that has active installations
 */
class AppHasInstallationsException extends BusinessLogicException
{
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param string $errorCode
     * @param int $httpCode
     */
    public function __construct(
        string $message = 'Cannot delete app with active installations',
        string $errorCode = 'MARKETPLACE_APP_HAS_INSTALLATIONS',
        int $httpCode = 409
    ) {
        parent::__construct($message, $errorCode, $httpCode);
    }
}

