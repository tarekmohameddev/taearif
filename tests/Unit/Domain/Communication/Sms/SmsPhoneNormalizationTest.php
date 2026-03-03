<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\Sms;

use App\Domain\Communication\Sms\Services\SmsRecipientResolverService;
use Tests\TestCase;

class SmsPhoneNormalizationTest extends TestCase
{
    private SmsRecipientResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(SmsRecipientResolverService::class);
    }

    /** @test */
    public function normalize_keeps_plus_prefix_and_digits_only(): void
    {
        $this->assertSame('+966501234567', $this->resolver->normalizePhone('+966 50 123 4567'));
        $this->assertSame('+966501234567', $this->resolver->normalizePhone('+966501234567'));
    }

    /** @test */
    public function normalize_returns_digits_without_plus_when_no_plus_given(): void
    {
        $this->assertSame('966501234567', $this->resolver->normalizePhone('966501234567'));
        $this->assertSame('966501234567', $this->resolver->normalizePhone('966 50 123 4567'));
    }

    /** @test */
    public function normalize_returns_null_for_empty_or_too_short(): void
    {
        $this->assertNull($this->resolver->normalizePhone(''));
        $this->assertNull($this->resolver->normalizePhone('   '));
        $this->assertNull($this->resolver->normalizePhone('123')); // fewer than 8 digits
    }

    /** @test */
    public function normalize_returns_null_for_non_numeric_strings(): void
    {
        $this->assertNull($this->resolver->normalizePhone('no-digits'));
        $this->assertNull($this->resolver->normalizePhone('+abc'));
    }

    /** @test */
    public function normalize_strips_spaces_and_non_digits(): void
    {
        $this->assertSame('+966501234567', $this->resolver->normalizePhone('  +966 (50) 123-4567  '));
    }
}
