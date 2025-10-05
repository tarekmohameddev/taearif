<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Rms\RentalController;
use App\Services\Rms\RentalService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class RentalControllerTest extends TestCase
{
    protected $rentalService;
    protected $controller;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rentalService = Mockery::mock(RentalService::class);
        $this->controller = new RentalController($this->rentalService);
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Mock the auth facade
        Auth::shouldReceive('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_constructs_with_rental_service()
    {
        $this->assertInstanceOf(RentalController::class, $this->controller);
    }

    /** @test */
    public function index_returns_json_response_with_rental_list()
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
        $response = $this->controller->index($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /** @test */
    public function store_creates_rental_with_valid_data()
    {
        // Arrange
        $requestData = [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'tenant_email' => 'john@example.com',
            'tenant_job_title' => 'Software Engineer',
            'tenant_social_status' => 'single',
            'tenant_national_id' => '123456789',
            'unit_id' => 1,
            'project_id' => 1,
            'building' => 'PROP-001',
            'move_in_date' => '2024-01-01',
            'rental_type' => 'monthly',
            'rental_duration' => 6,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 12000.00,
            'currency' => 'USD',
            'notes' => 'Test rental'
        ];

        $request = new Request($requestData);
        $request->setUserResolver(function () {
            return $this->user;
        });

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
            ->with(1, $requestData)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertEquals($expectedResponse, $responseData['data']);
    }

    /** @test */
    public function store_validates_required_fields()
    {
        // Arrange
        $request = new Request([]);
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->controller->store($request);
    }

    /** @test */
    public function show_returns_rental_details()
    {
        // Arrange
        $rentalId = 1;
        $expectedRental = (object) [
            'id' => $rentalId,
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890'
        ];

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with(1, $rentalId)
            ->andReturn($expectedRental);

        // Act
        $response = $this->controller->show($rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /** @test */
    public function show_handles_model_not_found_exception()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with(1, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->controller->show($rentalId);
    }

    /** @test */
    public function update_modifies_rental_data()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'tenant_email' => 'jane@example.com'
        ];

        $request = new Request($updateData);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $updatedRental = (object) array_merge(['id' => $rentalId], $updateData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $updateData, false)
            ->andReturn($updatedRental);

        // Act
        $response = $this->controller->update($request, $rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /** @test */
    public function update_handles_regenerate_schedule_flag()
    {
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'total_rental_amount' => 14400.00
        ];

        $request = new Request(array_merge($updateData, ['regenerate_schedule' => true]));
        $request->setUserResolver(function () {
            return $this->user;
        });

        $updatedRental = (object) array_merge(['id' => $rentalId], $updateData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $updateData, true)
            ->andReturn($updatedRental);

        // Act
        $response = $this->controller->update($request, $rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function update_handles_model_not_found_exception()
    {
        // Arrange
        $rentalId = 999;
        $updateData = ['tenant_full_name' => 'Jane Doe'];

        $request = new Request($updateData);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $updateData, false)
            ->andThrow(new ModelNotFoundException());

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->controller->update($request, $rentalId);
    }

    /** @test */
    public function destroy_deletes_rental()
    {
        // Arrange
        $rentalId = 1;

        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with(1, $rentalId);

        // Act
        $response = $this->controller->destroy($rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function destroy_handles_model_not_found_exception()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with(1, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->controller->destroy($rentalId);
    }

    /** @test */
    public function property_details_returns_property_information()
    {
        // Arrange
        $rentalId = 1;
        $expectedDetails = [
            'rental' => [
                'id' => $rentalId,
                'tenant_full_name' => 'John Doe',
                'tenant_phone' => '+1234567890'
            ],
            'property' => [
                'id' => 1,
                'name' => 'Test Property',
            ],
            'contract' => [
                'id' => 1,
                'contract_number' => 'CNT-2024-00001',
                'status' => 'active'
            ],
            'payment_details' => [
                'items' => []
            ]
        ];

        $this->rentalService
            ->shouldReceive('getPropertyDetails')
            ->once()
            ->with(1, $rentalId)
            ->andReturn($expectedDetails);

        // Act
        $response = $this->controller->propertyDetails($rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertEquals($expectedDetails, $responseData['data']);
    }

    /** @test */
    public function property_details_handles_model_not_found_exception()
    {
        // Arrange
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('getPropertyDetails')
            ->once()
            ->with(1, $rentalId)
            ->andThrow(new ModelNotFoundException());

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->controller->propertyDetails($rentalId);
    }

    /** @test */
    public function it_filters_allowed_fields_in_update()
    {
        // Arrange
        $rentalId = 1;
        $requestData = [
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'tenant_email' => 'jane@example.com',
            'tenant_job_title' => 'Manager',
            'tenant_social_status' => 'married',
            'tenant_national_id' => '987654321',
            'unit_id' => 2,
            'project_id' => 2,
            'building' => 'PROP-002',
            'move_in_date' => '2024-02-01',
            'rental_type' => 'annual',
            'rental_duration' => 2,
            'paying_plan' => 'quarterly',
            'total_rental_amount' => 36000.00,
            'currency' => 'EUR',
            'notes' => 'Updated rental',
            'unauthorized_field' => 'should_be_ignored'
        ];

        $request = new Request($requestData);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $expectedFilteredData = [
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'tenant_email' => 'jane@example.com',
            'tenant_job_title' => 'Manager',
            'tenant_social_status' => 'married',
            'tenant_national_id' => '987654321',
            'unit_id' => 2,
            'project_id' => 2,
            'building' => 'PROP-002',
            'move_in_date' => '2024-02-01',
            'rental_type' => 'annual',
            'rental_duration' => 2,
            'paying_plan' => 'quarterly',
            'total_rental_amount' => 36000.00,
            'currency' => 'EUR',
            'notes' => 'Updated rental'
        ];

        $updatedRental = (object) array_merge(['id' => $rentalId], $expectedFilteredData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $expectedFilteredData, false)
            ->andReturn($updatedRental);

        // Act
        $response = $this->controller->update($request, $rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
