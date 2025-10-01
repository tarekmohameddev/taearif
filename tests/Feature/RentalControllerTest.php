<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\Rms\RentalController;
use App\Services\Rms\RentalService;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

class RentalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $rentalService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rentalService = Mockery::mock(RentalService::class);
        $this->app->instance(RentalService::class, $this->rentalService);
        
        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_list_rentals()
    {
        // Arrange
        $request = new Request();
        $expectedData = new LengthAwarePaginator([], 0, 15);
        
        $this->rentalService
            ->shouldReceive('listRentals')
            ->once()
            ->with($request)
            ->andReturn($expectedData);

        // Act
        $response = $this->getJson('/api/v1/rms/rentals');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => []
                ]);
    }

    /** @test */
    public function it_can_list_rentals_with_filters()
    {
        // Arrange
        $request = new Request([
            'q' => 'John Doe',
            'status' => 'active',
            'unit_id' => 1,
            'project_id' => 1,
            'paying_plan' => 'monthly'
        ]);
        
        $expectedData = new LengthAwarePaginator([], 0, 15);
        
        $this->rentalService
            ->shouldReceive('listRentals')
            ->once()
            ->with(Mockery::on(function ($req) {
                return $req->q === 'John Doe' 
                    && $req->status === 'active'
                    && $req->unit_id === 1
                    && $req->project_id === 1
                    && $req->paying_plan === 'monthly';
            }))
            ->andReturn($expectedData);

        // Act
        $response = $this->getJson('/api/v1/rms/rentals?q=John%20Doe&status=active&unit_id=1&project_id=1&paying_plan=monthly');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => []
                ]);
    }

    /** @test */
    public function it_can_create_a_rental_with_valid_data()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'tenant_email' => 'john@example.com',
            'tenant_job_title' => 'Software Engineer',
            'tenant_social_status' => 'single',
            'tenant_national_id' => '123456789',
            'unit_id' => 1,
            'project_id' => 1,
            'unit_label' => 'A-101',
            'building' => 'PROP-001',
            'move_in_date' => '2024-01-01',
            'rental_period' => 12,
            'paying_plan' => 'monthly',
            'base_rent_amount' => 1000.00,
            'currency' => 'USD',
            'deposit_amount' => 1000.00,
            'platform_fee' => 50.00,
            'water_fee' => 30.00,
            'office_commission_type' => 'percentage',
            'office_commission_value' => 5.0,
            'contract_number' => 'CNT-2024-001',
            'notes' => 'Test rental'
        ];

        $expectedResponse = [
            'id' => 1,
            'status' => 'active',
            'contract' => [
                'id' => 1,
                'status' => 'pending'
            ]
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'data' => $expectedResponse
                ]);
    }

    /** @test */
    public function it_can_create_a_rental_with_minimal_data()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890'
        ];

        $expectedResponse = [
            'id' => 1,
            'status' => 'active'
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'data' => $expectedResponse
                ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_rental()
    {
        // Act
        $response = $this->postJson('/api/v1/rms/rentals', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_full_name', 'tenant_phone']);
    }

    /** @test */
    public function it_validates_email_format_when_creating_rental()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'tenant_email' => 'invalid-email'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_email']);
    }

    /** @test */
    public function it_validates_social_status_enum_when_creating_rental()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'tenant_social_status' => 'invalid_status'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tenant_social_status']);
    }

    /** @test */
    public function it_validates_paying_plan_enum_when_creating_rental()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'paying_plan' => 'invalid_plan'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['paying_plan']);
    }

    /** @test */
    public function it_validates_currency_size_when_creating_rental()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'currency' => 'US'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['currency']);
    }

    /** @test */
    public function it_can_show_rental_details()
    {
        // Arrange
        $rentalId = 1;
        $expectedRental = new RmRental([
            'id' => $rentalId,
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890'
        ]);

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andReturn($expectedRental);

        // Act
        $response = $this->getJson("/api/v1/rms/rentals/{$rentalId}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $expectedRental->toArray()
                ]);
    }

    /** @test */
    public function it_returns_404_when_rental_not_found()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act
        $response = $this->getJson("/api/v1/rms/rentals/{$rentalId}");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_rental()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'tenant_email' => 'jane@example.com'
        ];

        $updatedRental = new RmRental(array_merge(['id' => $rentalId], $updateData));

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with($this->user->id, $rentalId, $updateData, false)
            ->andReturn($updatedRental);

        // Act
        $response = $this->putJson("/api/v1/rms/rentals/{$rentalId}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedRental->toArray()
                ]);
    }

    /** @test */
    public function it_can_update_rental_with_regenerate_schedule()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'base_rent_amount' => 1200.00
        ];

        $updatedRental = new RmRental(array_merge(['id' => $rentalId], $updateData));

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with($this->user->id, $rentalId, $updateData, true)
            ->andReturn($updatedRental);

        // Act
        $response = $this->putJson("/api/v1/rms/rentals/{$rentalId}", array_merge($updateData, [
            'regenerate_schedule' => true
        ]));

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedRental->toArray()
                ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_rental()
    {
        // Arrange
        $rentalId = 999;
        $updateData = ['tenant_full_name' => 'Jane Doe'];

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with($this->user->id, $rentalId, $updateData, false)
            ->andThrow(new ModelNotFoundException());

        // Act
        $response = $this->putJson("/api/v1/rms/rentals/{$rentalId}", $updateData);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_rental()
    {
        // Arrange
        $rentalId = 1;

        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with($this->user->id, $rentalId);

        // Act
        $response = $this->deleteJson("/api/v1/rms/rentals/{$rentalId}");

        // Assert
        $response->assertStatus(204);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_rental()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act
        $response = $this->deleteJson("/api/v1/rms/rentals/{$rentalId}");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_prevents_deletion_of_rental_with_active_contract()
    {
        // Arrange
        $rentalId = 1;

        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andThrow(new \Exception('Cannot delete rental with active contract'));

        // Act
        $response = $this->deleteJson("/api/v1/rms/rentals/{$rentalId}");

        // Assert
        $response->assertStatus(500);
    }

    /** @test */
    public function it_can_get_property_details()
    {
        // Arrange
        $rentalId = 1;
        $expectedDetails = [
            'rental' => [
                'id' => $rentalId,
                'tenant_full_name' => 'John Doe',
                'tenant_phone' => '+1234567890',
                'base_rent_amount' => 1000.0,
                'platform_fee' => 50.0,
                'water_fee' => 30.0,
                'office_commission_type' => 'percentage',
                'office_commission_value' => 5.0,
                'office_fee' => 600.0, // 12 months * 1000 * 5% = 600
                'contract_number' => 'CNT-2024-001',
                'total_rental_amount' => 12000.0, // 12 periods * 1000 = 12000
                'currency' => 'USD'
            ],
            'property' => [
                'id' => 1,
                'name' => 'Test Property',
                'unit_label' => 'A-101',
                'building' => 'PROP-001',
                'project' => [
                    'id' => 1,
                    'name' => 'Test Project'
                ]
            ],
            'contract' => [
                'id' => 1,
                'contract_number' => 'CNT-2024-00001',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'active'
            ],
            'payment_details' => [
                'items' => []
            ]
        ];

        $this->rentalService
            ->shouldReceive('getPropertyDetails')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andReturn($expectedDetails);

        // Act
        $response = $this->getJson("/api/v1/rms/rentals/{$rentalId}/property-details");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $expectedDetails
                ]);
    }

    /** @test */
    public function it_returns_404_when_property_details_not_found()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('getPropertyDetails')
            ->once()
            ->with($this->user->id, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act
        $response = $this->getJson("/api/v1/rms/rentals/{$rentalId}/property-details");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_string_length_limits()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => str_repeat('a', 151), // Exceeds max:150
            'tenant_phone' => str_repeat('1', 33), // Exceeds max:32
            'tenant_job_title' => str_repeat('b', 121), // Exceeds max:120
            'tenant_national_id' => str_repeat('1', 21), // Exceeds max:20
            'unit_label' => str_repeat('c', 101), // Exceeds max:100
            'building' => str_repeat('d', 101), // Exceeds max:100
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'tenant_full_name',
                    'tenant_phone',
                    'tenant_job_title',
                    'tenant_national_id',
                    'unit_label',
                    'building'
                ]);
    }

    /** @test */
    public function it_validates_numeric_fields()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'unit_id' => 'not_a_number',
            'project_id' => 'not_a_number',
            'rental_period' => 'not_a_number',
            'base_rent_amount' => 'not_a_number',
            'deposit_amount' => 'not_a_number'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'unit_id',
                    'project_id',
                    'rental_period',
                    'base_rent_amount',
                    'deposit_amount'
                ]);
    }

    /** @test */
    public function it_validates_date_format()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'move_in_date' => 'invalid-date'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['move_in_date']);
    }

    /** @test */
    public function it_accepts_valid_enum_values()
    {
        // Arrange
        $validSocialStatuses = ['single', 'married', 'divorced', 'widowed', 'other'];
        $validPayingPlans = ['monthly', 'quarterly', 'semi_annual', 'annual'];

        foreach ($validSocialStatuses as $status) {
            foreach ($validPayingPlans as $plan) {
                $rentalData = [
                    'tenant_full_name' => 'John Doe',
                    'tenant_phone' => '+1234567890',
                    'tenant_social_status' => $status,
                    'paying_plan' => $plan
                ];

                $this->rentalService
                    ->shouldReceive('createRental')
                    ->once()
                    ->with($this->user->id, $rentalData)
                    ->andReturn(['id' => 1, 'status' => 'active']);

                // Act
                $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

                // Assert
                $response->assertStatus(201);
            }
        }
    }

    /** @test */
    public function it_validates_platform_fee_field()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'platform_fee' => -10.00 // Invalid negative value
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform_fee']);
    }

    /** @test */
    public function it_validates_water_fee_field()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'water_fee' => -5.00 // Invalid negative value
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['water_fee']);
    }

    /** @test */
    public function it_accepts_valid_platform_fee_and_water_fee_values()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'platform_fee' => 50.00,
            'water_fee' => 30.00
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_validates_office_commission_type_field()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'office_commission_type' => 'invalid_type'
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['office_commission_type']);
    }

    /** @test */
    public function it_validates_office_commission_value_field()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'office_commission_value' => -10.00 // Invalid negative value
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['office_commission_value']);
    }

    /** @test */
    public function it_accepts_valid_office_commission_values()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'office_commission_type' => 'percentage',
            'office_commission_value' => 5.0
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_accepts_null_office_commission_values()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'office_commission_type' => null,
            'office_commission_value' => null
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_accepts_null_platform_fee_and_water_fee_values()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'platform_fee' => null,
            'water_fee' => null
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_update_rental_with_platform_fee_and_water_fee()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'platform_fee' => 75.00,
            'water_fee' => 40.00
        ];

        $updatedRental = new RmRental(array_merge(['id' => $rentalId], $updateData));

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with($this->user->id, $rentalId, $updateData, false)
            ->andReturn($updatedRental);

        // Act
        $response = $this->putJson("/api/v1/rms/rentals/{$rentalId}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedRental->toArray()
                ]);
    }

    /** @test */
    public function it_can_update_rental_with_office_commission_fields()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'office_commission_type' => 'amount',
            'office_commission_value' => 500.00
        ];

        $updatedRental = new RmRental(array_merge(['id' => $rentalId], $updateData));

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with($this->user->id, $rentalId, $updateData, false)
            ->andReturn($updatedRental);

        // Act
        $response = $this->putJson("/api/v1/rms/rentals/{$rentalId}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedRental->toArray()
                ]);
    }

    /** @test */
    public function it_calculates_office_fee_correctly_for_percentage_commission()
    {
        // Arrange
        $rental = new RmRental([
            'office_commission_type' => 'percentage',
            'office_commission_value' => 5.0,
            'rental_period' => 12,
            'base_rent_amount' => 1000.00
        ]);

        // Act & Assert
        // Expected: (12 * 1000) * (5 / 100) = 12000 * 0.05 = 600
        $this->assertEquals(600.0, $rental->office_fee);
    }

    /** @test */
    public function it_calculates_office_fee_correctly_for_amount_commission()
    {
        // Arrange
        $rental = new RmRental([
            'office_commission_type' => 'amount',
            'office_commission_value' => 500.00,
            'rental_period' => 12,
            'base_rent_amount' => 1000.00
        ]);

        // Act & Assert
        // Expected: 500.00 (direct amount)
        $this->assertEquals(500.0, $rental->office_fee);
    }

    /** @test */
    public function it_returns_zero_office_fee_when_required_fields_are_null()
    {
        // Arrange
        $rental = new RmRental([
            'office_commission_type' => null,
            'office_commission_value' => 5.0,
            'rental_period' => 12,
            'base_rent_amount' => 1000.00
        ]);

        // Act & Assert
        $this->assertEquals(0.0, $rental->office_fee);
    }

    /** @test */
    public function it_validates_contract_number_field()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'contract_number' => str_repeat('a', 256) // Exceeds max:255
        ];

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['contract_number']);
    }

    /** @test */
    public function it_accepts_valid_contract_number()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'contract_number' => 'CNT-2024-001'
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_accepts_null_contract_number()
    {
        // Arrange
        $rentalData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'contract_number' => null
        ];

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with($this->user->id, $rentalData)
            ->andReturn(['id' => 1, 'status' => 'active']);

        // Act
        $response = $this->postJson('/api/v1/rms/rentals', $rentalData);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function it_calculates_total_rental_amount_correctly_for_monthly_payment()
    {
        // Arrange
        $rental = new RmRental([
            'base_rent_amount' => 1000.00,
            'rental_period' => 12,
            'paying_plan' => 'monthly'
        ]);

        // Act & Assert
        // Expected: 1000 * 12 = 12000 (12 monthly payments)
        $this->assertEquals(12000.0, $rental->total_rental_amount);
    }

    /** @test */
    public function it_calculates_total_rental_amount_correctly_for_quarterly_payment()
    {
        // Arrange
        $rental = new RmRental([
            'base_rent_amount' => 1000.00,
            'rental_period' => 4,
            'paying_plan' => 'quarterly'
        ]);

        // Act & Assert
        // Expected: 1000 * 4 = 4000 (4 quarterly payments)
        $this->assertEquals(4000.0, $rental->total_rental_amount);
    }

    /** @test */
    public function it_calculates_total_rental_amount_correctly_for_semi_annual_payment()
    {
        // Arrange
        $rental = new RmRental([
            'base_rent_amount' => 1000.00,
            'rental_period' => 2,
            'paying_plan' => 'semi_annual'
        ]);

        // Act & Assert
        // Expected: 1000 * 2 = 2000 (2 semi-annual payments)
        $this->assertEquals(2000.0, $rental->total_rental_amount);
    }

    /** @test */
    public function it_calculates_total_rental_amount_correctly_for_annual_payment()
    {
        // Arrange
        $rental = new RmRental([
            'base_rent_amount' => 1000.00,
            'rental_period' => 1,
            'paying_plan' => 'annual'
        ]);

        // Act & Assert
        // Expected: 1000 * 1 = 1000 (1 annual payment)
        $this->assertEquals(1000.0, $rental->total_rental_amount);
    }

    /** @test */
    public function it_returns_zero_total_rental_amount_when_required_fields_are_null()
    {
        // Arrange
        $rental = new RmRental([
            'base_rent_amount' => null,
            'rental_period' => 12,
            'paying_plan' => 'monthly'
        ]);

        // Act & Assert
        $this->assertEquals(0.0, $rental->total_rental_amount);
    }
}
