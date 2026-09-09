<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Vercel;

use App\Services\Vercel\WwwRedirectPolicy;
use PHPUnit\Framework\TestCase;

class WwwRedirectPolicyTest extends TestCase
{
    public function test_only_exact_301_to_apex_is_correct(): void
    {
        $apex = 'example.com';

        $this->assertTrue(WwwRedirectPolicy::isCorrect([
            'name' => 'www.example.com',
            'redirect' => 'example.com',
            'redirectStatusCode' => 301,
        ], $apex));

        $this->assertTrue(WwwRedirectPolicy::isCorrect([
            'redirect' => 'EXAMPLE.COM',
            'redirectStatusCode' => 301,
        ], $apex));
    }

    public function test_308_missing_and_wrong_target_are_not_correct(): void
    {
        $apex = 'example.com';

        $this->assertFalse(WwwRedirectPolicy::isCorrect([
            'redirect' => 'example.com',
            'redirectStatusCode' => 308,
        ], $apex));

        $this->assertFalse(WwwRedirectPolicy::isCorrect([
            'redirect' => 'example.com',
            'redirectStatusCode' => null,
        ], $apex));

        $this->assertFalse(WwwRedirectPolicy::isCorrect([
            'redirect' => 'example.com',
        ], $apex));

        $this->assertFalse(WwwRedirectPolicy::isCorrect([
            'redirect' => 'example.com',
            'redirectStatusCode' => '',
        ], $apex));

        $this->assertFalse(WwwRedirectPolicy::isCorrect([
            'redirect' => 'other.example.com',
            'redirectStatusCode' => 301,
        ], $apex));

        $this->assertFalse(WwwRedirectPolicy::isCorrect(null, $apex));
        $this->assertFalse(WwwRedirectPolicy::isCorrect([], $apex));
    }
}
