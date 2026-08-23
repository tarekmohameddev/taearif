<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;

/**
 * Strict KSA phone number normaliser for the Calling domain.
 *
 * Accepts:
 *   0512345678  ->  +966512345678
 *   966512345678  ->  +966512345678
 *   +966512345678  ->  +966512345678
 *   0512 345 678   ->  +966512345678
 *
 * Rejects anything that does not resolve to +9665XXXXXXXX (10 digits after +966).
 *
 * This is intentionally separate from App\Support\PhoneNormalizer which returns
 * the bare 966... format used by legacy features — do not merge them.
 */
final class PhoneNumberService
{
    private const COUNTRY_CODE = '966';
    private const MOBILE_PREFIX = '5'; // KSA mobile numbers start with 05xx
    private const EXPECTED_NATIONAL_LENGTH = 9; // 5 + 8 digits

    /**
     * Convert any accepted input to strict E.164 (+9665XXXXXXXX).
     *
     * @throws InvalidPhoneNumberException
     */
    public function toE164(string $input): string
    {
        $digits = preg_replace('/\D/', '', $input) ?? '';

        // Strip leading country code variants
        if (str_starts_with($digits, '00' . self::COUNTRY_CODE)) {
            $digits = substr($digits, strlen('00' . self::COUNTRY_CODE));
        } elseif (str_starts_with($digits, self::COUNTRY_CODE)) {
            $digits = substr($digits, strlen(self::COUNTRY_CODE));
        }

        // Strip leading zero (national format)
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (
            strlen($digits) !== self::EXPECTED_NATIONAL_LENGTH
            || !str_starts_with($digits, self::MOBILE_PREFIX)
        ) {
            throw new InvalidPhoneNumberException(
                "Phone number must be a valid KSA mobile number (05XXXXXXXX). Got: {$input}"
            );
        }

        return '+' . self::COUNTRY_CODE . $digits;
    }

    /**
     * Convert an E.164 number to the dial string format expected by the trunk.
     * For KSA Yeastar/STC trunks the full E.164 form works in all tested configs.
     * Conversion can be adjusted per trunk type here without touching call code.
     */
    public function toDialString(string $e164): string
    {
        // Strip the leading '+'
        return ltrim($e164, '+');
    }

    /**
     * Return true if the string is already valid E.164 (+9665XXXXXXXX).
     */
    public function isValidE164(string $input): bool
    {
        try {
            $this->toE164($input);
            return true;
        } catch (InvalidPhoneNumberException) {
            return false;
        }
    }
}
