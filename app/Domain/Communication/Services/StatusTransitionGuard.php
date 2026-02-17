<?php

namespace App\Domain\Communication\Services;

class StatusTransitionGuard
{
    /** Allowed forward progression: queued -> sent -> delivered -> read; queued|sent -> failed; failed -> sent (retry). */
    private const ALLOWED = [
        'whatsapp' => [
            'queued' => ['sent', 'failed'],
            'sent' => ['delivered', 'read', 'failed'],
            'delivered' => ['read'],
            'read' => [],
            'failed' => ['sent'],
            'received' => [],
        ],
        'sms' => [
            'pending' => ['sent', 'delivered', 'failed'],
            'sent' => ['delivered', 'failed'],
            'delivered' => [],
            'failed' => ['sent'],
        ],
    ];

    public function canTransition(string $currentStatus, string $proposedStatus, string $channel): bool
    {
        $channel = strtolower($channel);
        $current = strtolower(trim($currentStatus));
        $proposed = strtolower(trim($proposedStatus));

        if ($current === $proposed) {
            return true;
        }

        $map = self::ALLOWED[$channel] ?? null;
        if ($map === null) {
            return false;
        }

        $allowedNext = $map[$current] ?? null;
        if ($allowedNext === null) {
            return false;
        }

        return in_array($proposed, $allowedNext, true);
    }
}
