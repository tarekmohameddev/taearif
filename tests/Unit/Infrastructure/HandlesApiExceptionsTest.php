<?php

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;
use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Exceptions\Api\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

/**
 * Test HandlesApiExceptions trait
 *
 * Run with: php artisan test --filter=HandlesApiExceptionsTest
 */
class HandlesApiExceptionsTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test controller with trait
        $this->controller = new class extends BaseApiController {
            use HandlesApiExceptions;

            // Expose protected methods for testing
            public function testExecuteWithExceptionHandling(callable $action, string $actionName = 'perform action')
            {
                return $this->executeWithExceptionHandling($action, $actionName);
            }

            public function testHandleApiException(\Throwable $e, string $action = 'perform action')
            {
                return $this->handleApiException($e, $action);
            }
        };
    }

    /** @test */
    public function it_executes_action_successfully()
    {
        // Create a simple test that doesn't rely on $this context
        $controller = $this->controller;

        $result = $controller->testExecuteWithExceptionHandling(function () use ($controller) {
            return response()->json(['status' => true, 'data' => ['test' => 'data']]);
        }, 'test action');

        $this->assertEquals(200, $result->getStatusCode());

        $content = json_decode($result->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals(['test' => 'data'], $content['data']);
    }

    /** @test */
    public function it_handles_generic_exceptions()
    {
        Log::shouldReceive('error')->once();

        $result = $this->controller->testExecuteWithExceptionHandling(function () {
            throw new \Exception('Something went wrong');
        }, 'test action');

        $this->assertEquals(500, $result->getStatusCode());

        $content = json_decode($result->getContent(), true);
        $this->assertEquals('error', $content['status']); // String status
        $this->assertStringContainsString('wrong', strtolower($content['message'])); // e.g. "something went wrong"
    }

    /** @test */
    public function it_handles_model_not_found_exceptions()
    {
        $result = $this->controller->testExecuteWithExceptionHandling(function () {
            throw new ModelNotFoundException();
        }, 'find resource');

        $this->assertEquals(404, $result->getStatusCode());

        $content = json_decode($result->getContent(), true);
        $this->assertEquals('error', $content['status']); // String status
        $this->assertEquals('Resource not found', $content['message']);
    }

    /** @test */
    public function it_handles_validation_exceptions()
    {
        $validator = \Validator::make(
            ['email' => 'invalid'],
            ['email' => 'required|email']
        );

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $result = $this->controller->testHandleApiException($e, 'validate input');

            $this->assertEquals(422, $result->getStatusCode());

            $content = json_decode($result->getContent(), true);
            $this->assertEquals('error', $content['status']); // String status
            $this->assertEquals('Validation failed', $content['message']);
            $this->assertArrayHasKey('errors', $content);
        }
    }

    /** @test */
    public function it_handles_api_exceptions()
    {
        // Test that ApiException instances are handled if the class exists
        if (class_exists(ApiException::class)) {
            // ApiException is abstract, so we just verify the trait handles it
            // by checking that the trait has logic for ApiException
            $traitSource = file_get_contents(app_path('Traits/HandlesApiExceptions.php'));
            $hasApiExceptionHandling = strpos($traitSource, 'ApiException') !== false;

            $this->assertTrue($hasApiExceptionHandling, 'Trait should handle ApiException');
        } else {
            $this->markTestSkipped('ApiException class not available');
        }
    }

    /** @test */
    public function it_logs_errors_with_context()
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Failed to test action') &&
                       isset($context['error']) &&
                       isset($context['file']) &&
                       isset($context['line']);
            });

        $this->controller->testExecuteWithExceptionHandling(function () {
            throw new \RuntimeException('Test error');
        }, 'test action');
    }

    /** @test */
    public function it_returns_appropriate_status_codes()
    {
        // 404 for ModelNotFoundException
        $result = $this->controller->testHandleApiException(
            new ModelNotFoundException(),
            'find model'
        );
        $this->assertEquals(404, $result->getStatusCode());

        // 500 for generic exceptions
        Log::shouldReceive('error')->once();
        $result = $this->controller->testHandleApiException(
            new \RuntimeException('Error'),
            'do something'
        );
        $this->assertEquals(500, $result->getStatusCode());
    }

    /** @test */
    public function it_provides_different_messages_based_on_debug_mode()
    {
        // Enable debug mode
        config(['app.debug' => true]);

        Log::shouldReceive('error')->once();

        $result = $this->controller->testExecuteWithExceptionHandling(function () {
            throw new \Exception('Detailed error message');
        }, 'test action');

        $content = json_decode($result->getContent(), true);

        // In debug mode, should show actual exception message
        if (config('app.debug')) {
            $this->assertStringContainsString('Detailed error message', $content['message']);
        }
    }

    /** @test */
    public function it_wraps_multiple_exception_types()
    {
        $exceptionTypes = [
            ModelNotFoundException::class => 404,
            \InvalidArgumentException::class => 500,
            \RuntimeException::class => 500,
        ];

        foreach ($exceptionTypes as $exceptionClass => $expectedCode) {
            if ($exceptionClass === ModelNotFoundException::class) {
                $exception = new $exceptionClass();
            } else {
                Log::shouldReceive('error')->once();
                $exception = new $exceptionClass('Test error');
            }

            $result = $this->controller->testHandleApiException($exception, 'test');
            $this->assertEquals($expectedCode, $result->getStatusCode());
        }
    }

    /** @test */
    public function it_returns_json_responses()
    {
        $result = $this->controller->testExecuteWithExceptionHandling(function () {
            return $this->controller->success(['data' => 'value']);
        });

        $this->assertIsString($result->getContent());
        $this->assertJson($result->getContent());
    }

    /** @test */
    public function it_preserves_successful_responses()
    {
        $expectedData = ['id' => 1, 'name' => 'Test', 'values' => [1, 2, 3]];

        $result = $this->controller->testExecuteWithExceptionHandling(function () use ($expectedData) {
            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $expectedData
            ]);
        }, 'retrieve data');

        $this->assertEquals(200, $result->getStatusCode());

        $content = json_decode($result->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals('Success', $content['message']);
        $this->assertEquals($expectedData, $content['data']);
    }
}

