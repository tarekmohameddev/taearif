<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
use Illuminate\Support\Facades\DB;

final class PropertiesReportService
{
    public function summary(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $base = fn () => DB::table('user_properties')->where('user_id', $userId);

        $total     = ($base)()->count();
        $forSale   = ($base)()->where('purpose', 'sale')->count();
        $forRent   = ($base)()->where('purpose', 'rent')->count();
        $published = ($base)()->where('status', 1)->count();
        $featured  = ($base)()->where('featured', 1)->count();

        // Draft / incomplete: status = 0 or has validation_errors / missing_fields
        $draft = ($base)()->where(function ($q) {
            $q->where('status', 0)
              ->orWhereNotNull('missing_fields')
              ->orWhereNotNull('validation_errors');
        })->count();

        $avgSalePrice = ($base)()->where('purpose', 'sale')->avg('price') ?: 0.0;
        $avgRentPrice = ($base)()->where('purpose', 'rent')->avg('price') ?: 0.0;

        // Views from pageview_analytics
        $totalViews = DB::table('pageview_analytics')
            ->where('tenant_id', $userId)
            ->where('page_type', 'property')
            ->whereBetween('date_bucket', [$start->toDateString(), $end->toDateString()])
            ->sum('views_count');

        // Inquiries in period
        $totalInquiries = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $conversionRate = $totalViews > 0
            ? round($totalInquiries / $totalViews * 100, 2)
            : 0.0;

        // Properties imported in period
        $importedCount = DB::table('user_properties')
            ->where('user_id', $userId)
            ->whereNotNull('import_batch_id')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Import success rate
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

    public function priceDistribution(int $userId): array
    {
        $buckets = [
            ['label' => '0-500K',   'min' => 0,         'max' => 500000],
            ['label' => '500K-1M',  'min' => 500000,    'max' => 1000000],
            ['label' => '1M-2M',    'min' => 1000000,   'max' => 2000000],
            ['label' => '2M-5M',    'min' => 2000000,   'max' => 5000000],
            ['label' => '5M+',      'min' => 5000000,   'max' => PHP_INT_MAX],
        ];

        $rows = [];
        foreach ($buckets as $bucket) {
            $q = DB::table('user_properties')
                ->where('user_id', $userId)
                ->where('price', '>', 0);

            if ($bucket['max'] === PHP_INT_MAX) {
                $q->where('price', '>=', $bucket['min']);
            } else {
                $q->whereBetween('price', [$bucket['min'], $bucket['max'] - 1]);
            }

            $rows[] = ['label' => $bucket['label'], 'count' => $q->count()];
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function byCity(int $userId): array
    {
        $rows = DB::table('user_property_contents as pc')
            ->join('user_properties as p', 'p.id', '=', 'pc.property_id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->where('p.user_id', $userId)
            ->selectRaw('COALESCE(c.name_ar, c.name_en, "Unknown") as city_name, COUNT(DISTINCT p.id) as count')
            ->groupBy('c.id', 'c.name_ar', 'c.name_en')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['city' => $r->city_name, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function byType(int $userId): array
    {
        $rows = DB::table('user_properties')
            ->where('user_id', $userId)
            ->selectRaw('property_type, COUNT(*) as count')
            ->groupBy('property_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => $r->property_type, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function viewsTrend(int $userId, ReportDateFilter $filter): array
    {
        $granularity = $filter->granularity();
        $dateFormat  = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $rows = DB::table('pageview_analytics')
            ->where('tenant_id', $userId)
            ->where('page_type', 'property')
            ->whereBetween('date_bucket', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->selectRaw("DATE_FORMAT(date_bucket, '{$dateFormat}') as date_label, SUM(views_count) as total_views")
            ->groupByRaw("DATE_FORMAT(date_bucket, '{$dateFormat}')")
            ->orderBy('date_label')
            ->get()
            ->map(fn ($r) => ['date' => $r->date_label, 'total_views' => (int) $r->total_views])
            ->toArray();

        return ['granularity' => $granularity, 'data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function featuredComparison(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate->toDateString();
        $end   = $filter->endDate->toDateString();

        $compute = function (bool $featured) use ($userId, $start, $end) {
            $propIds = DB::table('user_properties')
                ->where('user_id', $userId)
                ->where('featured', $featured ? 1 : 0)
                ->pluck('id');

            if ($propIds->isEmpty()) {
                return 0.0;
            }

            $totalViews = DB::table('pageview_analytics')
                ->where('tenant_id', $userId)
                ->where('page_type', 'property')
                ->whereBetween('date_bucket', [$start, $end])
                ->sum('views_count');

            return $propIds->count() > 0
                ? round($totalViews / $propIds->count(), 2)
                : 0.0;
        };

        return [
            'data' => [
                'featured'     => $compute(true),
                'non_featured' => $compute(false),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function importHistory(int $userId, ReportDateFilter $filter): array
    {
        $rows = DB::table('bulk_import_batches')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
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

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function topListings(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate->toDateString();
        $end   = $filter->endDate->toDateString();

        $rows = DB::table('user_properties as p')
            ->where('p.user_id', $userId)
            ->leftJoin('user_property_contents as pc', 'pc.property_id', '=', 'p.id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->leftJoin('user_districts as d', 'd.id', '=', 'pc.state_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->leftJoin(DB::raw(
                "(SELECT page_slug, SUM(views_count) as total_views
                  FROM pageview_analytics
                  WHERE tenant_id = {$userId} AND page_type = 'property'
                  AND date_bucket BETWEEN '{$start}' AND '{$end}'
                  GROUP BY page_slug) as pv"
            ), 'pv.page_slug', '=', 'pc.slug')
            ->selectRaw(
                "p.id, pc.title, p.property_type, p.purpose, p.price,
                 COALESCE(c.name_ar, c.name_en, '') as city,
                 COALESCE(d.name_ar, d.name_en, '') as district,
                 COALESCE(pv.total_views, 0) as view_count,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name"
            )
            ->orderByDesc('view_count')
            ->limit(10)
            ->get()
            ->map(function ($r) use ($userId) {
                $inquiries = DB::table('users_property_requests')
                    ->where('user_id', $userId)
                    ->where('initial_property_id', $r->id)
                    ->count();

                return [
                    'title'        => $r->title,
                    'type'         => $r->property_type,
                    'city'         => $r->city,
                    'district'     => $r->district,
                    'purpose'      => $r->purpose,
                    'price'        => (float) $r->price,
                    'view_count'   => (int) $r->view_count,
                    'inquiry_count' => $inquiries,
                    'agent_name'   => trim((string) $r->agent_name),
                ];
            })
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function agentPerformance(int $userId, ReportDateFilter $filter, int $page, int $limit, ?int $actorId = null): array
    {
        $start = $filter->startDate->toDateString();
        $end   = $filter->endDate->toDateString();

        $query = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee');

        if ($actorId !== null) {
            $query->where('u.id', $actorId);
        }

        $total = (clone $query)->count();

        $rows = (clone $query)
            ->leftJoin('user_properties as p', function ($j) use ($userId) {
                $j->on('p.created_by', '=', 'u.id')->where('p.user_id', $userId)->where('p.status', 1);
            })
            ->selectRaw(
                "u.id,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                 COUNT(DISTINCT p.id) as active_listings_count"
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'agent_name'              => trim((string) $r->agent_name),
                'active_listings_count'   => (int) $r->active_listings_count,
                'total_views_generated'   => 0,
                'inquiries_received'      => 0,
                'avg_days_listed_before_inquiry' => null,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }
}
