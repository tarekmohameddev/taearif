<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class PlatformReportService
{
    public function summary(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $prevStart = (clone $start)->subMonth();
        $prevEnd   = (clone $end)->subMonth();

        $revenue     = $this->sumRevenue($userId, $start, $end);
        $prevRevenue = $this->sumRevenue($userId, $prevStart, $prevEnd);

        $properties     = DB::table('user_properties')->where('user_id', $userId)->count();
        $prevProperties = DB::table('user_properties')->where('user_id', $userId)
            ->where('created_at', '<', $prevEnd)->count();

        $employees     = DB::table('users')->where('tenant_id', $userId)->where('account_type', 'employee')->where('active', 1)->count();
        $prevEmployees = $employees;

        $avgResponseMin     = $this->avgResponseTime($userId, $start, $end);
        $prevAvgResponseMin = $this->avgResponseTime($userId, $prevStart, $prevEnd);

        $mom = function ($cur, $prev): ?float {
            if ($prev === null || $prev == 0) {
                return null;
            }
            return round(($cur - $prev) / $prev * 100, 1);
        };

        return [
            'total_revenue'            => $revenue,
            'total_properties'         => $properties,
            'active_employees'         => $employees,
            'avg_response_time_min'    => $avgResponseMin,
            'mom_revenue'              => $mom($revenue, $prevRevenue),
            'mom_properties'           => $mom($properties, $prevProperties),
            'mom_employees'            => $mom($employees, $prevEmployees),
            'mom_avg_response_time'    => null,
            'generated_at'             => now()->toISOString(),
        ];
    }

    public function overviewRevenue(int $userId): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->startOfMonth();
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $revenue  = $this->sumRevenue($userId, $start, $end);
            $expenses = $this->sumExpenses($userId, $start, $end);

            $rows[] = [
                'month'    => $month->format('Y-m'),
                'revenue'  => $revenue,
                'expenses' => $expenses,
            ];
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function overviewPortfolio(int $userId): array
    {
        $rows = DB::table('user_properties')
            ->where('user_id', $userId)
            ->selectRaw('property_type, COUNT(*) as count, SUM(COALESCE(price, 0)) as total_worth')
            ->groupBy('property_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'type'        => $r->property_type,
                'count'       => (int) $r->count,
                'total_worth' => (float) $r->total_worth,
            ])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function employees(int $userId, int $page, int $limit, ?int $actorId = null): array
    {
        $query = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee');

        if ($actorId !== null) {
            $query->where('u.id', $actorId);
        }

        $total = (clone $query)->count();

        $rows = (clone $query)
            ->leftJoin('user_properties as p', function ($j) use ($userId) {
                $j->on('p.created_by', '=', 'u.id')->where('p.user_id', $userId);
            })
            ->selectRaw(
                "u.id, u.first_name, u.last_name, u.account_type as role,
                 COUNT(DISTINCT p.id) as properties_listed"
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'name'             => trim("{$r->first_name} {$r->last_name}"),
                'role'             => $r->role,
                'properties_listed' => (int) $r->properties_listed,
                'deals_closed'     => 0,
                'revenue_generated' => 0.0,
                'commission_earned' => 0.0,
                'rating'           => null,
                'performance_score' => null,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function geographicCities(int $userId): array
    {
        $rows = DB::table('user_property_contents as pc')
            ->join('user_properties as p', 'p.id', '=', 'pc.property_id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->leftJoin('users as u', 'u.tenant_id', '=', DB::raw($userId))
            ->where('p.user_id', $userId)
            ->selectRaw(
                "COALESCE(c.name_ar, c.name_en, 'Unknown') as city_name,
                 COUNT(DISTINCT p.id) as property_count,
                 SUM(p.price) as revenue,
                 0 as agent_count,
                 'active' as status"
            )
            ->groupBy('c.id', 'c.name_ar', 'c.name_en')
            ->orderByDesc('property_count')
            ->get()
            ->map(fn ($r) => [
                'city_name'      => $r->city_name,
                'property_count' => (int) $r->property_count,
                'revenue'        => (float) $r->revenue,
                'agent_count'    => 0,
                'status'         => 'active',
            ])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function geographicAreas(int $userId): array
    {
        $rows = DB::table('user_property_contents as pc')
            ->join('user_properties as p', 'p.id', '=', 'pc.property_id')
            ->leftJoin('user_districts as d', 'd.id', '=', 'pc.state_id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'd.city_id')
            ->where('p.user_id', $userId)
            ->selectRaw(
                "COALESCE(d.name_ar, d.name_en, 'Unknown') as area_name,
                 COALESCE(c.name_ar, c.name_en, '') as city_name,
                 SUM(CASE WHEN p.purpose = 'rent' THEN 1 ELSE 0 END) as rental_count,
                 SUM(CASE WHEN p.purpose = 'sale' THEN 1 ELSE 0 END) as sale_count,
                 SUM(CASE WHEN p.purpose = 'rent' THEN COALESCE(p.price,0) ELSE 0 END) as total_rental_value,
                 SUM(CASE WHEN p.purpose = 'sale' THEN COALESCE(p.price,0) ELSE 0 END) as total_sale_value,
                 SUM(CASE WHEN p.purpose = 'rent' AND p.status = 1 THEN 1 ELSE 0 END) as available_rentals,
                 SUM(CASE WHEN p.purpose = 'sale' AND p.status = 1 THEN 1 ELSE 0 END) as available_sales"
            )
            ->groupBy('d.id', 'd.name_ar', 'd.name_en', 'c.name_ar', 'c.name_en')
            ->orderByDesc('rental_count')
            ->get()
            ->map(fn ($r) => [
                'area_name'          => $r->area_name,
                'city'               => $r->city_name,
                'rental_count'       => (int) $r->rental_count,
                'sale_count'         => (int) $r->sale_count,
                'total_rental_value' => (float) $r->total_rental_value,
                'total_sale_value'   => (float) $r->total_sale_value,
                'available_rentals'  => (int) $r->available_rentals,
                'available_sales'    => (int) $r->available_sales,
            ])
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function reservationStatus(int $userId): array
    {
        $rows = DB::table('reservations')
            ->where('tenant_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'data' => [
                'reserved'    => (int) ($rows['active'] ?? $rows['reserved'] ?? 0),
                'available'   => DB::table('user_properties')
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->count(),
                'under_offer' => (int) ($rows['pending'] ?? $rows['under_offer'] ?? 0),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function propertyDetails(int $userId, ReportDateFilter $filter, int $page, int $limit): array
    {
        $query = DB::table('user_properties as p')
            ->where('p.user_id', $userId)
            ->leftJoin('user_property_contents as pc', 'pc.property_id', '=', 'p.id')
            ->leftJoin('user_cities as c', 'c.id', '=', 'pc.city_id')
            ->leftJoin('user_districts as d', 'd.id', '=', 'pc.state_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by');

        $total = (clone $query)->distinct('p.id')->count();

        $rows = (clone $query)
            ->selectRaw(
                "p.id, pc.title, p.property_type, p.purpose, p.price, p.status,
                 COALESCE(c.name_ar, c.name_en, '') as city,
                 COALESCE(d.name_ar, d.name_en, '') as district,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                 p.created_at as listed_date"
            )
            ->groupBy('p.id', 'pc.title', 'p.property_type', 'p.purpose', 'p.price', 'p.status',
                      'c.name_ar', 'c.name_en', 'd.name_ar', 'd.name_en', 'u.first_name', 'u.last_name', 'p.created_at')
            ->orderByDesc('p.created_at')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'reference'  => $r->id,
                'title'      => $r->title,
                'type'       => $r->property_type,
                'city'       => $r->city,
                'district'   => $r->district,
                'purpose'    => $r->purpose,
                'price'      => (float) $r->price,
                'status'     => $r->status ? 'published' : 'draft',
                'agent_name' => trim((string) $r->agent_name),
                'listed_date' => $r->listed_date,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function financialMonthly(int $userId): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->startOfMonth();
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $revenue  = $this->sumRevenue($userId, $start, $end);
            $expenses = $this->sumExpenses($userId, $start, $end);
            $net      = $revenue - $expenses;

            $rows[] = [
                'month'      => $month->format('Y-m'),
                'revenue'    => $revenue,
                'expenses'   => $expenses,
                'net_profit' => $net,
                'target'     => null,
            ];
        }

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function financialSummary(int $userId): array
    {
        $ytdStart = Carbon::now()->startOfYear();
        $ytdEnd   = Carbon::now();

        $revenueYTD  = $this->sumRevenue($userId, $ytdStart, $ytdEnd);
        $expensesYTD = $this->sumExpenses($userId, $ytdStart, $ytdEnd);

        // Commission: affiliate_transactions uses affiliate_id, not user_id — guard gracefully
        $commissionYTD = 0.0;
        try {
            $affiliateId = DB::table('affiliates')->where('user_id', $userId)->value('id');
            if ($affiliateId) {
                $commissionYTD = (float) DB::table('affiliate_transactions')
                    ->where('affiliate_id', $affiliateId)
                    ->where('type', 'commission')
                    ->whereBetween('created_at', [$ytdStart, $ytdEnd])
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            $commissionYTD = 0.0;
        }

        return [
            'data' => [
                'total_revenue_ytd'   => $revenueYTD,
                'total_expenses_ytd'  => $expensesYTD,
                'net_profit_ytd'      => $revenueYTD - $expensesYTD,
                'commission_paid_ytd' => $commissionYTD,
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function performanceAlerts(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $alerts = [];

        // Conversion rate alert
        $totalViews = (int) DB::table('pageview_analytics')
            ->where('tenant_id', $userId)
            ->whereBetween('date_bucket', [$start->toDateString(), $end->toDateString()])
            ->sum('views_count');

        $totalInquiries = (int) DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $conversionRate = $totalViews > 0 ? round($totalInquiries / $totalViews * 100, 2) : 0.0;
        $targetConversion = 5.0;
        $alerts[] = [
            'metric'        => 'conversion_rate',
            'current_value' => $conversionRate,
            'target_value'  => $targetConversion,
            'status'        => $conversionRate >= $targetConversion
                ? 'ok'
                : ($conversionRate >= $targetConversion * 0.5 ? 'warning' : 'critical'),
            'message'       => "Views to inquiries conversion is {$conversionRate}%",
        ];

        // Response time alert
        $avgResponseMs = (float) DB::table('wa_ai_response_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->avg('response_time_ms');

        $avgResponseMin = $avgResponseMs > 0 ? round($avgResponseMs / 60000, 2) : null;
        if ($avgResponseMin !== null) {
            $targetResponseMin = 5.0;
            $alerts[] = [
                'metric'        => 'avg_response_time_min',
                'current_value' => $avgResponseMin,
                'target_value'  => $targetResponseMin,
                'status'        => $avgResponseMin <= $targetResponseMin
                    ? 'ok'
                    : ($avgResponseMin <= $targetResponseMin * 2 ? 'warning' : 'critical'),
                'message'       => "Average AI response time is {$avgResponseMin} minutes",
            ];
        }

        // Unresolved conversations alert
        $unresolvedConvs = (int) DB::table('wa_conversation_states')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'active'])
            ->count();

        $targetMaxUnresolved = 50;
        $alerts[] = [
            'metric'        => 'unresolved_conversations',
            'current_value' => $unresolvedConvs,
            'target_value'  => $targetMaxUnresolved,
            'status'        => $unresolvedConvs <= $targetMaxUnresolved
                ? 'ok'
                : ($unresolvedConvs <= $targetMaxUnresolved * 2 ? 'warning' : 'critical'),
            'message'       => "{$unresolvedConvs} unresolved WhatsApp conversations",
        ];

        return ['data' => $alerts, 'generated_at' => now()->toISOString()];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sumRevenue(int $userId, Carbon $start, Carbon $end): float
    {
        $rms = (float) DB::table('rm_payment_installments as rpi')
            ->join('rm_rentals as rr', 'rr.id', '=', 'rpi.rental_id')
            ->where('rr.user_id', $userId)
            ->where('rpi.status', 'paid')
            ->whereBetween('rpi.paid_at', [$start, $end])
            ->sum('rpi.amount');

        $sales = (float) DB::table('sales')
            ->where('user_id', $userId)
            ->whereBetween('sale_date', [$start, $end])
            ->sum('sale_price');

        return round($rms + $sales, 2);
    }

    private function sumExpenses(int $userId, Carbon $start, Carbon $end): float
    {
        try {
            return (float) round(
                DB::table('rm_expenses')
                    ->where('user_id', $userId)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount_value'),
                2
            );
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function avgResponseTime(int $userId, Carbon $start, Carbon $end): ?float
    {
        $ms = DB::table('wa_ai_response_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->avg('response_time_ms');

        return $ms !== null ? round((float) $ms / 60000, 2) : null;
    }
}
