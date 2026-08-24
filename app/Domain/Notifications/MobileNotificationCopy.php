<?php

namespace App\Domain\Notifications;

use Illuminate\Support\Facades\App;

/**
 * Resolves mobile push/inbox title and body copy.
 *
 * Always forces Arabic so stored notification text is Arabic regardless of
 * the ambient / request locale (mobile displays server title/body as-is).
 */
final class MobileNotificationCopy
{
    public static function t(string $key, array $replace = []): string
    {
        $previous = App::getLocale();

        try {
            App::setLocale('ar');

            return (string) __('notifications.'.$key, $replace);
        } finally {
            App::setLocale($previous);
        }
    }
}
