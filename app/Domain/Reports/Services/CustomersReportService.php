<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
use Illuminate\Support\Facades\DB;

final class CustomersReportService
{
    public function summary(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $base = fn () => DB::table('api_customers')->where('user_id', $userId);

        $totalCustomers  = ($base)()->count();
        $newCustomers    = ($base)()->whereBetween('created_at', [$start, $end])->count();
        $activeCustomers = ($base)()->whereBetween('updated_at', [$start, $end])->count();

        // Incoming requests (property requests + inquiries in period)
        $requestsTotal = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $requestsBySource = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('source, COUNT(*) as cnt')
            ->groupBy('source')
            ->pluck('cnt', 'source')
            ->toArray();

        // Avg time to first action: approx by checking first reminder/appointment date vs request creation
        $avgFirstActionMin = DB::table('users_property_requests as upr')
            ->where('upr.user_id', $userId)
            ->whereBetween('upr.created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, upr.created_at, upr.updated_at)) as avg_min')
            ->value('avg_min');

        // Active appointments (using customers_hub)
        $activeAppointments = DB::table('property_request_appointments')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        // Completed tasks in period
        $completedTasks = DB::table('reminders')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        // Deals (customers in closing/deal_completed stages)
        $closingStageIds = DB::table('customers_hub_stages')
            ->whereIn('stage_id', ['deal_completed', 'closing'])
            ->pluck('stage_id')
            ->toArray();

        $closedDeals = 0;
        $totalDealValue = 0.0;
        if (!empty($closingStageIds)) {
            $closedDeals = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereIn('customers_hub_stage_id', $closingStageIds)
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            try {
                $totalDealValue = (float) DB::table('api_customers')
                    ->where('user_id', $userId)
                    ->whereIn('customers_hub_stage_id', $closingStageIds)
                    ->whereBetween('updated_at', [$start, $end])
                    ->sum('deal_value');
            } catch (\Exception $e) {
                $totalDealValue = 0.0;
            }
        }

        $avgDealValue = $closedDeals > 0 ? round($totalDealValue / $closedDeals, 2) : 0.0;

        // Conversion rate
        $conversionRate = $requestsTotal > 0
            ? round($closedDeals / $requestsTotal * 100, 2)
            : 0.0;

        // Avg days to close
        $avgDaysToClose = null;
        try {
            $avgDaysToClose = DB::table('api_customers')
                ->where('user_id', $userId)
                ->whereIn('customers_hub_stage_id', $closingStageIds)
                ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));
            if ($avgDaysToClose !== null) {
                $avgDaysToClose = (float) round($avgDaysToClose, 1);
            }
        } catch (\Exception $e) {
            $avgDaysToClose = null;
        }

        return [
            'total_customers'         => $totalCustomers,
            'new_customers'           => $newCustomers,
            'active_customers'        => $activeCustomers,
            'total_incoming_requests' => $requestsTotal,
            'requests_by_source'      => [
                'inquiry'   => (int) ($requestsBySource['inquiry'] ?? 0),
                'whatsapp'  => (int) ($requestsBySource['whatsapp'] ?? 0),
                'manual'    => (int) ($requestsBySource['manual'] ?? 0),
                'import'    => (int) ($requestsBySource['import'] ?? 0),
                'referral'  => (int) ($requestsBySource['referral'] ?? 0),
                'website'   => (int) ($requestsBySource['website'] ?? 0),
                'affiliate' => (int) ($requestsBySource['affiliate'] ?? 0),
            ],
            'avg_time_to_first_action_min' => $avgFirstActionMin ? round((float) $avgFirstActionMin, 1) : null,
            'active_appointments'          => $activeAppointments,
            'completed_tasks'              => $completedTasks,
            'conversion_rate'              => $conversionRate,
            'total_deal_value'             => $totalDealValue,
            'avg_deal_value'               => $avgDealValue,
            'avg_days_to_close'            => $avgDaysToClose,
            'generated_at'                 => now()->toISOString(),
        ];
    }

    public function requestsBySource(int $userId, ReportDateFilter $filter): array
    {
        $rows = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
            ->selectRaw('COALESCE(source, "unknown") as source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['source' => $r->source, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function pipelineFunnel(int $userId, ReportDateFilter $filter): array
    {
        $stages = DB::table('customers_hub_stages')
            ->orderBy('order')
            ->get(['stage_id', 'stage_name_en', 'stage_name_ar']);

        $counts = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
            ->selectRaw('customers_hub_stage_id, COUNT(*) as cnt')
            ->groupBy('customers_hub_stage_id')
            ->pluck('cnt', 'customers_hub_stage_id')
            ->toArray();

        $avgDays = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->selectRaw('customers_hub_stage_id, AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->groupBy('customers_hub_stage_id')
            ->pluck('avg_days', 'customers_hub_stage_id')
            ->toArray();

        $funnel = [];
        $prevCount = null;

        foreach ($stages as $stage) {
            $count = (int) ($counts[$stage->stage_id] ?? 0);
            $conversionFromPrev = null;
            if ($prevCount !== null && $prevCount > 0) {
                $conversionFromPrev = round($count / $prevCount * 100, 2);
            }
            $funnel[] = [
                'stage_id'               => $stage->stage_id,
                'stage_name'             => $stage->stage_name_en,
                'customer_count'         => $count,
                'conversion_from_prev'   => $conversionFromPrev,
                'avg_days_in_stage'      => isset($avgDays[$stage->stage_id])
                    ? round((float) $avgDays[$stage->stage_id], 1)
                    : null,
            ];
            if ($count > 0) {
                $prevCount = $count;
            }
        }

        return ['data' => $funnel, 'generated_at' => now()->toISOString()];
    }

    public function dailyNewCustomers(int $userId, ReportDateFilter $filter): array
    {
        $granularity = $filter->granularity();
        $dateFormat  = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $rows = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date_label, COUNT(*) as count")
            ->groupByRaw("DATE_FORMAT(created_at, '{$dateFormat}')")
            ->orderBy('date_label')
            ->get()
            ->map(fn ($r) => ['date' => $r->date_label, 'count' => (int) $r->count])
            ->toArray();

        return ['granularity' => $granularity, 'data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function lifecycleDistribution(int $userId): array
    {
        $rows = DB::table('api_customers as ac')
            ->leftJoin('customers_hub_stages as chs', 'chs.stage_id', '=', 'ac.customers_hub_stage_id')
            ->where('ac.user_id', $userId)
            ->selectRaw('COALESCE(chs.stage_name_en, ac.customers_hub_stage_id, "unassigned") as stage_name, COUNT(*) as count')
            ->groupBy('chs.stage_name_en', 'ac.customers_hub_stage_id')
            ->get()
            ->map(fn ($r) => ['stage' => $r->stage_name, 'count' => (int) $r->count])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function agentPerformance(int $userId, ReportDateFilter $filter, int $page, int $limit, ?int $actorId = null): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $query = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee');

        if ($actorId !== null) {
            $query->where('u.id', $actorId);
        }

        $total = (clone $query)->count();

        $rows = (clone $query)
            ->leftJoin('users_property_requests as upr', function ($j) use ($userId, $start, $end) {
                $j->on('upr.responsible_employee_id', '=', 'u.id')
                  ->where('upr.user_id', $userId)
                  ->whereBetween('upr.created_at', [$start, $end]);
            })
            ->leftJoin('api_customers as ac', function ($j) use ($userId) {
                $j->on('ac.responsible_employee_id', '=', 'u.id')
                  ->where('ac.user_id', $userId);
            })
            ->selectRaw(
                "u.id,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                 COUNT(DISTINCT upr.id) as requests_handled,
                 COUNT(DISTINCT ac.id) as customers_assigned"
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'agent_name'         => trim((string) $r->agent_name),
                'customers_assigned' => (int) $r->customers_assigned,
                'requests_handled'   => (int) $r->requests_handled,
                'avg_response_time_min' => null,
                'deals_closed'       => null,
                'revenue_generated'  => 0.0,
                'conversion_rate'    => null,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function topDeals(int $userId, ReportDateFilter $filter): array
    {
        // Top deals: requests in closing stages ordered by deal value (if column exists)
        $closingStageIds = DB::table('customers_hub_stages')
            ->whereIn('stage_id', ['deal_completed', 'closing'])
            ->pluck('stage_id')
            ->toArray();

        if (empty($closingStageIds)) {
            return ['data' => [], 'generated_at' => now()->toISOString()];
        }

        try {
            $rows = DB::table('api_customers as ac')
                ->leftJoin('users as u', 'u.id', '=', 'ac.responsible_employee_id')
                ->where('ac.user_id', $userId)
                ->whereIn('ac.customers_hub_stage_id', $closingStageIds)
                ->whereBetween('ac.updated_at', [$filter->startDate, $filter->endDate])
                ->selectRaw(
                    "ac.name as customer_name,
                     COALESCE(ac.deal_value, 0) as deal_value,
                     ac.customers_hub_stage_id as stage,
                     CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                     DATEDIFF(NOW(), ac.created_at) as days_in_pipeline,
                     ac.updated_at as close_date"
                )
                ->orderByDesc('deal_value')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'customer_name'    => $r->customer_name,
                    'deal_value'       => (float) $r->deal_value,
                    'stage'            => $r->stage,
                    'agent_name'       => trim((string) $r->agent_name),
                    'days_in_pipeline' => (int) $r->days_in_pipeline,
                    'close_date'       => $r->close_date,
                ])
                ->toArray();
        } catch (\Exception $e) {
            $rows = [];
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }
}
