<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\SlotFillingPolicy;
use Tests\TestCase;

final class SlotFillingPolicyTest extends TestCase
{
    private SlotFillingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SlotFillingPolicy();
    }

    public function test_it_asks_for_city_when_location_unknown(): void
    {
        $question = $this->policy->nextQuestion([], 'property_search');
        $this->assertNotNull($question);
        $this->assertStringContainsString('مدينة', $question);
    }

    public function test_it_asks_for_budget_when_city_known(): void
    {
        $facts = ['city' => 'الرياض'];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        $this->assertNotNull($question);
        $this->assertStringContainsString('ميزانيت', $question);
    }

    public function test_it_searches_without_bedrooms_when_type_unknown(): void
    {
        $facts = ['city' => 'الرياض', 'budget_max' => 500000];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        $this->assertNull($question);
    }

    public function test_it_asks_for_bedrooms_for_apartment_type(): void
    {
        $facts = ['city' => 'الرياض', 'budget_max' => 500000, 'type' => 'شقة'];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        $this->assertNotNull($question);
        $this->assertStringContainsString('غرف', $question);
    }

    public function test_it_returns_null_for_non_search_intent(): void
    {
        $question = $this->policy->nextQuestion([], 'complaint');
        $this->assertNull($question);
    }

    public function test_it_stops_asking_after_max_consecutive_questions(): void
    {
        $facts = ['_questions_asked' => 2];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        $this->assertNull($question);
    }

    public function test_it_skips_bedrooms_for_non_residential_types(): void
    {
        $facts = ['city' => 'الرياض', 'budget_max' => 100000, 'type' => 'office'];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        // Should ask about type or return null since type is already set
        // Should NOT ask about bedrooms for office type
        if ($question !== null) {
            $this->assertStringNotContainsString('غرف', $question);
        } else {
            $this->assertNull($question);
        }
    }

    public function test_it_does_not_ask_for_bedrooms_for_building_type(): void
    {
        $facts = ['city' => 'الرياض', 'budget_max' => 7_000_000, 'type' => 'عمارة'];
        $question = $this->policy->nextQuestion($facts, 'property_search');
        $this->assertNull($question);
    }

    public function test_it_does_not_reask_city_when_already_asked_and_budget_known(): void
    {
        $facts = [
            'budget_max'       => 7_000_000,
            'type'             => 'عمارة',
            '_asked_slots'     => ['city'],
            '_questions_asked' => 1,
        ];
        $this->assertNull($this->policy->nextQuestion($facts, 'property_search'));
        $this->assertNull($this->policy->nextSlot($facts, 'property_search'));
    }

    public function test_it_records_city_slot_when_budget_only(): void
    {
        $facts = ['budget_max' => 500000, 'type' => 'شقة'];
        $slot = $this->policy->nextSlot($facts, 'property_search');
        $this->assertNotNull($slot);
        $this->assertSame('city', $slot['slot']);
        $this->assertStringContainsString('مدينة', $slot['question']);
    }
}
