<?php

declare(strict_types=1);

namespace App\Domain\Reports\Support;

use App\Domain\Reports\DTOs\ReportFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PropertyTransactionReportQuery
{
    public function __construct(private readonly int $userId, private readonly ReportFilters $filters) {}

    public function properties(string $alias = 'p'): Builder
    {
        $q = DB::table("user_properties as {$alias}")->where("{$alias}.user_id", $this->userId);
        if ($this->filters->purpose !== null) $q->where("{$alias}.listing_purpose", $this->filters->purpose);
        if ($this->filters->type !== null) {
            $normalized = mb_strtolower($this->filters->type);
            $q->join('api_user_categories as report_category', 'report_category.id', '=', "{$alias}.category_id")
                ->where(function (Builder $inner) use ($normalized): void {
                    $inner->whereRaw('LOWER(report_category.slug) = ?', [$normalized])
                        ->orWhereRaw('LOWER(report_category.name) = ?', [$normalized]);
                });
        }
        return $q;
    }

    public function start(): string { return $this->filters->date->startDate->setTimezone('Asia/Riyadh')->toDateTimeString(); }
    public function end(): string { return $this->filters->date->endDate->setTimezone('Asia/Riyadh')->toDateTimeString(); }
    public function startDate(): string { return $this->filters->date->startDate->setTimezone('Asia/Riyadh')->toDateString(); }
    public function endDate(): string { return $this->filters->date->endDate->setTimezone('Asia/Riyadh')->toDateString(); }
    public function userId(): int { return $this->userId; }
    public function filtersPurpose(): ?string { return $this->filters->purpose; }
    public function filtersType(): ?string { return $this->filters->type; }
    public function filtersStart(): string { return $this->filters->date->startDate->setTimezone('Asia/Riyadh')->toIso8601String(); }
    public function filtersEnd(): string { return $this->filters->date->endDate->setTimezone('Asia/Riyadh')->toIso8601String(); }
}
