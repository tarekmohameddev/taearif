<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\RelevanceGate;
use Tests\TestCase;

final class RelevanceGateTest extends TestCase
{
    private RelevanceGate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new RelevanceGate();
    }

    public function test_it_marks_real_estate_keywords_as_relevant(): void
    {
        $result = $this->gate->check('أريد شقة في الرياض');
        $this->assertTrue($result['relevant']);
        $this->assertSame('real_estate_keyword', $result['reason']);
    }

    public function test_it_marks_empty_message_as_off_topic(): void
    {
        $result = $this->gate->check('  ');
        $this->assertFalse($result['relevant']);
        $this->assertSame('too_short', $result['reason']);
    }

    public function test_it_marks_maintenance_request_as_off_topic(): void
    {
        // Pure maintenance message without real-estate keywords
        $result = $this->gate->check('عندي مشكلة صيانة تسريب');
        $this->assertFalse($result['relevant']);
        $this->assertStringStartsWith('off_topic:', $result['reason']);
    }

    public function test_it_marks_pure_numeric_as_off_topic(): void
    {
        $result = $this->gate->check('12345');
        $this->assertFalse($result['relevant']);
        $this->assertSame('numeric_only', $result['reason']);
    }

    public function test_it_allows_ambiguous_message_by_default(): void
    {
        $result = $this->gate->check('مرحبا كيف الحال');
        $this->assertTrue($result['relevant']);
        $this->assertSame('default_allow', $result['reason']);
    }

    public function test_it_marks_price_inquiry_as_relevant(): void
    {
        $result = $this->gate->check('كم السعر؟');
        $this->assertTrue($result['relevant']);
    }

    public function test_it_handles_foreign_text_real_estate(): void
    {
        $result = $this->gate->check('I need apartment for rent');
        $this->assertTrue($result['relevant']);
    }
}
