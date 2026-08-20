<?php

namespace Tests\Unit\Calling;

use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;
use App\Domain\Calling\Services\PhoneNumberService;
use PHPUnit\Framework\TestCase;

class PhoneNumberServiceTest extends TestCase
{
    private PhoneNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNumberService();
    }

    /** @test */
    public function test_it_converts_national_format_to_e164(): void
    {
        $this->assertSame('+966512345678', $this->service->toE164('0512345678'));
    }

    /** @test */
    public function test_it_converts_country_code_without_plus_to_e164(): void
    {
        $this->assertSame('+966512345678', $this->service->toE164('966512345678'));
    }

    /** @test */
    public function test_it_accepts_e164_format(): void
    {
        $this->assertSame('+966512345678', $this->service->toE164('+966512345678'));
    }

    /** @test */
    public function test_it_strips_spaces_and_dashes(): void
    {
        $this->assertSame('+966512345678', $this->service->toE164('0512 345 678'));
    }

    /** @test */
    public function test_it_accepts_00966_prefix(): void
    {
        $this->assertSame('+966512345678', $this->service->toE164('00966512345678'));
    }

    /** @test */
    public function test_it_rejects_too_short_number(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->toE164('0512345');
    }

    /** @test */
    public function test_it_rejects_non_mobile_number(): void
    {
        // Landline prefix 11 instead of 5
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->toE164('0112345678');
    }

    /** @test */
    public function test_it_rejects_empty_string(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->toE164('');
    }

    /** @test */
    public function test_to_dial_string_strips_plus(): void
    {
        $e164 = $this->service->toE164('+966512345678');
        $this->assertSame('966512345678', $this->service->toDialString($e164));
    }

    /** @test */
    public function test_is_valid_e164_returns_true_for_valid_number(): void
    {
        $this->assertTrue($this->service->isValidE164('0512345678'));
    }

    /** @test */
    public function test_is_valid_e164_returns_false_for_invalid_number(): void
    {
        $this->assertFalse($this->service->isValidE164('abc'));
    }
}
