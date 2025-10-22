<?php

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;
use App\Constants\RmsConstants;

/**
 * Test RmsConstants class
 *
 * Run with: php artisan test --filter=RmsConstantsTest
 */
class RmsConstantsTest extends TestCase
{
    /** @test */
    public function it_has_rental_types_constants()
    {
        $this->assertEquals('monthly', RmsConstants::RENTAL_TYPE_MONTHLY);
        $this->assertEquals('annual', RmsConstants::RENTAL_TYPE_ANNUAL);

        $this->assertIsArray(RmsConstants::RENTAL_TYPES);
        $this->assertCount(2, RmsConstants::RENTAL_TYPES);
        $this->assertContains('monthly', RmsConstants::RENTAL_TYPES);
        $this->assertContains('annual', RmsConstants::RENTAL_TYPES);
    }

    /** @test */
    public function it_has_paying_plans_constants()
    {
        $this->assertEquals('monthly', RmsConstants::PAYING_PLAN_MONTHLY);
        $this->assertEquals('quarterly', RmsConstants::PAYING_PLAN_QUARTERLY);
        $this->assertEquals('semi_annual', RmsConstants::PAYING_PLAN_SEMI_ANNUAL);
        $this->assertEquals('annual', RmsConstants::PAYING_PLAN_ANNUAL);

        $this->assertIsArray(RmsConstants::PAYING_PLANS);
        $this->assertCount(4, RmsConstants::PAYING_PLANS);
    }

