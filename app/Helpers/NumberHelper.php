<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Arabic-Indic numerals mapping to Western numerals
     *
     * @var array
     */
    protected static $arabicNumerals = [
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
    ];

    /**
     * Persian/Farsi numerals mapping to Western numerals
     *
     * @var array
     */
    protected static $persianNumerals = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
    ];

    /**
     * Convert Arabic-Indic and Persian numerals to Western numerals
     *
     * @param string|int|float|null $value
     * @return string|int|float|null
     */
    public static function convertToWestern($value)
    {
        if (is_null($value)) {
            return $value;
        }

        // If it's already a number (int/float), return as is
        if (is_numeric($value) && !is_string($value)) {
            return $value;
        }

        // Convert to string for processing
        $string = (string) $value;

        // Replace Arabic-Indic numerals
        $string = strtr($string, self::$arabicNumerals);

        // Replace Persian numerals
        $string = strtr($string, self::$persianNumerals);

        // If the original value was numeric, try to preserve the type
        if (is_numeric($string)) {
            // If it contains a decimal point, return as float
            if (strpos($string, '.') !== false) {
                return (float) $string;
            }
            // Otherwise return as integer if it's not too large
            if ($string <= PHP_INT_MAX && $string >= PHP_INT_MIN) {
                return (int) $string;
            }
        }

        return $string;
    }

    /**
     * Convert Arabic-Indic and Persian numerals in an array (recursive)
     *
     * @param array $data
     * @return array
     */
    public static function convertArrayToWestern(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::convertArrayToWestern($value);
            } else {
                $data[$key] = self::convertToWestern($value);
            }
        }

        return $data;
    }

    /**
     * Check if string contains Arabic-Indic or Persian numerals
     *
     * @param string $value
     * @return bool
     */
    public static function hasArabicNumerals(string $value): bool
    {
        return preg_match('/[٠-٩۰-۹]/', $value) === 1;
    }
}

