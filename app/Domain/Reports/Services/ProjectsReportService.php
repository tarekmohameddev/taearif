<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
use Illuminate\Support\Facades\DB;

final class ProjectsReportService
{
    public function summary(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $base = fn () => DB::table('user_projects')->where('user_id', $userId);

        $total     = ($base)()->count();
        $published = ($base)()->where('published', 1)->count();
        $draft     = ($base)()->where('published', 0)->count();
        $featured  = ($base)()->where('featured', 1)->count();
        $totalUnits = (int) ($base)()->sum('units');

        // Page visits from pageview_analytics for projects owned by this tenant
        $projectVisits = DB::table('pageview_analytics')
            ->where('tenant_id', $userId)
            ->where('page_type', 'project')
            ->whereBetween('date_bucket', [$start->toDateString(), $end->toDateString()])
            ->sum('views_count');

        // Inquiries: property requests that link to this tenant's project properties
        $projectIds = DB::table('user_projects')->where('user_id', $userId)->pluck('id');
        $projectPropertyIds = DB::table('user_properties')
            ->where('user_id', $userId)
            ->whereIn('project_id', $projectIds)
            ->pluck('id');

        $inquiryCount = 0;
        if ($projectPropertyIds->isNotEmpty()) {
            $inquiryCount = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereJsonContains('property_ids', $projectPropertyIds->toArray())
                ->orWhereIn('initial_property_id', $projectPropertyIds)
                ->whereBetween('created_at', [$start, $end])
                ->where('user_id', $userId)
                ->distinct('id')
                ->count();
        }

        $conversionRate = $projectVisits > 0
            ? round($inquiryCount / $projectVisits * 100, 2)
            : 0.0;

        return [
            'total_projects'      => $total,
            'published_projects'  => $published,
            'draft_projects'      => $draft,
            'featured_projects'   => $featured,
            'total_units'         => $totalUnits,
            'total_visits'        => (int) $projectVisits,
            'total_inquiries'     => $inquiryCount,
            'conversion_rate'     => $conversionRate,
            'generated_at'        => now()->toISOString(),
        ];
    }

    public function inquiriesTrend(int $userId, ReportDateFilter $filter): array
    {
        $granularity = $filter->granularity();
        $dateFormat  = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        // Inquiries linked to project properties
        $projectPropertyIds = DB::table('user_properties')
            ->where('user_id', $userId)
            ->whereNotNull('project_id')
            ->pluck('id');

        if ($projectPropertyIds->isEmpty()) {
            return ['granularity' => $granularity, 'data' => [], 'generated_at' => now()->toISOString()];
        }

        $rows = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereIn('initial_property_id', $projectPropertyIds)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date_label, COUNT(*) as count")
            ->groupByRaw("DATE_FORMAT(created_at, '{$dateFormat}')")
            ->orderBy('date_label')
            ->get()
            ->map(fn ($r) => ['date' => $r->date_label, 'count' => (int) $r->count])
            ->toArray();

        return ['granularity' => $granularity, 'data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function statusDistribution(int $userId): array
    {
        $base = DB::table('user_projects')->where('user_id', $userId);

        return [
            'data' => [
                'published' => (clone $base)->where('published', 1)->count(),
                'draft'     => (clone $base)->where('published', 0)->count(),
                'featured'  => (clone $base)->where('featured', 1)->count(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function topByVisits(int $userId, ReportDateFilter $filter): array
    {
        $rows = DB::table('pageview_analytics as pa')
            ->join('user_projects as p', function ($j) use ($userId) {
                $j->on('pa.page_slug', '=', DB::raw('(SELECT pc.slug FROM user_project_contents pc WHERE pc.project_id = p.id LIMIT 1)'))
                  ->orWhere('pa.page_path', 'LIKE', DB::raw("CONCAT('%/', p.id, '/%')"));
            })
            ->where('pa.tenant_id', $userId)
            ->where('pa.page_type', 'project')
            ->whereBetween('pa.date_bucket', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->selectRaw(
                "p.id, p.developer,
                 (SELECT pc2.title FROM user_project_contents pc2 WHERE pc2.project_id = p.id LIMIT 1) as project_name,
                 SUM(pa.views_count) as visit_count"
            )
            ->groupBy('p.id', 'p.developer')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'project_name' => $r->project_name,
                'visit_count'  => (int) $r->visit_count,
            ])
            ->toArray();

        // Fallback: if join is complex, get top projects by stored unit count
        if (empty($rows)) {
            $rows = DB::table('user_projects as p')
                ->where('p.user_id', $userId)
                ->leftJoin('user_project_contents as pc', 'pc.project_id', '=', 'p.id')
                ->selectRaw('p.id, pc.title as project_name, 0 as visit_count')
                ->orderByDesc('p.units')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'project_name' => $r->project_name,
                    'visit_count'  => 0,
                ])
                ->toArray();
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function projectsList(int $userId, ReportDateFilter $filter, int $page, int $limit): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $query = DB::table('user_projects as p')
            ->where('p.user_id', $userId)
            ->leftJoin('user_project_contents as pc', 'pc.project_id', '=', 'p.id');

        $total = (clone $query)->distinct('p.id')->count();

        $projects = (clone $query)
            ->selectRaw(
                "p.id, p.developer, p.published, p.featured, p.units, p.created_at,
                 pc.title as project_name"
            )
            ->groupBy('p.id', 'p.developer', 'p.published', 'p.featured', 'p.units', 'p.created_at', 'pc.title')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderByDesc('p.created_at')
            ->get();

        $projectIds = $projects->pluck('id');

        // Get visit counts per project
        $visits = DB::table('pageview_analytics')
            ->where('tenant_id', $userId)
            ->where('page_type', 'project')
            ->whereBetween('date_bucket', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('page_slug, SUM(views_count) as total_views')
            ->groupBy('page_slug')
            ->pluck('total_views', 'page_slug')
            ->toArray();

        $rows = $projects->map(function ($p) use ($visits, $userId, $start, $end) {
            // Inquiries for this project's properties
            $propIds = DB::table('user_properties')
                ->where('project_id', $p->id)
                ->where('user_id', $userId)
                ->pluck('id');

            $inquiryCount = $propIds->isNotEmpty()
                ? DB::table('users_property_requests')
                    ->where('user_id', $userId)
                    ->whereIn('initial_property_id', $propIds)
                    ->whereBetween('created_at', [$start, $end])
                    ->count()
                : 0;

            $visitCount = 0;
            foreach ($visits as $slug => $v) {
                if (str_contains((string) $slug, (string) $p->id)) {
                    $visitCount += (int) $v;
                }
            }

            $convRate = $visitCount > 0 ? round($inquiryCount / $visitCount * 100, 2) : 0.0;

            return [
                'project_name'   => $p->project_name,
                'developer'      => $p->developer,
                'status'         => $p->published ? 'published' : 'draft',
                'featured'       => (bool) $p->featured,
                'unit_count'     => (int) $p->units,
                'visit_count'    => $visitCount,
                'inquiry_count'  => $inquiryCount,
                'conversion_rate' => $convRate,
                'publish_date'   => $p->created_at,
            ];
        })->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }
}
