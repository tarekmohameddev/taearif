<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\Rms\ContractController;
use App\Services\Rms\ContractService;
use App\Models\Api\Rms\RmContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $contractService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->contractService = Mockery::mock(ContractService::class);
        $this->app->instance(ContractService::class, $this->contractService);
        
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
    public function it_can_list_contracts_by_rental()
    {
        // Arrange
        $rentalId = 1;
        $expectedContracts = collect([
            new RmContract([
                'id' => 1,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'active'
            ])
        ]);

        // Act
        $response = $this->getJson("/api/v1/rms/rentals/{$rentalId}/contracts");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => []
                ]);
    }

    /** @test */
    public function it_can_create_contract_with_valid_data()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'file_path' => '/path/to/contract.pdf',
            'generate_schedule' => true,
            'unit_id' => 1,
            'project_id' => 1,
            'property_name' => 'Test Property',
            'project_name' => 'Test Project',
            'grace_period_months' => 1
        ];

        $expectedContract = new RmContract(array_merge(['id' => 1], $contractData));

        $this->contractService
            ->shouldReceive('createContract')
            ->once()
            ->with($rentalId, $contractData, $this->user->id)
            ->andReturn($expectedContract);

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'data' => $expectedContract->toArray()
                ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_contract()
    {
        // Arrange
        $rentalId = 1;

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'start_date',
                    'end_date',
                    'status'
                ]);
    }

    /** @test */
    public function it_validates_end_date_after_start_date()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01', // End date before start date
            'status' => 'active'
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_validates_status_enum()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'invalid_status'
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }


    /** @test */
    public function it_validates_grace_period_months_range()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'grace_period_months' => 5 // Exceeds max:2
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['grace_period_months']);
    }

    /** @test */
    public function it_does_not_accept_platform_fee_field()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'platform_fee' => 50.00 // This field should be rejected
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform_fee']);
    }

    /** @test */
    public function it_does_not_accept_water_fee_monthly_field()
    {
        // Arrange
        $rentalId = 1;
        $contractData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'water_fee_monthly' => 30.00 // This field should be rejected
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['water_fee_monthly']);
    }

    /** @test */
    public function it_can_update_contract()
    {
        // Arrange
        $contractId = 1;
        $updateData = [
            'status' => 'expired',
            'file_path' => '/new/path/to/contract.pdf'
        ];

        $updatedContract = new RmContract(array_merge(['id' => $contractId], $updateData));

        $this->contractService
            ->shouldReceive('updateContract')
            ->once()
            ->with($contractId, $updateData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_validates_update_status_enum()
    {
        // Arrange
        $contractId = 1;
        $updateData = [
            'status' => 'invalid_status'
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}", $updateData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_does_not_accept_platform_fee_in_update()
    {
        // Arrange
        $contractId = 1;
        $updateData = [
            'platform_fee' => 75.00 // This field should be rejected
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}", $updateData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform_fee']);
    }

    /** @test */
    public function it_does_not_accept_water_fee_monthly_in_update()
    {
        // Arrange
        $contractId = 1;
        $updateData = [
            'water_fee_monthly' => 40.00 // This field should be rejected
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}", $updateData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['water_fee_monthly']);
    }

    /** @test */
    public function it_can_terminate_contract()
    {
        // Arrange
        $contractId = 1;
        $terminationData = [
            'termination_reason' => 'Tenant requested early termination',
            'terminate_on' => '2024-06-30'
        ];

        $terminatedContract = new RmContract(array_merge(['id' => $contractId], $terminationData));

        $this->contractService
            ->shouldReceive('terminateContract')
            ->once()
            ->with($contractId, $terminationData, $this->user->id)
            ->andReturn($terminatedContract);

        // Act
        $response = $this->postJson("/api/v1/rms/contracts/{$contractId}/terminate", $terminationData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $terminatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_validates_termination_required_fields()
    {
        // Arrange
        $contractId = 1;

        // Act
        $response = $this->postJson("/api/v1/rms/contracts/{$contractId}/terminate", []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'termination_reason',
                    'terminate_on'
                ]);
    }

    /** @test */
    public function it_validates_termination_reason_length()
    {
        // Arrange
        $contractId = 1;
        $terminationData = [
            'termination_reason' => str_repeat('a', 256), // Exceeds max:255
            'terminate_on' => '2024-06-30'
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/contracts/{$contractId}/terminate", $terminationData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['termination_reason']);
    }

    /** @test */
    public function it_validates_terminate_on_date_format()
    {
        // Arrange
        $contractId = 1;
        $terminationData = [
            'termination_reason' => 'Valid reason',
            'terminate_on' => 'invalid-date'
        ];

        // Act
        $response = $this->postJson("/api/v1/rms/contracts/{$contractId}/terminate", $terminationData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['terminate_on']);
    }


    /** @test */
    public function it_accepts_valid_grace_period_values()
    {
        // Arrange
        $rentalId = 1;
        $validGracePeriods = [0, 1, 2];

        foreach ($validGracePeriods as $gracePeriod) {
            $contractData = [
                'contract_number' => 'CNT-2024-00001',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'active',
                'grace_period_months' => $gracePeriod
            ];

            $expectedContract = new RmContract(array_merge(['id' => 1], $contractData));

            $this->contractService
                ->shouldReceive('createContract')
                ->once()
                ->with($rentalId, $contractData, $this->user->id)
                ->andReturn($expectedContract);

            // Act
            $response = $this->postJson("/api/v1/rms/rentals/{$rentalId}/contracts", $contractData);

            // Assert
            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_can_change_contract_status_to_active()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'active',
            'reason' => 'Contract approved and activated'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'active',
            'termination_reason' => 'Contract approved and activated'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_can_change_contract_status_to_expired()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'expired',
            'reason' => 'Contract has reached its end date'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'expired',
            'termination_reason' => 'Contract has reached its end date'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_can_change_contract_status_to_terminated()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'terminated',
            'reason' => 'Early termination requested by tenant',
            'effective_date' => '2024-06-30'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'terminated',
            'termination_reason' => 'Early termination requested by tenant',
            'end_date' => '2024-06-30'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_can_change_contract_status_to_pending()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'pending',
            'reason' => 'Contract under review'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'pending',
            'termination_reason' => 'Contract under review'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_validates_required_status_field()
    {
        // Arrange
        $contractId = 1;

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_validates_status_enum_values()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'invalid_status'
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_validates_reason_length()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'active',
            'reason' => str_repeat('a', 256) // Exceeds max:255
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function it_validates_effective_date_format()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'terminated',
            'effective_date' => 'invalid-date'
        ];

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['effective_date']);
    }

    /** @test */
    public function it_accepts_minimal_status_change_data()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'active'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'active'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_handles_contract_not_found()
    {
        // Arrange
        $contractId = 999;
        $statusData = [
            'status' => 'active'
        ];

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_change_contract_status_from_terminated_to_active()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'active',
            'reason' => 'Contract reactivated after review'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'active',
            'termination_reason' => 'Contract reactivated after review'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }

    /** @test */
    public function it_can_change_contract_status_from_active_to_pending()
    {
        // Arrange
        $contractId = 1;
        $statusData = [
            'status' => 'pending',
            'reason' => 'Contract under review - temporarily suspended'
        ];

        $updatedContract = new RmContract([
            'id' => $contractId,
            'status' => 'pending',
            'termination_reason' => 'Contract under review - temporarily suspended'
        ]);

        $this->contractService
            ->shouldReceive('changeContractStatus')
            ->once()
            ->with($contractId, $statusData, $this->user->id)
            ->andReturn($updatedContract);

        // Act
        $response = $this->patchJson("/api/v1/rms/contracts/{$contractId}/status", $statusData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'data' => $updatedContract->toArray()
                ]);
    }
}
