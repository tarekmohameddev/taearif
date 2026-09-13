<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use Carbon\Carbon;
use Tests\TestCase;

class ReportDateFilterWeekTest extends TestCase
{
    public function test_week_preset_starts_sunday_and_ends_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));

        $filter = ReportDateFilter::fromPreset('week');

        $this->assertSame(Carbon::SUNDAY, $filter->startDate->dayOfWeek);
        $this->assertSame(Carbon::SATURDAY, $filter->endDate->dayOfWeek);
        $this->assertSame('2026-09-06', $filter->startDate->toDateString());
        $this->assertSame('2026-09-12', $filter->endDate->toDateString());

        Carbon::setTestNow();
    }
}
