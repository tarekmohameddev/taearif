<?php

declare(strict_types=1);

namespace App\Domain\Reports\DTOs;

use Illuminate\Http\Request;

final class ReportFilters
{
    public function __construct(
        public readonly ReportDateFilter $date,
        public readonly ?string $purpose = null,
        public readonly ?string $type = null,
        public readonly ?string $number = null,
        public readonly ?string $search = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            date: ReportDateFilter::fromRequest($request),
            purpose: self::nullableString($request->input('purpose')),
            type: self::nullableString($request->input('type')),
            number: self::nullableString($request->input('number')),
            search: self::nullableString($request->input('search')),
        );
    }

    public static function fromDate(ReportDateFilter $date): self
    {
        return new self(date: $date);
    }

    public function hasPropertySubsetFilters(): bool
    {
        return $this->purpose !== null || $this->type !== null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
