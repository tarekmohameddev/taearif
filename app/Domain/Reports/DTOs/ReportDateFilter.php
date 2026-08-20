<?php

declare(strict_types=1);

namespace App\Domain\Reports\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class ReportDateFilter
{
    public function __construct(
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $preset = $request->input('preset');

        if ($preset && $preset !== 'custom') {
            return self::fromPreset($preset);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            return new self(
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            );
        }

        return self::fromPreset('month');
    }

    public static function fromPreset(string $preset): self
    {
        return match ($preset) {
            'today'   => new self(Carbon::today()->startOfDay(), Carbon::today()->endOfDay()),
            'week'    => new self(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()),
            'quarter' => new self(Carbon::now()->firstOfQuarter(), Carbon::now()->lastOfQuarter()->endOfDay()),
            'year'    => new self(Carbon::now()->startOfYear(), Carbon::now()->endOfYear()->endOfDay()),
            default   => new self(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()->endOfDay()),
        };
    }

    public function granularity(): string
    {
        $days = $this->startDate->diffInDays($this->endDate);

        if ($days <= 31) {
            return 'day';
        }

        if ($days <= 90) {
            return 'week';
        }

        return 'month';
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toISOString(),
            'end_date'   => $this->endDate->toISOString(),
        ];
    }
}
