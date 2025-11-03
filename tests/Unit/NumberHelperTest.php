<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NumberHelper;

class NumberHelperTest extends TestCase
{
    /**
     * Test converting Arabic-Indic numerals to Western numerals
     *
     * @return void
     */
    public function test_convert_arabic_indic_numerals_to_western()
    {
        // Test Arabic-Indic numerals (the ones in your error)
        $this->assertEquals('17000', NumberHelper::convertToWestern('١٧٠٠٠'));
        $this->assertEquals('20000', NumberHelper::convertToWestern('٢٠٠٠٠'));

        // Test individual digits
        $this->assertEquals('0', NumberHelper::convertToWestern('٠'));
        $this->assertEquals('1', NumberHelper::convertToWestern('١'));
        $this->assertEquals('2', NumberHelper::convertToWestern('٢'));
        $this->assertEquals('3', NumberHelper::convertToWestern('٣'));
        $this->assertEquals('4', NumberHelper::convertToWestern('٤'));
        $this->assertEquals('5', NumberHelper::convertToWestern('٥'));
        $this->assertEquals('6', NumberHelper::convertToWestern('٦'));
        $this->assertEquals('7', NumberHelper::convertToWestern('٧'));
        $this->assertEquals('8', NumberHelper::convertToWestern('٨'));
        $this->assertEquals('9', NumberHelper::convertToWestern('٩'));
    }

    /**
     * Test converting Persian numerals to Western numerals
     *
     * @return void
     */
    public function test_convert_persian_numerals_to_western()
    {
        $this->assertEquals('12345', NumberHelper::convertToWestern('۱۲۳۴۵'));
        $this->assertEquals('67890', NumberHelper::convertToWestern('۶۷۸۹۰'));
    }

    /**
     * Test converting mixed content
     *
     * @return void
     */
    public function test_convert_mixed_content()
    {
        // Mixed Arabic-Indic numerals with text
        $this->assertEquals('السعر 17000 ريال', NumberHelper::convertToWestern('السعر ١٧٠٠٠ ريال'));

        // Phone number with Arabic-Indic numerals
        $this->assertEquals('0540215420', NumberHelper::convertToWestern('٠٥٤٠٢١٥٤٢٠'));
    }

    /**
     * Test converting arrays
     *
     * @return void
     */
    public function test_convert_array_to_western()
    {
        $input = [
            'budget_from' => '١٧٠٠٠',
            'budget_to' => '٢٠٠٠٠',
            'phone' => '٠٥٤٠٢١٥٤٢٠',
            'area_from' => '50',
            'nested' => [
                'price' => '١٢٣٤٥',
            ],
        ];

        $expected = [
            'budget_from' => '17000',
            'budget_to' => '20000',
            'phone' => '0540215420',
            'area_from' => '50',
            'nested' => [
                'price' => '12345',
            ],
        ];

        $result = NumberHelper::convertArrayToWestern($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test null and empty values
     *
     * @return void
     */
    public function test_handle_null_and_empty_values()
    {
        $this->assertNull(NumberHelper::convertToWestern(null));
        $this->assertEquals('', NumberHelper::convertToWestern(''));
        $this->assertEquals(0, NumberHelper::convertToWestern(0));
    }

    /**
     * Test Western numerals remain unchanged
     *
     * @return void
     */
    public function test_western_numerals_unchanged()
    {
        $this->assertEquals('12345', NumberHelper::convertToWestern('12345'));
        $this->assertEquals(12345, NumberHelper::convertToWestern(12345));
        $this->assertEquals(123.45, NumberHelper::convertToWestern(123.45));
    }

    /**
     * Test detecting Arabic numerals
     *
     * @return void
     */
    public function test_has_arabic_numerals()
    {
        $this->assertTrue(NumberHelper::hasArabicNumerals('١٧٠٠٠'));
        $this->assertTrue(NumberHelper::hasArabicNumerals('السعر ١٧٠٠٠'));
        $this->assertTrue(NumberHelper::hasArabicNumerals('۱۲۳۴۵'));

        $this->assertFalse(NumberHelper::hasArabicNumerals('17000'));
        $this->assertFalse(NumberHelper::hasArabicNumerals('The price 17000'));
    }

    /**
     * Test the exact values from your error log
     *
     * @return void
     */
    public function test_exact_error_log_values()
    {
        // These are the exact values from your error log
        $budgetFrom = '١٧٠٠٠';
        $budgetTo = '٢٠٠٠٠';

        $convertedFrom = NumberHelper::convertToWestern($budgetFrom);
        $convertedTo = NumberHelper::convertToWestern($budgetTo);

        $this->assertEquals('17000', $convertedFrom);
        $this->assertEquals('20000', $convertedTo);

        // Verify they're numeric after conversion
        $this->assertTrue(is_numeric($convertedFrom));
        $this->assertTrue(is_numeric($convertedTo));
    }

    /**
     * Test that phone numbers without Arabic numerals stay as strings
     *
     * @return void
     */
    public function test_phone_numbers_remain_strings()
    {
        // Phone numbers with Western numerals should remain unchanged
        $phone1 = '5555555555';
        $phone2 = '0540215420';

        $result1 = NumberHelper::convertToWestern($phone1);
        $result2 = NumberHelper::convertToWestern($phone2);

        // Should remain exactly the same (including type)
        $this->assertSame($phone1, $result1);
        $this->assertSame($phone2, $result2);

        // Should be strings
        $this->assertIsString($result1);
        $this->assertIsString($result2);
    }

    /**
     * Test that only values with Arabic numerals are converted
     *
     * @return void
     */
    public function test_only_convert_values_with_arabic_numerals()
    {
        // Values without Arabic numerals should be untouched
        $this->assertSame('12345', NumberHelper::convertToWestern('12345'));
        $this->assertSame('hello', NumberHelper::convertToWestern('hello'));
        $this->assertSame('test123', NumberHelper::convertToWestern('test123'));

        // Values with Arabic numerals should be converted
        $this->assertEquals('12345', NumberHelper::convertToWestern('١٢٣٤٥'));
        $this->assertEquals('test123', NumberHelper::convertToWestern('test١٢٣'));
    }
}

