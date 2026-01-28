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

class RentalStoreIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $property;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        // Create test project manually
        $this->project = Project::create([
            'user_id' => $this->user->id,
            'featured_image' => 'test-image.jpg',
            'min_price' => 100000,
            'max_price' => 500000,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'featured' => 1,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => 100,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
            'amenities' => []
        ]);

        // Create test property manually
        $this->property = Property::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'category_id' => 1,
            'region_id' => 1,
            'featured_image' => 'test-property.jpg',
            'price' => 250000,
            'pricePerMeter' => 5000,
            'purpose' => 'rent',
            'type' => 'apartment',
            'beds' => 2,
            'bath' => 2,
            'area' => 100,
            'size' => 100,
            'status' => 'available',
            'property_status' => 'available',
            'featured' => 1,
            'features' => [],
            'faqs' => [],
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'reorder' => 1,
            'reorder_featured' => 1
        ]);
    }

    /** @test */
    public function it_can_create_rental_with_complete_data_and_generates_contract_installments()
    {
        // Arrange - Complete rental data
        $rentalData = [
            'tenant_full_name' => 'Ahmed Al-Rashid',
            'tenant_phone' => '+966501234567',
            'tenant_email' => 'ahmed.rashid@example.com',
            'tenant_job_title' => 'Software Engineer',
            'tenant_social_status' => 'married',
            'tenant_national_id' => '1234567890',
            'unit_id' => $this->property->id,
            'project_id' => $this->project->id,
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

        // Act - Create rental via API
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert - Response structure
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'id',
                        'status',
                        'contract' => [
                            'id',
                            'status'
                        ]
                    ]
                ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['status']);
        $this->assertEquals('active', $responseData['data']['status']);

        // Assert - Rental was created in database
        $this->assertDatabaseHas('rm_rentals', [
            'user_id' => $this->user->id,
            'tenant_full_name' => 'Ahmed Al-Rashid',
            'tenant_phone' => '+966501234567',
            'unit_id' => $this->property->id,
            'project_id' => $this->project->id,
            'rental_type' => 'monthly',
            'rental_duration' => 12,
            'total_rental_amount' => 30000.00,
            'status' => 'active'
        ]);

        $rental = RmRental::where('tenant_full_name', 'Ahmed Al-Rashid')->first();
        $this->assertNotNull($rental);

        // Assert - Contract was created
        $this->assertDatabaseHas('rm_contracts', [
            'user_id' => $this->user->id,
            'rental_id' => $rental->id,
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31' // 12 months from start date minus 1 day
        ]);

        $contract = RmContract::where('rental_id', $rental->id)->first();
        $this->assertNotNull($contract);

        // Assert - Installments were generated (12 monthly installments)
        $installments = RmPaymentInstallment::where('rental_id', $rental->id)->get();
        $this->assertCount(12, $installments);

        // Assert - First installment details
        $firstInstallment = $installments->first();
        $this->assertEquals(1, $firstInstallment->sequence_no);
        $this->assertEquals('2024-01-01', $firstInstallment->due_date->format('Y-m-d'));
        $this->assertEquals(2500.00, $firstInstallment->amount);
        $this->assertEquals('pending', $firstInstallment->status);

        // Assert - Last installment details
        $lastInstallment = $installments->last();
        $this->assertEquals(12, $lastInstallment->sequence_no);
        $this->assertEquals('2024-12-01', $lastInstallment->due_date->format('Y-m-d'));
        $this->assertEquals(2500.00, $lastInstallment->amount);

        // Assert - Total rental amount calculation
        $this->assertEquals(30000.00, $rental->total_rental_amount); // 2500 * 12
    }

    /** @test */
    public function it_can_create_rental_with_quarterly_payment_plan()
    {
        // Arrange - Quarterly payment data
        $rentalData = [
            'tenant_full_name' => 'Sarah Johnson',
            'tenant_phone' => '+966502345678',
            'tenant_email' => 'sarah.johnson@example.com',
            'unit_id' => $this->property->id,
            'project_id' => $this->project->id,
            'move_in_date' => '2024-01-01',
            'rental_type' => 'monthly',
            'rental_duration' => 12, // 12 months
            'paying_plan' => 'quarterly',
            'total_rental_amount' => 30000.00,
            'currency' => 'SAR'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Sarah Johnson')->first();
        $this->assertNotNull($rental);

        // Assert - Contract duration (4 quarters = 12 months)
        $contract = RmContract::where('rental_id', $rental->id)->first();
        $this->assertEquals('2024-01-01', $contract->start_date);
        $this->assertEquals('2024-12-31', $contract->end_date);

        // Assert - 4 quarterly installments
        $installments = RmPaymentInstallment::where('rental_id', $rental->id)->get();
        $this->assertCount(4, $installments);

        // Assert - Quarterly amounts (2500 * 3 months = 7500 per quarter)
        $firstInstallment = $installments->first();
        $this->assertEquals(7500.00, $firstInstallment->amount);
        $this->assertEquals('2024-01-01', $firstInstallment->due_date->format('Y-m-d'));

        $secondInstallment = $installments->skip(1)->first();
        $this->assertEquals(7500.00, $secondInstallment->amount);
        $this->assertEquals('2024-04-01', $secondInstallment->due_date->format('Y-m-d'));

        // Assert - Office fee calculation (amount type)
        $this->assertEquals(500.00, $rental->office_fee);
    }

    /** @test */
    public function it_can_create_rental_with_minimal_required_data()
    {
        // Arrange - Minimal required data only
        $rentalData = [
            'tenant_full_name' => 'Minimal Test',
            'tenant_phone' => '+966503456789'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'data' => [
                        'status' => 'active'
                    ]
                ]);

        // Assert - Rental created but no contract/installments (insufficient data)
        $this->assertDatabaseHas('rm_rentals', [
            'tenant_full_name' => 'Minimal Test',
            'tenant_phone' => '+966503456789',
            'status' => 'active'
        ]);

        $rental = RmRental::where('tenant_full_name', 'Minimal Test')->first();

        // No contract should be created due to missing required fields
        $contract = RmContract::where('rental_id', $rental->id)->first();
        $this->assertNull($contract);

        // No installments should be created
        $installments = RmPaymentInstallment::where('rental_id', $rental->id)->get();
        $this->assertCount(0, $installments);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // Test missing tenant_full_name
        $response = $this->postJson('/api/v1/rms/rentals', [
            'tenant_phone' => '+966501234567'
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_full_name']);

        // Test missing tenant_phone
        $response = $this->postJson('/api/v1/rms/rentals', [
            'tenant_full_name' => 'Test User'
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_phone']);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $rentalData = [
            'tenant_full_name' => 'Test User',
            'tenant_phone' => '+966501234567',
            'tenant_email' => 'invalid-email-format'
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_email']);
    }

    /** @test */
    public function it_validates_social_status_enum()
    {
        $rentalData = [
            'tenant_full_name' => 'Test User',
            'tenant_phone' => '+966501234567',
            'tenant_social_status' => 'invalid_status'
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_social_status']);
    }

    /** @test */
    public function it_validates_paying_plan_enum()
    {
        $rentalData = [
            'tenant_full_name' => 'Test User',
            'tenant_phone' => '+966501234567',
            'paying_plan' => 'invalid_plan'
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['paying_plan']);
    }

    /** @test */
    public function it_validates_office_commission_type_enum()
    {
        $rentalData = [
            'tenant_full_name' => 'Test User',
            'tenant_phone' => '+966501234567',
            'office_commission_type' => 'invalid_type'
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['office_commission_type']);
    }

    /** @test */
    public function it_calculates_office_fee_correctly_for_percentage_type()
    {
        $rentalData = [
            'tenant_full_name' => 'Percentage Test',
            'tenant_phone' => '+966501234567',
            'base_rent_amount' => 2000.00,
            'rental_period' => 12,
            'office_commission_type' => 'percentage',
            'office_commission_value' => 10.0
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Percentage Test')->first();

        // Expected: (12 * 2000) * (10 / 100) = 24000 * 0.1 = 2400
        $this->assertEquals(2400.00, $rental->office_fee);
    }

    /** @test */
    public function it_calculates_total_rental_amount_correctly()
    {
        $rentalData = [
            'tenant_full_name' => 'Amount Test',
            'tenant_phone' => '+966501234567',
            'base_rent_amount' => 3000.00,
            'rental_period' => 6
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Amount Test')->first();

        // Expected: 3000 * 6 = 18000
        $this->assertEquals(18000.00, $rental->total_rental_amount);
    }

    /** @test */
    public function it_handles_annual_payment_plan_correctly()
    {
        $rentalData = [
            'tenant_full_name' => 'Annual Test',
            'tenant_phone' => '+966501234567',
            'move_in_date' => '2024-01-01',
            'rental_period' => 2, // 2 years
            'paying_plan' => 'annual',
            'base_rent_amount' => 2000.00
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Annual Test')->first();
        $contract = RmContract::where('rental_id', $rental->id)->first();

        // Contract should be 24 months (2 years)
        $this->assertEquals('2024-01-01', $contract->start_date);
        $this->assertEquals('2025-12-31', $contract->end_date);

        // Should have 2 annual installments
        $installments = RmPaymentInstallment::where('rental_id', $rental->id)->get();
        $this->assertCount(2, $installments);

        // Each installment should be 2000 * 12 = 24000
        $firstInstallment = $installments->first();
        $this->assertEquals(24000.00, $firstInstallment->amount);
        $this->assertEquals('2024-01-01', $firstInstallment->due_date->format('Y-m-d'));

        $secondInstallment = $installments->last();
        $this->assertEquals(24000.00, $secondInstallment->amount);
        $this->assertEquals('2025-01-01', $secondInstallment->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_creates_rental_with_semi_annual_payment_plan()
    {
        $rentalData = [
            'tenant_full_name' => 'Semi Annual Test',
            'tenant_phone' => '+966501234567',
            'move_in_date' => '2024-01-01',
            'rental_period' => 4, // 4 semi-annual periods = 24 months
            'paying_plan' => 'semi_annual',
            'base_rent_amount' => 1500.00
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Semi Annual Test')->first();
        $contract = RmContract::where('rental_id', $rental->id)->first();

        // Contract should be 24 months
        $this->assertEquals('2024-01-01', $contract->start_date);
        $this->assertEquals('2025-12-31', $contract->end_date);

        // Should have 4 semi-annual installments
        $installments = RmPaymentInstallment::where('rental_id', $rental->id)->get();
        $this->assertCount(4, $installments);

        // Each installment should be 1500 * 6 = 9000
        $firstInstallment = $installments->first();
        $this->assertEquals(9000.00, $firstInstallment->amount);
        $this->assertEquals('2024-01-01', $firstInstallment->due_date->format('Y-m-d'));

        $secondInstallment = $installments->skip(1)->first();
        $this->assertEquals(9000.00, $secondInstallment->amount);
        $this->assertEquals('2024-07-01', $secondInstallment->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_handles_rental_with_all_fee_types()
    {
        $rentalData = [
            'tenant_full_name' => 'Full Fee Test',
            'tenant_phone' => '+966501234567',
            'unit_id' => $this->property->id,
            'project_id' => $this->project->id,
            'move_in_date' => '2024-01-01',
            'rental_period' => 12,
            'paying_plan' => 'monthly',
            'base_rent_amount' => 2000.00,
            'currency' => 'SAR',
            'deposit_amount' => 2000.00,
            'platform_fee' => 75.00,
            'water_fee' => 25.00,
            'office_commission_type' => 'percentage',
            'office_commission_value' => 3.0,
            'notes' => 'Test with all fee types'
        ];

        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);
        $response->assertStatus(201);

        $rental = RmRental::where('tenant_full_name', 'Full Fee Test')->first();

        // Verify all fees are stored correctly
        $this->assertEquals(75.00, $rental->platform_fee);
        $this->assertEquals(25.00, $rental->water_fee);
        $this->assertEquals('percentage', $rental->office_commission_type);
        $this->assertEquals(3.0, $rental->office_commission_value);

        // Office fee calculation: (12 * 2000) * (3 / 100) = 24000 * 0.03 = 720
        $this->assertEquals(720.00, $rental->office_fee);

        // Total rental amount: 2000 * 12 = 24000
        $this->assertEquals(24000.00, $rental->total_rental_amount);
    }
}
