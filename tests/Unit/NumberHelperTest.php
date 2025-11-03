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
}

