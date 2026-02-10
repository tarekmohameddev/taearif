<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Rms\RentalController;
use App\Http\Requests\Rms\Rental\ListRentalsRequest;
use App\Http\Requests\Rms\Rental\StoreRentalRequest;
use App\Http\Requests\Rms\Rental\UpdateRentalRequest;
use App\Services\Rms\RentalService;
use App\Services\Rms\PaymentService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

/** Minimal Authenticatable for tests; getUserId() uses tenantOwnerId() when user is set */
class StubAuthenticatable implements \Illuminate\Contracts\Auth\Authenticatable
{
    public function getAuthIdentifierName() { return 'id'; }
    public function getAuthIdentifier() { return 1; }
    public function getAuthPassword() { return ''; }
    public function getRememberToken() { return null; }
    public function setRememberToken($value) {}
    public function getRememberTokenName() { return null; }
    public function tenantOwnerId() { return 1; }
}

class RentalControllerTest extends TestCase
{
    protected $rentalService;
    protected $paymentService;
    protected $controller;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rentalService = Mockery::mock(RentalService::class);
        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->controller = Mockery::mock(RentalController::class, [$this->rentalService, $this->paymentService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->controller->shouldReceive('getUserId')->andReturn(1);
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Use real auth guard so destroy() and actingAs() work (Auth facade not mocked)
        $this->actingAs(new StubAuthenticatable());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Stub for RentalResource (needs relationLoaded + all fields toArray reads) */
    private function rentalStub(int $id, array $data = []): object
    {
        $defaults = [
            'id' => $id,
            'user_id' => 1,
            'tenant_full_name' => null,
            'tenant_phone' => null,
            'tenant_email' => null,
            'tenant_job_title' => null,
            'tenant_social_status' => null,
            'tenant_national_id' => null,
            'unit_id' => null,
            'project_id' => null,
            'building_id' => null,
            'move_in_date' => null,
            'rental_type' => null,
            'rental_duration' => null,
            'paying_plan' => null,
            'total_rental_amount' => null,
            'base_rent_amount' => null,
            'currency' => null,
            'contract_number' => null,
            'notes' => null,
            'status' => null,
            'end_date' => null,
            'termination_reason' => null,
            'next_payment_due_date' => null,
            'next_payment_amount' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
        return new class(array_merge($defaults, $data)) {
            public $id, $user_id, $tenant_full_name, $tenant_phone, $tenant_email, $tenant_job_title;
            public $tenant_social_status, $tenant_national_id, $unit_id, $project_id, $building_id;
            public $move_in_date, $rental_type, $rental_duration, $paying_plan, $total_rental_amount;
            public $base_rent_amount, $currency, $contract_number, $notes, $status, $end_date;
            public $termination_reason, $next_payment_due_date, $next_payment_amount, $created_at, $updated_at;
            public function __construct(array $attrs) {
                foreach ($attrs as $k => $v) { $this->$k = $v; }
            }
            public function relationLoaded($r) { return false; }
        };
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
        $request = ListRentalsRequest::create('/api/rentals', 'GET', []);
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
        $this->markTestSkipped('RentalResource/ResolvesLocalizedNames hits DB (user_languages etc.); requires MySQL test DB with tables.');
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
            'building_id' => 1,
            'move_in_date' => '2024-01-01',
            'rental_type' => 'monthly',
            'rental_duration' => 6,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 12000.00,
            'currency' => 'USD',
            'notes' => 'Test rental'
        ];

        $request = StoreRentalRequest::create('/api/rentals', 'POST', $requestData);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);
        $request->validateResolved();
        $validated = $request->validated();

        // Service must return object with relationLoaded() for RentalResource
        $expectedResponse = new class {
            public $id = 1;
            public $user_id = 1;
            public $status = 'active';
            public $tenant_full_name = 'John Doe';
            public $tenant_phone = '+1234567890';
            public $tenant_email = null;
            public $tenant_job_title = null;
            public $tenant_social_status = null;
            public $tenant_national_id = null;
            public $unit_id = null;
            public $project_id = null;
            public $building_id = null;
            public $contract = ['id' => 1, 'status' => 'pending'];
            public function relationLoaded($r) { return false; }
        };

        $this->rentalService
            ->shouldReceive('createRental')
            ->once()
            ->with(1, $validated)
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(1, $responseData['data']['id'] ?? null);
    }

    /** @test */
    public function store_validates_required_fields()
    {
        // Arrange: empty data fails StoreRentalRequest validation
        $request = StoreRentalRequest::create('/api/rentals', 'POST', []);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);

        $this->expectException(ValidationException::class);
        $request->validateResolved();
    }

