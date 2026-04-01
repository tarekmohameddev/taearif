<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') return null;
        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        return '966' . $digits;
    }

    /**
     * All distinct `users.phone` string values to try for login lookup.
     * Handles Saudi mobile local forms: 5XXXXXXXX vs 05XXXXXXXX vs 9665… / +966…
     */
    public static function loginLookupValues(?string $phone): array
    {
        if ($phone === null || trim($phone) === '') {
            return [];
        }

        $raw = trim($phone);
        $values = [$raw];

        $normalized = self::normalize($raw);
        if ($normalized !== null) {
            $values[] = $normalized;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '') {
            return array_values(array_unique(array_filter($values)));
        }

        // 5XXXXXXXX <-> 05XXXXXXXX (common DB vs client mismatch)
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $values[] = '0' . $digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '05')) {
            $values[] = substr($digits, 1);
        }

        // 9665XXXXXXXX / 009665XXXXXXXX -> local 5XXXXXXXX and 05XXXXXXXX
        if (str_starts_with($digits, '00966') && strlen($digits) >= 14) {
            $local = substr($digits, 5);
            if (strlen($local) === 9 && str_starts_with($local, '5')) {
                $values[] = $local;
                $values[] = '0' . $local;
            }
        } elseif (str_starts_with($digits, '966') && strlen($digits) >= 12) {
            $local = substr($digits, 3);
            if (strlen($local) === 9 && str_starts_with($local, '5')) {
                $values[] = $local;
                $values[] = '0' . $local;
            }
        }

        return array_values(array_unique(array_filter($values, fn ($v) => $v !== null && $v !== '')));
    }
}


