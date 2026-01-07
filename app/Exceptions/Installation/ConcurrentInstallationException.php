<?php

namespace App\Exceptions\Installation;

/**
 * Concurrent Installation Exception
 *
 * Thrown when multiple installation attempts are detected simultaneously
 */
class ConcurrentInstallationException extends InstallationException
{
    public function __construct(
        int $userId,
        int $appId,
        string $message = 'Another installation is already in progress'
    ) {
        parent::__construct(
            $message,
            'CONCURRENT_INSTALLATION',
            409,
            [
                'user_id' => $userId,
                'app_id' => $appId,
            ],
            'Please wait for the current installation to complete'
        );
    }
}

