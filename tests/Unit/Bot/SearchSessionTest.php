<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\SearchSession;
use Tests\TestCase;

final class SearchSessionTest extends TestCase
{
    public function test_is_active_when_budget_present(): void
    {
        $this->assertTrue(SearchSession::isActive(['budget_max' => 10_000_000]));
    }

    public function test_should_continue_search_for_short_followup(): void
    {
        $facts = SearchSession::markActive(['budget_max' => 10_000_000, 'city' => 'الرياض']);
        $this->assertTrue(SearchSession::shouldContinueSearch($facts, 'الرياض', false));
        $this->assertTrue(SearchSession::shouldContinueSearch($facts, 'ارسلي كل التفاصيل', false));
    }

    public function test_should_not_force_search_on_pure_greeting(): void
    {
        $facts = SearchSession::markActive(['budget_max' => 10_000_000]);
        $this->assertFalse(SearchSession::shouldContinueSearch($facts, 'هلا', true));
    }
}