    /** @test */
    public function show_returns_rental_details()
    {
        $this->markTestSkipped('RentalResource/ResolvesLocalizedNames hits DB; requires MySQL test DB with tables.');
        // Arrange: stub with fields RentalResource reads and relationLoaded()
        $rentalId = 1;
        $expectedRental = $this->rentalStub($rentalId, [
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
        ]);

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

        // Act: controller catches ModelNotFoundException and returns 404
        $response = $this->controller->show($rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function update_modifies_rental_data()
    {
        $this->markTestSkipped('RentalResource/ResolvesLocalizedNames hits DB; requires MySQL test DB with tables.');
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'tenant_email' => 'jane@example.com'
        ];

        $request = UpdateRentalRequest::create("/api/rentals/{$rentalId}", 'PUT', $updateData);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);
        $request->validateResolved();
        $validated = $request->validated();

        $updatedRental = $this->rentalStub($rentalId, $updateData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $validated, false)
            ->andReturn($updatedRental);

        $response = $this->controller->update($request, $rentalId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
    }

    /** @test */
    public function update_handles_regenerate_schedule_flag()
    {
        $this->markTestSkipped('RentalResource/ResolvesLocalizedNames hits DB; requires MySQL test DB with tables.');
        // Arrange
        $rentalId = 1;
        $updateData = [
            'tenant_full_name' => 'Jane Doe',
            'total_rental_amount' => 14400.00,
            'regenerate_schedule' => true
        ];

        $request = UpdateRentalRequest::create("/api/rentals/{$rentalId}", 'PUT', $updateData);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);
        $request->validateResolved();
        $validated = $request->validated();

        $updatedRental = $this->rentalStub($rentalId, $updateData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $validated, true)
            ->andReturn($updatedRental);

        $response = $this->controller->update($request, $rentalId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function update_handles_model_not_found_exception()
    {
        // Arrange
        $rentalId = 999;
        $updateData = ['tenant_full_name' => 'Jane Doe'];

        $request = UpdateRentalRequest::create("/api/rentals/{$rentalId}", 'PUT', $updateData);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);
        $request->validateResolved();
        $validated = $request->validated();

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $validated, false)
            ->andThrow(new ModelNotFoundException());

        // Act: controller catches ModelNotFoundException and returns 404
        $response = $this->controller->update($request, $rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function destroy_deletes_rental()
    {
        // Arrange: destroy() calls getRentalDetails first, then deleteRental, returns 200
        $rentalId = 1;
        $rental = (object) [
            'id' => $rentalId,
            'tenant_full_name' => 'John Doe',
            'property' => null,
        ];

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with(1, $rentalId)
            ->andReturn($rental);
        $this->rentalService
            ->shouldReceive('deleteRental')
            ->once()
            ->with(1, $rentalId);

        $response = $this->controller->destroy($rentalId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function destroy_handles_model_not_found_exception()
    {
        // Arrange: destroy() calls getRentalDetails first; throw there
        $rentalId = 999;

        $this->rentalService
            ->shouldReceive('getRentalDetails')
            ->once()
            ->with(1, $rentalId)
            ->andThrow(new ModelNotFoundException());

        $response = $this->controller->destroy($rentalId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
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

        // Act: controller catches ModelNotFoundException and returns 404
        $response = $this->controller->propertyDetails($rentalId);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function it_filters_allowed_fields_in_update()
    {
        $this->markTestSkipped('RentalResource/ResolvesLocalizedNames hits DB; requires MySQL test DB with tables.');
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
            'building_id' => 2,
            'move_in_date' => '2024-02-01',
            'rental_type' => 'annual',
            'rental_duration' => 2,
            'paying_plan' => 'quarterly',
            'total_rental_amount' => 36000.00,
            'currency' => 'EUR',
            'notes' => 'Updated rental',
            'unauthorized_field' => 'should_be_ignored'
        ];

        $request = UpdateRentalRequest::create("/api/rentals/{$rentalId}", 'PUT', $requestData);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->setUserResolver(fn () => $this->user);
        $request->validateResolved();
        $expectedFilteredData = $request->validated();

        $updatedRental = $this->rentalStub($rentalId, $expectedFilteredData);

        $this->rentalService
            ->shouldReceive('updateRental')
            ->once()
            ->with(1, $rentalId, $expectedFilteredData, false)
            ->andReturn($updatedRental);

        $response = $this->controller->update($request, $rentalId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
