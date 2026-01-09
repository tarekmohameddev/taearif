<?php

namespace App\Exceptions\Installation;

/**
 * Invalid Installation Exception
 *
 * Thrown when user or app is invalid for installation
 */
class InvalidInstallationException extends InstallationException
{
    public function __construct(
        string $message = 'Invalid user or app provided',
        ?int $userId = null,
        ?int $appId = null
    ) {
        parent::__construct(
            $message,
            'INVALID_INSTALLATION',
            400,
            array_filter([
                'user_id' => $userId,
                'app_id' => $appId,
            ]),
            'Unable to process installation request'
        );
    }

    public static function invalidUser(?int $userId = null): self
    {
        return new self('Invalid user provided', $userId, null);
    }

    public static function invalidApp(?int $appId = null): self
    {
        return new self('Invalid app provided', null, $appId);
    }

    public static function appNotEnabled(int $appId): self
    {
        return new self(
            'App is not enabled for installation',
            null,
            $appId
        );
    }
}

