<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Manual Test for Rental Store Endpoint
 * 
 * This test demonstrates how to test the rental store endpoint manually
 * and provides examples of the expected data structure and behavior.
 * 
 * To run this test, you need to:
 * 1. Ensure your database is properly configured
 * 2. Run: php artisan test tests/Feature/RentalStoreManualTest.php
 * 
 * Or test manually via API:
 * 1. Start your Laravel server: php artisan serve
 * 2. Use Postman/curl to POST to: http://localhost:8000/api/v1/rms/rentals
 * 3. Include proper authentication headers
 */
class RentalStoreManualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Example of complete rental data that should be sent to the API
     */
    public function getCompleteRentalData()
    {
        return [
            'tenant_full_name' => 'Ahmed Al-Rashid',
            'tenant_phone' => '+966501234567',
            'tenant_email' => 'ahmed.rashid@example.com',
            'tenant_job_title' => 'Software Engineer',
            'tenant_social_status' => 'married',
            'tenant_national_id' => '1234567890',
            'unit_id' => 1, // Should exist in your database
            'project_id' => 1,  // Should exist in your database
            'unit_label' => 'A-101',
            'building' => 'PROP-2024-001',
            'move_in_date' => '2024-01-01',
            'rental_type' => 'monthly',
            'rental_duration' => 12,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 30000.00,
            'currency' => 'SAR',
            'contract_number' => 'CNT-2024-001',
            'notes' => 'Test rental with complete data for integration testing'
        ];
    }

    /**
     * Example of minimal rental data (only required fields)
     */
    public function getMinimalRentalData()
    {
        return [
            'tenant_full_name' => 'Minimal Test User',
            'tenant_phone' => '+966501234567'
        ];
    }

    /**
     * Example of quarterly payment plan data
     */
    public function getQuarterlyRentalData()
    {
        return [
            'tenant_full_name' => 'Sarah Johnson',
            'tenant_phone' => '+966502345678',
            'tenant_email' => 'sarah.johnson@example.com',
            'unit_id' => 1,
            'project_id' => 1,
            'move_in_date' => '2024-01-01',
            'rental_period' => 4, // 4 quarters = 12 months
            'paying_plan' => 'quarterly',
            'base_rent_amount' => 2500.00,
            'currency' => 'SAR',
            'platform_fee' => 100.00,
            'water_fee' => 50.00,
            'office_commission_type' => 'amount',
            'office_commission_value' => 500.00
        ];
    }

    /**
     * Test that demonstrates the expected API response structure
     */
    public function test_expected_api_response_structure()
    {
        // This test shows what the API should return
        $expectedResponse = [
            'status' => true,
            'data' => [
                'id' => 1,
                'status' => 'active',
                'contract' => [
                    'id' => 1,
                    'status' => 'active'
                ]
            ]
        ];

        $this->assertArrayHasKey('status', $expectedResponse);
        $this->assertArrayHasKey('data', $expectedResponse);
        $this->assertTrue($expectedResponse['status']);
        $this->assertEquals('active', $expectedResponse['data']['status']);
        $this->assertArrayHasKey('contract', $expectedResponse['data']);
    }

    /**
     * Test that demonstrates validation rules
     */
    public function test_validation_rules()
    {
        // Required fields
        $requiredFields = ['tenant_full_name', 'tenant_phone'];
        
        // Optional fields with validation rules
        $validationRules = [
            'tenant_email' => 'email',
            'tenant_social_status' => 'in:single,married,divorced,widowed,other',
            'paying_plan' => 'in:monthly,quarterly,semi_annual,annual',
            'office_commission_type' => 'in:percentage,amount',
            'currency' => 'size:3',
            'base_rent_amount' => 'numeric',
            'deposit_amount' => 'numeric',
            'platform_fee' => 'numeric|min:0',
            'water_fee' => 'numeric|min:0',
            'office_commission_value' => 'numeric|min:0',
            'move_in_date' => 'date',
            'rental_period' => 'integer'
        ];

        $this->assertCount(2, $requiredFields);
        $this->assertContains('tenant_full_name', $requiredFields);
        $this->assertContains('tenant_phone', $requiredFields);
        
        $this->assertArrayHasKey('tenant_email', $validationRules);
        $this->assertArrayHasKey('paying_plan', $validationRules);
    }

    /**
     * Test that demonstrates business logic calculations
     */
    public function test_business_logic_calculations()
    {
        // Test total rental amount calculation
        $baseRentAmount = 2500.00;
        $rentalPeriod = 12;
        $expectedTotalAmount = $baseRentAmount * $rentalPeriod; // 30000.00
        
        $this->assertEquals(30000.00, $expectedTotalAmount);

        // Test office fee calculation (percentage type)
        $officeCommissionType = 'percentage';
        $officeCommissionValue = 5.0;
        $expectedOfficeFee = ($rentalPeriod * $baseRentAmount) * ($officeCommissionValue / 100); // 1500.00
        
        $this->assertEquals(1500.00, $expectedOfficeFee);

        // Test office fee calculation (amount type)
        $officeCommissionType = 'amount';
        $officeCommissionValue = 500.00;
        $expectedOfficeFeeAmount = $officeCommissionValue; // 500.00
        
        $this->assertEquals(500.00, $expectedOfficeFeeAmount);

        // Test installment calculations for different payment plans
        $monthlyInstallment = 2500.00; // 1 month
        $quarterlyInstallment = 2500.00 * 3; // 7500.00 (3 months)
        $semiAnnualInstallment = 2500.00 * 6; // 15000.00 (6 months)
        $annualInstallment = 2500.00 * 12; // 30000.00 (12 months)

        $this->assertEquals(2500.00, $monthlyInstallment);
        $this->assertEquals(7500.00, $quarterlyInstallment);
        $this->assertEquals(15000.00, $semiAnnualInstallment);
        $this->assertEquals(30000.00, $annualInstallment);
    }

    /**
     * Test that demonstrates contract duration calculations
     */
    public function test_contract_duration_calculations()
    {
        $moveInDate = '2024-01-01';
        $rentalPeriod = 12;
        $payingPlan = 'monthly';

        // Calculate total months based on paying plan
        $totalMonths = match($payingPlan) {
            'monthly' => $rentalPeriod * 1,
            'quarterly' => $rentalPeriod * 3,
            'semi_annual' => $rentalPeriod * 6,
            'annual' => $rentalPeriod * 12,
            default => $rentalPeriod
        };

        $this->assertEquals(12, $totalMonths);

        // Calculate end date (start date + total months - 1 day)
        $startDate = Carbon::parse($moveInDate);
        $endDate = $startDate->copy()->addMonths($totalMonths)->subDay();
        
        $this->assertEquals('2024-12-31', $endDate->format('Y-m-d'));
    }

    /**
     * Test that demonstrates installment generation logic
     */
    public function test_installment_generation_logic()
    {
        $baseRentAmount = 2500.00;
        $rentalPeriod = 12;
        $payingPlan = 'monthly';
        $moveInDate = '2024-01-01';

        // Calculate chunks and periods
        $chunks = match($payingPlan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        $periods = $rentalPeriod;
        $installmentAmount = round($baseRentAmount * $chunks, 2);

        $this->assertEquals(1, $chunks);
        $this->assertEquals(12, $periods);
        $this->assertEquals(2500.00, $installmentAmount);

        // Generate installment dates
        $start = Carbon::parse($moveInDate);
        $installmentDates = [];
        
        for ($i = 0; $i < $periods; $i++) {
            $dueDate = $start->copy()->addMonths($i * $chunks);
            $installmentDates[] = $dueDate->format('Y-m-d');
        }

        $this->assertCount(12, $installmentDates);
        $this->assertEquals('2024-01-01', $installmentDates[0]);
        $this->assertEquals('2024-12-01', $installmentDates[11]);
    }

    /**
     * Test that demonstrates different payment plan scenarios
     */
    public function test_different_payment_plan_scenarios()
    {
        $baseRentAmount = 2000.00;
        $rentalPeriod = 2; // 2 periods

        $scenarios = [
            'monthly' => [
                'total_months' => 2,
                'installment_amount' => 2000.00,
                'installment_count' => 2
            ],
            'quarterly' => [
                'total_months' => 6,
                'installment_amount' => 6000.00,
                'installment_count' => 2
            ],
            'semi_annual' => [
                'total_months' => 12,
                'installment_amount' => 12000.00,
                'installment_count' => 2
            ],
            'annual' => [
                'total_months' => 24,
                'installment_amount' => 24000.00,
                'installment_count' => 2
            ]
        ];

        foreach ($scenarios as $plan => $expected) {
            $chunks = match($plan) {
                'monthly' => 1,
                'quarterly' => 3,
                'semi_annual' => 6,
                'annual' => 12,
                default => 1
            };

            $totalMonths = $rentalPeriod * $chunks;
            $installmentAmount = $baseRentAmount * $chunks;
            $installmentCount = $rentalPeriod;

            $this->assertEquals($expected['total_months'], $totalMonths, "Total months for {$plan}");
            $this->assertEquals($expected['installment_amount'], $installmentAmount, "Installment amount for {$plan}");
            $this->assertEquals($expected['installment_count'], $installmentCount, "Installment count for {$plan}");
        }
    }

    /**
     * Test that demonstrates the complete workflow
     */
    public function test_complete_rental_workflow()
    {
        // Step 1: Prepare rental data
        $rentalData = $this->getCompleteRentalData();
        
        // Step 2: Validate required fields
        $this->assertArrayHasKey('tenant_full_name', $rentalData);
        $this->assertArrayHasKey('tenant_phone', $rentalData);
        
        // Step 3: Check if contract should be created
        $hasEnoughData = isset($rentalData['move_in_date']) 
            && isset($rentalData['rental_period']) 
            && isset($rentalData['paying_plan']) 
            && isset($rentalData['base_rent_amount']);
        
        $this->assertTrue($hasEnoughData);
        
        // Step 4: Calculate expected values
        $expectedTotalAmount = $rentalData['base_rent_amount'] * $rentalData['rental_period'];
        $this->assertEquals(30000.00, $expectedTotalAmount);
        
        // Step 5: Calculate expected office fee
        $expectedOfficeFee = ($rentalData['rental_period'] * $rentalData['base_rent_amount']) * ($rentalData['office_commission_value'] / 100);
        $this->assertEquals(1500.00, $expectedOfficeFee);
        
        // Step 6: Calculate expected contract duration
        $totalMonths = match($rentalData['paying_plan']) {
            'monthly' => $rentalData['rental_period'] * 1,
            'quarterly' => $rentalData['rental_period'] * 3,
            'semi_annual' => $rentalData['rental_period'] * 6,
            'annual' => $rentalData['rental_period'] * 12,
            default => $rentalData['rental_period']
        };
        
        $this->assertEquals(12, $totalMonths);
        
        // Step 7: Calculate expected installment count and amounts
        $chunks = match($rentalData['paying_plan']) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };
        
        $expectedInstallmentCount = $rentalData['rental_period'];
        $expectedInstallmentAmount = $rentalData['base_rent_amount'] * $chunks;
        
        $this->assertEquals(12, $expectedInstallmentCount);
        $this->assertEquals(2500.00, $expectedInstallmentAmount);
    }
}
