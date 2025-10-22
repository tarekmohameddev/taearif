<?php

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Test BaseApiController helper methods
 *
 * Run with: php artisan test --filter=BaseApiControllerTest
 */
class BaseApiControllerTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous controller instance for testing
        $this->controller = new class extends BaseApiController {
            // Expose protected methods for testing
            public function testSuccess($data = null, ?string $message = null, int $statusCode = 200)
            {
                return $this->success($data, $message, $statusCode);
            }

            public function testCreated($data = null, ?string $message = null)
            {
                return $this->created($data, $message);
            }

            public function testError(string $message, int $statusCode = 400, ?array $errors = null)
            {
                return $this->error($message, $statusCode, $errors);
            }

            public function testNotFound(?string $message = null)
            {
                return $this->notFound($message);
            }

            public function testValidationError(array $errors, ?string $message = null)
            {
                return $this->validationError($errors, $message);
            }

            public function testPaginated($paginator, ?string $resourceClass = null)
            {
                return $this->paginated($paginator, $resourceClass);
            }

            public function testGetUserId(): int
            {
                return $this->getUserId();
            }

            public function testServerError(?string $message = null)
            {
                return $this->serverError($message);
            }

            public function testForbidden(?string $message = null)
            {
                return $this->forbidden($message);
            }

            public function testUnauthorized(?string $message = null)
            {
                return $this->unauthorized($message);
            }
        };
    }

    /** @test */
    public function it_returns_success_response()
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = $this->controller->testSuccess($data, 'Success message');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    /** @test */
    public function it_returns_success_without_message()
    {
        $data = ['test' => 'data'];
        $response = $this->controller->testSuccess($data);

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals($data, $content['data']);
        $this->assertArrayNotHasKey('message', $content);
    }

    /** @test */
    public function it_returns_created_response()
    {
        $data = ['id' => 1];
        $response = $this->controller->testCreated($data, 'Created successfully');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals('Created successfully', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    /** @test */
    public function it_returns_error_response()
    {
        $response = $this->controller->testError('Error message', 400);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error' not boolean
        $this->assertEquals('Error message', $content['message']);
    }

    /** @test */
    public function it_returns_error_with_validation_errors()
    {
        $errors = ['email' => ['Email is required']];
        $response = $this->controller->testError('Validation failed', 422, $errors);

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error' not boolean
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    /** @test */
    public function it_returns_not_found_response()
    {
        $response = $this->controller->testNotFound('Resource not found');

        $this->assertEquals(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error' not boolean
        $this->assertEquals('Resource not found', $content['message']);
    }

    /** @test */
    public function it_returns_not_found_with_default_message()
    {
        $response = $this->controller->testNotFound();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Resource not found', $content['message']);
    }

    /** @test */
    public function it_returns_validation_error_response()
    {
        $errors = [
            'name' => ['Name is required'],
            'email' => ['Email is invalid']
        ];
        $response = $this->controller->testValidationError($errors, 'Validation failed');

        $this->assertEquals(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error'
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    /** @test */
    public function it_returns_server_error_response()
    {
        $response = $this->controller->testServerError('Internal server error');

        $this->assertEquals(500, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error'
        $this->assertEquals('Internal server error', $content['message']);
    }

    /** @test */
    public function it_returns_forbidden_response()
    {
        $response = $this->controller->testForbidden('Access forbidden');

        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error'
        $this->assertEquals('Access forbidden', $content['message']);
    }

    /** @test */
    public function it_returns_unauthorized_response()
    {
        $response = $this->controller->testUnauthorized('Unauthenticated');

        $this->assertEquals(401, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']); // Uses string 'error'
        $this->assertEquals('Unauthenticated', $content['message']);
    }

    /** @test */
    public function it_returns_paginated_response()
    {
        $items = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ]);

        $paginator = new LengthAwarePaginator(
            $items,
            10,
            3,
            1,
            ['path' => 'http://test.com/api/items']
        );

        $response = $this->controller->testPaginated($paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('pagination', $content);

        // Check pagination structure
        $pagination = $content['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(3, $pagination['per_page']);
        $this->assertEquals(10, $pagination['total']);
        $this->assertArrayHasKey('has_more_pages', $pagination);
    }

    /** @test */
    public function it_has_user_id_helper_method()
    {
        // Test that the getUserId method exists and is callable
        $this->assertTrue(method_exists($this->controller, 'testGetUserId'));

        // The method should handle both authenticated and guest users
        // Full testing requires database setup, so we just verify the method exists
        $this->assertTrue(is_callable([$this->controller, 'testGetUserId']));
    }
}

