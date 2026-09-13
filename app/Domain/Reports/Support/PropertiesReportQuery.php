<?php

declare(strict_types=1);

namespace App\Domain\Reports\Support;

use App\Domain\Reports\DTOs\ReportFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PropertiesReportQuery
{
    public function __construct(
        private readonly int $userId,
        private readonly ReportFilters $filters,
    ) {}

    public function analyticsTenantKey(): string
    {
        $username = DB::table('users')->where('id', $this->userId)->value('username');

        return is_string($username) && $username !== ''
            ? $username
            : (string) $this->userId;
    }

    public function companyName(): ?string
    {
        $name = null;
        if (Schema::hasTable('user_basic_settings')) {
            $name = DB::table('user_basic_settings')->where('user_id', $this->userId)->value('company_name');
        }

        if (! is_string($name) || $name === '' || strcasecmp($name, 'N/A') === 0) {
            $name = DB::table('users')->where('id', $this->userId)->value('company_name');
        }

        if (! is_string($name) || $name === '' || strcasecmp($name, 'N/A') === 0) {
            return null;
        }

        return $name;
    }

    /**
     * Base tenant properties query with optional purpose/type/created_at filters.
     */
    public function properties(string $alias = 'p', bool $applyCreatedAt = true): Builder
    {
        $query = DB::table("user_properties as {$alias}")
            ->where("{$alias}.user_id", $this->userId);

        $this->applyPurpose($query, $alias);
        $this->applyType($query, $alias);

        if ($applyCreatedAt) {
            $query->whereBetween("{$alias}.created_at", [
                $this->filters->date->startDate,
                $this->filters->date->endDate,
            ]);
        }

        return $query;
    }

    /**
     * Property IDs matching purpose/type (and created_at when requested).
     *
     * @return list<int>
     */
    public function matchingPropertyIds(bool $applyCreatedAt = true): array
    {
        return $this->properties('p', $applyCreatedAt)
            ->distinct()
            ->pluck('p.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function viewsSubquery(): Builder
    {
        $date = $this->filters->date;

        return DB::table('pageview_analytics')
            ->selectRaw('page_slug, SUM(views_count) as total_views')
            ->where('tenant_id', $this->analyticsTenantKey())
            ->where('page_type', 'property')
            ->whereBetween('date_bucket', [$date->startDate->toDateString(), $date->endDate->toDateString()])
            ->groupBy('page_slug');
    }

    public function totalViewsForSlugs(array $slugs): int
    {
        $slugs = array_values(array_filter($slugs, fn ($s) => is_string($s) && $s !== ''));
        if ($slugs === []) {
            return 0;
        }

        $date = $this->filters->date;

        return (int) DB::table('pageview_analytics')
            ->where('tenant_id', $this->analyticsTenantKey())
            ->where('page_type', 'property')
            ->whereIn('page_slug', $slugs)
            ->whereBetween('date_bucket', [$date->startDate->toDateString(), $date->endDate->toDateString()])
            ->sum('views_count');
    }

    private function applyPurpose(Builder $query, string $alias): void
    {
        $purpose = $this->filters->purpose;
        if ($purpose === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($alias, $purpose): void {
            if (Schema::hasColumn('user_properties', 'listing_purpose')) {
                $inner->where("{$alias}.listing_purpose", $purpose)
                    ->orWhere("{$alias}.purpose", $purpose);
            } else {
                $inner->where("{$alias}.purpose", $purpose);
            }
        });
    }

    private function applyType(Builder $query, string $alias): void
    {
        $type = $this->filters->type;
        if ($type === null || ! Schema::hasTable('api_user_categories')) {
            return;
        }

        $normalized = mb_strtolower($type);
        $catAlias = $alias === 'p' ? 'report_cat' : $alias . '_cat';

        $query->join("api_user_categories as {$catAlias}", "{$catAlias}.id", '=', "{$alias}.category_id")
            ->where(function (Builder $inner) use ($catAlias, $normalized): void {
                $inner->whereRaw("LOWER({$catAlias}.slug) = ?", [$normalized])
                    ->orWhereRaw("LOWER({$catAlias}.name) = ?", [$normalized]);
            });
    }
}
