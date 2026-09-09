<?php

namespace App\Services\Vercel;

/**
 * Canonical www → apex redirect correctness.
 *
 * Correct only when redirect target is the exact apex AND status is explicit HTTP 301.
 * HTTP 308 or a missing/unknown redirectStatusCode must never count as correct.
 */
final class WwwRedirectPolicy
{
    /**
     * @param  array<string, mixed>|null  $entry  Inventory or project-domain entry
     */
    public static function isCorrect(?array $entry, string $apex): bool
    {
        if ($entry === null) {
            return false;
        }

        if (! filled($entry['redirect'] ?? null)) {
            return false;
        }

        $redirectTarget = strtolower((string) $entry['redirect']);
        $expectedApex = strtolower(trim($apex));

        if ($redirectTarget !== $expectedApex) {
            return false;
        }

        if (! array_key_exists('redirectStatusCode', $entry) || $entry['redirectStatusCode'] === null || $entry['redirectStatusCode'] === '') {
            return false;
        }

        return (int) $entry['redirectStatusCode'] === 301;
    }
}
