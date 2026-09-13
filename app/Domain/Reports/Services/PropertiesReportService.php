<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Support\PropertiesReportQuery;
use Illuminate\Support\Facades\DB;

final class PropertiesReportService
{
    public function summary(int $userId, ReportFilters $filter): array
    {
        $scope = new PropertiesReportQuery($userId, $filter);
        $base  = fn () => $scope->properties('p', true);

        $total     = (int) (clone $base())->count(DB::raw('DISTINCT p.id'));
        $forSale   = (int) (clone $base())->where('p.purpose', 'sale')->count(DB::raw('DISTINCT p.id'));
        $forRent   = (int) (clone $base())->where('p.purpose', 'rent')->count(DB::raw('DISTINCT p.id'));
        $published = (int) (clone $base())->where('p.status', 1)->count(DB::raw('DISTINCT p.id'));
        $featured  = (int) (clone $base())->where('p.featured', 1)->count(DB::raw('DISTINCT p.id'));

        $draft = (int) (clone $base())->where(function ($q) {
            $q->where('p.status', 0)
              ->orWhereNotNull('p.missing_fields')
              ->orWhereNotNull('p.validation_errors');
        })->count(DB::raw('DISTINCT p.id'));

        $avgSalePrice = (clone $base())->where('p.purpose', 'sale')->avg('p.price') ?: 0.0;
        $avgRentPrice = (clone $base())->where('p.purpose', 'rent')->avg('p.price') ?: 0.0;

        $propIds = $scope->matchingPropertyIds(false);
        $start   = $filter->date->startDate;
        $end     = $filter->date->endDate;

        $viewsQuery = DB::table('pageview_analytics')
            ->where('tenant_id', $scope->analyticsTenantKey())
            ->where('page_type', 'property')
            ->whereBetween('date_bucket', [$start->toDateString(), $end->toDateString()]);

        if ($propIds !== []) {
            $slugs = DB::table('user_property_contents')
                ->whereIn('property_id', $propIds)
                ->whereNotNull('slug')
                ->pluck('slug')
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($filter->hasPropertySubsetFilters()) {
                $viewsQuery->whereIn('page_slug', $slugs === [] ? [''] : $slugs);
            }
        } elseif ($filter->hasPropertySubsetFilters()) {
            $viewsQuery->whereRaw('1 = 0');
        }

        $totalViews = (int) $viewsQuery->sum('views_count');

        $inquiryQuery = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end]);
        if ($filter->hasPropertySubsetFilters()) {
            if ($propIds === []) {
                $inquiryQuery->whereRaw('1 = 0');
            } else {
                $inquiryQuery->whereIn('initial_property_id', $propIds);
            }
        }
        $totalInquiries = $inquiryQuery->count();

        $conversionRate = $totalViews > 0
            ? round($totalInquiries / $totalViews * 100, 2)
            : 0.0;

        $importedQuery = $scope->properties('p', true)->whereNotNull('p.import_batch_id');
        $importedCount = (int) (clone $importedQuery)->count(DB::raw('DISTINCT p.id'));

        $importStats = DB::table('bulk_import_batches')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(total) as total, SUM(succeeded) as succeeded')
            ->first();

        $importSuccessRate = 0.0;
        if ($importStats && $importStats->total > 0) {
            $importSuccessRate = round($importStats->succeeded / $importStats->total * 100, 2);
        }

        return [
            'total_properties'     => $total,
            'for_sale_count'       => $forSale,
            'for_rent_count'       => $forRent,
            'published_count'      => $published,
            'draft_count'          => $draft,
            'featured_count'       => $featured,
            'avg_sale_price'       => round((float) $avgSalePrice, 2),
            'avg_rent_price'       => round((float) $avgRentPrice, 2),
            'conversion_rate'      => $conversionRate,
            'imported_this_period' => $importedCount,
            'import_success_rate'  => $importSuccessRate,
            'generated_at'         => now()->toISOString(),
        ];
    }

    public function priceDistribution(int $userId, ReportFilters $filter): array
    {
        $scope = new PropertiesReportQuery($userId, $filter);
        $buckets = [
            ['label' => '0-500K',   'min' => 0,         'max' => 500000],
            ['label' => '500K-1M',  'min' => 500000,    'max' => 1000000],
            ['label' => '1M-2M',    'min' => 1000000,   'max' => 2000000],
            ['label' => '2M-5M',    'min' => 2000000,   'max' => 5000000],
            ['label' => '5M+',      'min' => 5000000,   'max' => PHP_INT_MAX],
        ];

        $rows = [];
        foreach ($buckets as $bucket) {
            $q = $scope->properties('p', true)->where('p.price', '>', 0);

            if ($bucket['max'] === PHP_INT_MAX) {
                $q->where('p.price', '>=', $bucket['min']);
            } else {
                $q->whereBetween('p.price', [$bucket['min'], $bucket['max'] - 1]);
            }

            $rows[] = ['label' => $bucket['label'], 'count' => (int) (clone $q)->count(DB::raw('DISTINCT p.id'))];
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function byCity(int $userId, ReportFilters $filter): array
    {
        $scope = new PropertiesReportQuery($userId, $filter);

        $rows = $scope->properties('p', true)
            ->join('user_property_contents as pc', 'pc.property_id', '=', 'p.id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->selectRaw('COALESCE(c.name_ar, c.name_en, "Unknown") as city_name, COUNT(DISTINCT p.id) as count')
            ->groupBy('c.id', 'c.name_ar', 'c.name_en')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['city' => $r->city_name, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function byType(int $userId, ReportFilters $filter): array
    {
        $scope = new PropertiesReportQuery($userId, $filter);

        $rows = $scope->properties('p', true)
            ->selectRaw('p.property_type, COUNT(DISTINCT p.id) as count')
            ->groupBy('p.property_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => $r->property_type, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function viewsTrend(int $userId, ReportFilters $filter): array
    {
        $scope       = new PropertiesReportQuery($userId, $filter);
        $granularity = $filter->date->granularity();
        $dateFormat  = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $query = DB::table('pageview_analytics')
            ->where('tenant_id', $scope->analyticsTenantKey())
            ->where('page_type', 'property')
            ->whereBetween('date_bucket', [
                $filter->date->startDate->toDateString(),
                $filter->date->endDate->toDateString(),
            ]);

        if ($filter->hasPropertySubsetFilters()) {
            $propIds = $scope->matchingPropertyIds(false);
            $slugs = $propIds === []
                ? []
                : DB::table('user_property_contents')
                    ->whereIn('property_id', $propIds)
                    ->whereNotNull('slug')
                    ->pluck('slug')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            $query->whereIn('page_slug', $slugs === [] ? [''] : $slugs);
        }

        $rows = $query
            ->selectRaw("DATE_FORMAT(date_bucket, '{$dateFormat}') as date_label, SUM(views_count) as total_views")
            ->groupByRaw("DATE_FORMAT(date_bucket, '{$dateFormat}')")
            ->orderBy('date_label')
            ->get()
            ->map(fn ($r) => ['date' => $r->date_label, 'total_views' => (int) $r->total_views])
            ->toArray();

        return ['granularity' => $granularity, 'data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function featuredComparison(int $userId, ReportFilters $filter): array
    {
        $scope = new PropertiesReportQuery($userId, $filter);

        $compute = function (bool $featured) use ($scope): float {
            $propIds = $scope->properties('p', true)
                ->where('p.featured', $featured ? 1 : 0)
                ->distinct()
                ->pluck('p.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($propIds === []) {
                return 0.0;
            }

            $slugs = DB::table('user_property_contents')
                ->whereIn('property_id', $propIds)
                ->whereNotNull('slug')
                ->pluck('slug')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $totalViews = $scope->totalViewsForSlugs($slugs);

            return round($totalViews / count($propIds), 2);
        };

        return [
            'data' => [
                'featured'     => $compute(true),
                'non_featured' => $compute(false),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function importHistory(int $userId, ReportFilters $filter): array
    {
        $start = $filter->date->startDate;
        $end   = $filter->date->endDate;

        $rows = DB::table('bulk_import_batches')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['created_at', 'succeeded', 'failed', 'total', 'status'])
            ->map(fn ($r) => [
                'date'            => $r->created_at,
                'imported_count'  => (int) ($r->succeeded ?? 0),
                'updated_count'   => 0,
                'failed_count'    => (int) ($r->failed ?? 0),
                'total'           => (int) ($r->total ?? 0),
                'status'          => $r->status,
            ])
            ->toArray();

        if ($rows === []) {
            $scope = new PropertiesReportQuery($userId, $filter);
            $rows = $scope->properties('p', true)
                ->whereNotNull('p.import_batch_id')
                ->selectRaw('DATE(p.created_at) as date_label, COUNT(DISTINCT p.id) as imported_count')
                ->groupByRaw('DATE(p.created_at)')
                ->orderBy('date_label')
                ->get()
                ->map(fn ($r) => [
                    'date'            => $r->date_label,
                    'imported_count'  => (int) $r->imported_count,
                    'updated_count'   => 0,
                    'failed_count'    => 0,
                    'total'           => (int) $r->imported_count,
                    'status'          => 'done',
                ])
                ->toArray();
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function topListings(int $userId, ReportFilters $filter): array
    {
        $scope       = new PropertiesReportQuery($userId, $filter);
        $companyName = $scope->companyName();

        $rows = $scope->properties('p', true)
            ->leftJoin('user_property_contents as pc', 'pc.property_id', '=', 'p.id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->leftJoin('user_districts as d', 'd.id', '=', 'pc.state_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->leftJoinSub($scope->viewsSubquery(), 'pv', 'pv.page_slug', '=', 'pc.slug')
            ->selectRaw(
                "p.id, pc.title, p.property_type, p.purpose, p.price, p.status,
                 COALESCE(c.name_ar, c.name_en, '') as city,
                 COALESCE(d.name_ar, d.name_en, '') as district,
                 COALESCE(pv.total_views, 0) as view_count,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name"
            )
            ->orderByDesc('view_count')
            ->orderByDesc('p.price')
            ->limit(40)
            ->get()
            ->map(function ($r) use ($userId, $companyName) {
                $inquiries = DB::table('users_property_requests')
                    ->where('user_id', $userId)
                    ->where('initial_property_id', $r->id)
                    ->count();

                $agentName = trim((string) $r->agent_name);
                if ($agentName === '' || strcasecmp($agentName, 'User') === 0) {
                    $agentName = $companyName ?: $agentName;
                }

                return [
                    'title'         => $r->title,
                    'type'          => $r->property_type,
                    'city'          => $r->city,
                    'district'      => $r->district,
                    'purpose'       => $r->purpose,
                    'price'         => (float) $r->price,
                    'view_count'    => (int) $r->view_count,
                    'inquiry_count' => $inquiries,
                    'agent_name'    => $agentName,
                    'status'        => $r->status,
                ];
            })
            ->filter(function (array $row): bool {
                $title = trim((string) ($row['title'] ?? ''));
                $type  = trim((string) ($row['type'] ?? ''));
                $hasSignal = $row['price'] > 0 || $row['view_count'] > 0 || $row['inquiry_count'] > 0;

                if ($title === '') {
                    return false;
                }
                if ($type === '' && ! $hasSignal) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (array $row) => [$row['view_count'], $row['inquiry_count'], $row['price']])
            ->take(10)
            ->values()
            ->map(function (array $row) {
                unset($row['status']);
                return $row;
            })
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function agentPerformance(int $userId, ReportFilters $filter, int $page, int $limit, ?int $actorId = null): array
    {
        $scope     = new PropertiesReportQuery($userId, $filter);
        $start     = $filter->date->startDate;
        $end       = $filter->date->endDate;
        $filtered  = $filter->hasPropertySubsetFilters();
        $propQuery = $scope->properties('p', true);
        if (! $filtered) {
            $propQuery->where('p.status', 1);
        }
        $matchingIds = $propQuery->distinct()->pluck('p.id')->map(fn ($id) => (int) $id)->all();

        if ($filtered && $matchingIds === []) {
            return [
                'data'       => [],
                'pagination' => ['total' => 0, 'page' => $page, 'limit' => $limit],
                'generated_at' => now()->toISOString(),
            ];
        }

        $query = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee');

        if ($actorId !== null) {
            $query->where('u.id', $actorId);
        }

        $join = function ($j) use ($userId, $matchingIds, $filtered): void {
            $j->on('p.created_by', '=', 'u.id')->where('p.user_id', $userId);
            if ($matchingIds !== []) {
                $j->whereIn('p.id', $matchingIds);
            } elseif ($filtered) {
                $j->whereRaw('1 = 0');
            } else {
                $j->where('p.status', 1);
            }
        };

        if ($filtered) {
            $query->join('user_properties as p', $join);
        } else {
            $query->leftJoin('user_properties as p', $join);
        }

        $total = (clone $query)
            ->select('u.id')
            ->groupBy('u.id')
            ->get()
            ->count();

        $rows = (clone $query)
            ->selectRaw(
                "u.id,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                 COUNT(DISTINCT p.id) as active_listings_count"
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $agentIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $viewsByAgent = [];
        $inquiriesByAgent = [];
        $avgDaysByAgent = [];

        if ($agentIds !== [] && $matchingIds !== []) {
            $viewsByAgent = DB::table('user_properties as p')
                ->join('user_property_contents as pc', 'pc.property_id', '=', 'p.id')
                ->leftJoinSub($scope->viewsSubquery(), 'pv', 'pv.page_slug', '=', 'pc.slug')
                ->whereIn('p.id', $matchingIds)
                ->whereIn('p.created_by', $agentIds)
                ->selectRaw('p.created_by as agent_id, COALESCE(SUM(pv.total_views), 0) as total_views')
                ->groupBy('p.created_by')
                ->pluck('total_views', 'agent_id')
                ->all();

            $inquiryRows = DB::table('users_property_requests as upr')
                ->join('user_properties as p', 'p.id', '=', 'upr.initial_property_id')
                ->where('upr.user_id', $userId)
                ->whereIn('upr.initial_property_id', $matchingIds)
                ->whereIn('p.created_by', $agentIds)
                ->whereBetween('upr.created_at', [$start, $end])
                ->selectRaw('p.created_by as agent_id, COUNT(*) as inquiries, AVG(DATEDIFF(upr.created_at, p.created_at)) as avg_days')
                ->groupBy('p.created_by')
                ->get();

            foreach ($inquiryRows as $row) {
                $inquiriesByAgent[(int) $row->agent_id] = (int) $row->inquiries;
                $avgDaysByAgent[(int) $row->agent_id] = $row->avg_days !== null
                    ? round((float) $row->avg_days, 2)
                    : null;
            }
        }

        $mapped = $rows->map(function ($r) use ($viewsByAgent, $inquiriesByAgent, $avgDaysByAgent) {
            $id = (int) $r->id;

            return [
                'agent_id'                       => $id,
                'agent_name'                     => trim((string) $r->agent_name),
                'active_listings_count'          => (int) $r->active_listings_count,
                'total_views_generated'          => (int) ($viewsByAgent[$id] ?? 0),
                'inquiries_received'             => (int) ($inquiriesByAgent[$id] ?? 0),
                'avg_days_listed_before_inquiry' => $avgDaysByAgent[$id] ?? null,
            ];
        })->toArray();

        return [
            'data'       => $mapped,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }
}
