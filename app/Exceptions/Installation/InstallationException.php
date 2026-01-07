<?php

namespace App\Exceptions\Installation;

use App\Exceptions\Api\ApiException;

/**
 * Base Installation Exception
 *
 * All installation-related exceptions extend this class
 */
abstract class InstallationException extends ApiException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'INSTALLATION_ERROR';
}

