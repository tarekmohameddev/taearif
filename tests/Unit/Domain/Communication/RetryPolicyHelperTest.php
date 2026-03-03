<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Services\RetryPolicyHelper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RetryPolicyHelperTest extends TestCase
{
    private RetryPolicyHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('communication.reliability.retry.initial_backoff_seconds', 30);
        Config::set('communication.reliability.retry.max_backoff_seconds', 600);
        Config::set('communication.reliability.retry.max_attempts', 3);
        $this->helper = app(RetryPolicyHelper::class);
    }

    /** @test */
    public function http_429_is_transient(): void
    {
        $this->assertTrue($this->helper->isTransient('meta', 429, null, null));
    }

    /** @test */
    public function http_5xx_is_transient(): void
    {
        $this->assertTrue($this->helper->isTransient('meta', 503, null, null));
    }

    /** @test */
    public function http_4xx_except_429_is_non_transient(): void
    {
        $this->assertFalse($this->helper->isTransient('meta', 400, null, null));
        $this->assertFalse($this->helper->isTransient('meta', 401, null, null));
    }

    /** @test */
    public function max_attempts_from_config(): void
    {
        $this->assertSame(3, $this->helper->maxAttempts());
    }

    /** @test */
    public function next_retry_at_increases_with_attempt_number(): void
    {
        $t1 = $this->helper->nextRetryAt(1);
        $t2 = $this->helper->nextRetryAt(2);
        $this->assertTrue($t2->gt($t1));
    }
}
