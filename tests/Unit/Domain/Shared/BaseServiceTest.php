<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Services\BaseService;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BaseServiceTest extends TestCase
{
    private TestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TestService();
    }

    /** @test */
    public function it_executes_callback_inside_transaction(): void
    {
        $result = $this->service->runInTransaction(fn () => DB::transactionLevel());

        $this->assertSame(1, $result);
        $this->assertSame(0, DB::transactionLevel());
    }

    /** @test */
    public function it_respects_existing_transaction_levels(): void
    {
        $levels = [];

        DB::transaction(function () use (&$levels) {
            $levels[] = DB::transactionLevel();
            $levels[] = $this->service->runInTransaction(fn () => DB::transactionLevel());
        });

        $this->assertSame([1, 1], $levels);
        $this->assertSame(0, DB::transactionLevel());
    }

    /** @test */
    public function it_validates_business_rules(): void
    {
        $this->service->assertBusinessRule(true);

        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('Rule failed');

        $this->service->assertBusinessRule(false);
    }

    /** @test */
    public function it_can_fail_with_custom_error(): void
    {
        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('Operation failed');

        $this->service->failWith('Operation failed', 'OPS_001', 422);
    }

    /** @test */
    public function it_ensures_value_is_found(): void
    {
        $value = $this->service->ensureValue('foo');

        $this->assertSame('foo', $value);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Missing resource');

        $this->service->ensureValue(null, 'Missing resource');
    }
}

class TestService extends BaseService
{
    public function runInTransaction(callable $callback): mixed
    {
        return $this->executeInTransaction($callback);
    }

    public function assertBusinessRule(bool $condition): void
    {
        $this->validateBusinessRule($condition, 'Rule failed', 'RULE_001', 422);
    }

    public function failWith(string $message, string $code, int $httpCode): void
    {
        $this->fail($message, $code, $httpCode);
    }

    public function ensureValue(mixed $value, string $message = 'Resource not found'): mixed
    {
        return $this->ensureFound($value, $message);
    }
}