    /** @test */
    public function it_has_rental_statuses_constants()
    {
        $expectedStatuses = ['active', 'inactive', 'terminated', 'ended', 'cancelled', 'draft'];

        $this->assertIsArray(RmsConstants::RENTAL_STATUSES);
        $this->assertCount(6, RmsConstants::RENTAL_STATUSES);

        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, RmsConstants::RENTAL_STATUSES);
        }
    }

    /** @test */
    public function it_has_payment_methods_constants()
    {
        $expectedMethods = ['cash', 'bank_transfer', 'credit_card', 'online_payment', 'check', 'other'];

        $this->assertIsArray(RmsConstants::PAYMENT_METHODS);
        $this->assertCount(6, RmsConstants::PAYMENT_METHODS);

        foreach ($expectedMethods as $method) {
            $this->assertContains($method, RmsConstants::PAYMENT_METHODS);
        }
    }

    /** @test */
    public function it_has_contract_statuses_constants()
    {
        $expectedStatuses = ['pending', 'active', 'expired', 'terminated'];

        $this->assertIsArray(RmsConstants::CONTRACT_STATUSES);
        $this->assertCount(4, RmsConstants::CONTRACT_STATUSES);

        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, RmsConstants::CONTRACT_STATUSES);
        }
    }

    /** @test */
    public function it_has_installment_statuses_constants()
    {
        $expectedStatuses = ['pending', 'paid', 'partial', 'overdue', 'void'];

        $this->assertIsArray(RmsConstants::INSTALLMENT_STATUSES);
        $this->assertCount(5, RmsConstants::INSTALLMENT_STATUSES);

        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, RmsConstants::INSTALLMENT_STATUSES);
        }
    }

    /** @test */
    public function it_has_maintenance_priorities_constants()
    {
        $expectedPriorities = ['low', 'medium', 'high', 'critical'];

        $this->assertIsArray(RmsConstants::MAINTENANCE_PRIORITIES);
        $this->assertCount(4, RmsConstants::MAINTENANCE_PRIORITIES);

        foreach ($expectedPriorities as $priority) {
            $this->assertContains($priority, RmsConstants::MAINTENANCE_PRIORITIES);
        }
    }

    /** @test */
    public function it_generates_validation_rules()
    {
        $rule = RmsConstants::validationRule(RmsConstants::RENTAL_TYPES);

        $this->assertIsString($rule);
        $this->assertStringStartsWith('in:', $rule);
        $this->assertStringContainsString('monthly', $rule);
        $this->assertStringContainsString('annual', $rule);
    }

    /** @test */
    public function it_generates_validation_rules_for_different_arrays()
    {
        $rentalTypesRule = RmsConstants::validationRule(RmsConstants::RENTAL_TYPES);
        $this->assertEquals('in:monthly,annual', $rentalTypesRule);

        $paymentMethodsRule = RmsConstants::validationRule(RmsConstants::PAYMENT_METHODS);
        $this->assertStringStartsWith('in:', $paymentMethodsRule);
        $this->assertStringContainsString('cash', $paymentMethodsRule);
        $this->assertStringContainsString('bank_transfer', $paymentMethodsRule);
    }

    /** @test */
    public function it_validates_values_correctly()
    {
        // Valid values
        $this->assertTrue(RmsConstants::isValid('active', RmsConstants::RENTAL_STATUSES));
        $this->assertTrue(RmsConstants::isValid('cash', RmsConstants::PAYMENT_METHODS));
        $this->assertTrue(RmsConstants::isValid('monthly', RmsConstants::RENTAL_TYPES));

        // Invalid values
        $this->assertFalse(RmsConstants::isValid('invalid', RmsConstants::RENTAL_STATUSES));
        $this->assertFalse(RmsConstants::isValid('unknown', RmsConstants::PAYMENT_METHODS));
        $this->assertFalse(RmsConstants::isValid('weekly', RmsConstants::RENTAL_TYPES));
    }

    /** @test */
    public function it_returns_all_constants_as_array()
    {
        $allConstants = RmsConstants::toArray();

        $this->assertIsArray($allConstants);
        $this->assertArrayHasKey('rental_types', $allConstants);
        $this->assertArrayHasKey('paying_plans', $allConstants);
        $this->assertArrayHasKey('rental_statuses', $allConstants);
        $this->assertArrayHasKey('payment_methods', $allConstants);
        $this->assertArrayHasKey('contract_statuses', $allConstants);
        $this->assertArrayHasKey('installment_statuses', $allConstants);
        $this->assertArrayHasKey('maintenance_priorities', $allConstants);
    }

    /** @test */
    public function it_gets_constants_by_category()
    {
        $rentalStatuses = RmsConstants::getByCategory('rental_statuses');
        $this->assertIsArray($rentalStatuses);
        $this->assertEquals(RmsConstants::RENTAL_STATUSES, $rentalStatuses);

        $paymentMethods = RmsConstants::getByCategory('payment_methods');
        $this->assertIsArray($paymentMethods);
        $this->assertEquals(RmsConstants::PAYMENT_METHODS, $paymentMethods);

        // Invalid category
        $invalid = RmsConstants::getByCategory('invalid_category');
        $this->assertNull($invalid);
    }

    /** @test */
    public function it_has_transfer_to_options_with_arabic()
    {
        $this->assertIsArray(RmsConstants::TRANSFER_TO_OPTIONS);
        $this->assertCount(3, RmsConstants::TRANSFER_TO_OPTIONS);

        // Check Arabic strings
        $this->assertContains('منصة ناجز', RmsConstants::TRANSFER_TO_OPTIONS);
        $this->assertContains('المالك', RmsConstants::TRANSFER_TO_OPTIONS);
        $this->assertContains('المكتب', RmsConstants::TRANSFER_TO_OPTIONS);
    }

    /** @test */
    public function it_has_cost_centers_constants()
    {
        $this->assertIsArray(RmsConstants::COST_CENTERS);
        $this->assertCount(2, RmsConstants::COST_CENTERS);
        $this->assertContains('tenant', RmsConstants::COST_CENTERS);
        $this->assertContains('owner', RmsConstants::COST_CENTERS);
    }

    /** @test */
    public function it_has_payment_types_constants()
    {
        $this->assertIsArray(RmsConstants::PAYMENT_TYPES);
        $this->assertCount(3, RmsConstants::PAYMENT_TYPES);
        $this->assertContains('rent', RmsConstants::PAYMENT_TYPES);
        $this->assertContains('cost_item', RmsConstants::PAYMENT_TYPES);
        $this->assertContains('deposit', RmsConstants::PAYMENT_TYPES);
    }

    /** @test */
    public function it_has_amount_types_constants()
    {
        $this->assertIsArray(RmsConstants::AMOUNT_TYPES);
        $this->assertCount(2, RmsConstants::AMOUNT_TYPES);
        $this->assertContains('percentage', RmsConstants::AMOUNT_TYPES);
        $this->assertContains('fixed', RmsConstants::AMOUNT_TYPES);
    }

    /** @test */
    public function it_has_sort_fields_and_orders()
    {
        $this->assertIsArray(RmsConstants::SORT_FIELDS);
        $this->assertGreaterThan(0, count(RmsConstants::SORT_FIELDS));

        $this->assertIsArray(RmsConstants::SORT_ORDERS);
        $this->assertCount(2, RmsConstants::SORT_ORDERS);
        $this->assertContains('asc', RmsConstants::SORT_ORDERS);
        $this->assertContains('desc', RmsConstants::SORT_ORDERS);
    }

    /** @test */
    public function it_provides_helper_arrays()
    {
        $rentalStatuses = RmsConstants::getRentalStatusesArray();
        $this->assertIsArray($rentalStatuses);
        $this->assertArrayHasKey('active', $rentalStatuses);

        $paymentMethods = RmsConstants::getPaymentMethodsArray();
        $this->assertIsArray($paymentMethods);
        $this->assertArrayHasKey('cash', $paymentMethods);

        $priorities = RmsConstants::getMaintenancePrioritiesArray();
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('low', $priorities);
    }

    /** @test */
    public function it_maintains_type_safety()
    {
        // All constants should be strings
        foreach (RmsConstants::RENTAL_STATUSES as $status) {
            $this->assertIsString($status);
        }

        foreach (RmsConstants::PAYMENT_METHODS as $method) {
            $this->assertIsString($method);
        }

        foreach (RmsConstants::CONTRACT_STATUSES as $status) {
            $this->assertIsString($status);
        }
    }

    /** @test */
    public function it_has_no_duplicate_values_in_arrays()
    {
        // Check each constant array for duplicates
        $arrays = [
            'RENTAL_STATUSES' => RmsConstants::RENTAL_STATUSES,
            'PAYMENT_METHODS' => RmsConstants::PAYMENT_METHODS,
            'CONTRACT_STATUSES' => RmsConstants::CONTRACT_STATUSES,
            'INSTALLMENT_STATUSES' => RmsConstants::INSTALLMENT_STATUSES,
        ];

        foreach ($arrays as $name => $array) {
            $unique = array_unique($array);
            $this->assertCount(
                count($array),
                $unique,
                "Duplicate values found in {$name}"
            );
        }
    }

    /** @test */
    public function validation_rules_can_be_used_with_laravel_validator()
    {
        $rule = RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES);

        // Valid value
        $validator = \Validator::make(
            ['status' => 'active'],
            ['status' => ['required', $rule]]
        );
        $this->assertTrue($validator->passes());

        // Invalid value
        $validator = \Validator::make(
            ['status' => 'invalid_status'],
            ['status' => ['required', $rule]]
        );
        $this->assertTrue($validator->fails());
    }
}

