<?php

namespace App\Exceptions\Installation;

use App\Enums\InstallStatus;

/**
 * Invalid Status Transition Exception
 *
 * Thrown when attempting an invalid status transition
 */
class InvalidStatusTransitionException extends InstallationException
{
    public function __construct(
        InstallStatus $from,
        InstallStatus $to,
        string $reason = ''
    ) {
        $message = "Cannot transition from {$from->value} to {$to->value}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        parent::__construct(
            $message,
            'INVALID_STATUS_TRANSITION',
            422,
            [
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
            ],
            'Invalid installation status change'
        );
    }
}

