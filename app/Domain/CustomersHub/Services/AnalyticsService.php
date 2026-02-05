<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AnalyticsService
 * 
 * Handles analytics and reporting for Customers Hub.
 */
class AnalyticsService
{
    /**
     * Get key metrics for analytics dashboard.
     */
    public function getKeyMetrics(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $query = DB::table('api_customers')->where('user_id', $userId);

        // Total customers in period
        $totalCustomers = (clone $query)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Conversion rate
        $qualifiedCount = (clone $query)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%qualified%');
            })
            ->count();

        $closedCount = (clone $query)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%closing%')
                    ->orWhere('stage_name', 'LIKE', '%post_sale%');
            })
            ->count();

        $conversionRate = $qualifiedCount > 0 ? ($closedCount / $qualifiedCount) * 100 : 0;

        // Total deal value (if column exists, otherwise 0)
        $totalDealValue = 0;
        try {
            $totalDealValue = (clone $query)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('stage_id', function ($q) use ($userId) {
                    $q->select('id')->from('users_api_customers_stages')
                        ->where('user_id', $userId)
                        ->where('stage_name', 'LIKE', '%closing%')
                        ->orWhere('stage_name', 'LIKE', '%post_sale%');
                })
                ->sum(DB::raw('COALESCE(deal_value, 0)'));
        } catch (\Exception $e) {
            // Column doesn't exist, use 0
            $totalDealValue = 0;
        }

        // Average days in pipeline
        $avgDays = (clone $query)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days');

        // New/closed this month
        $monthStart = Carbon::now()->startOfMonth();
        $newThisMonth = (clone $query)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $closedThisMonth = (clone $query)
            ->where('updated_at', '>=', $monthStart)
            ->whereIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->count();

        return [
            'totalCustomers' => $totalCustomers,
            'conversionRate' => round($conversionRate, 2),
            'totalDealValue' => (float) $totalDealValue,
            'avgDaysInPipeline' => $avgDays ? (int) round($avgDays) : 0,
            'newThisMonth' => $newThisMonth,
            'closedThisMonth' => $closedThisMonth,
        ];
    }

    /**
     * Get distribution by stage.
     */
    public function getStageDistribution(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $distribution = DB::table('api_customers')
            ->join('users_api_customers_stages as s', 'api_customers.stage_id', '=', 's.id')
            ->where('api_customers.user_id', $userId)
            ->whereBetween('api_customers.created_at', [$startDate, $endDate])
            ->groupBy('s.id', 's.stage_name', 's.color')
            ->select([
                's.stage_name as label',
                's.color',
                DB::raw('COUNT(*) as count'),
            ])
            ->get()
            ->map(fn($item) => [
                'label' => $item->label,
                'count' => $item->count,
                'color' => $item->color,
            ])
            ->toArray();

        return $distribution;
    }

    /**
     * Get distribution by source.
     */
    public function getSourceDistribution(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $distribution = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('source')
            ->select([
                'source as label',
                DB::raw('COUNT(*) as count'),
            ])
            ->get()
            ->map(fn($item) => [
                'label' => $item->label ?? 'Unknown',
                'count' => $item->count,
            ])
            ->toArray();

        return $distribution;
    }

    /**
     * Get time-series data for customers over time.
     */
    public function getTimeSeries(int $userId, array $timeRange, string $interval = 'day'): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $format = match ($interval) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $series = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '{$format}')"))
            ->orderBy(DB::raw("DATE_FORMAT(created_at, '{$format}')"))
            ->select([
                DB::raw("DATE_FORMAT(created_at, '{$format}') as date"),
                DB::raw('COUNT(*) as count'),
            ])
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => $item->count,
            ])
            ->toArray();

        return $series;
    }

    /**
     * Get activity metrics (inquiries, appointments, reminders).
     */
    public function getActivityMetrics(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $inquiries = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $appointments = DB::table('users_api_customers_appointments')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $reminders = DB::table('reminders')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();

        return [
            'inquiries' => $inquiries,
            'appointments' => $appointments,
            'reminders' => $reminders,
            'totalActivity' => $inquiries + $appointments + $reminders,
        ];
    }

    /**
     * Parse time range array to start/end dates.
     */
    private function parseTimeRange(array $timeRange): array
    {
        $rangeType = $timeRange['timeRange'] ?? 'last30days';

        $now = Carbon::now();

        return match ($rangeType) {
            'today' => [$now->copy()->startOfDay(), $now],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last7days' => [$now->copy()->subDays(7), $now],
            'last30days' => [$now->copy()->subDays(30), $now],
            'thisMonth' => [$now->copy()->startOfMonth(), $now],
            'lastMonth' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ],
            'thisQuarter' => [$now->copy()->startOfQuarter(), $now],
            'lastQuarter' => [
                $now->copy()->subQuarter()->startOfQuarter(),
                $now->copy()->subQuarter()->endOfQuarter()
            ],
            'thisYear' => [$now->copy()->startOfYear(), $now],
            'lastYear' => [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear()
            ],
            'custom' => [
                isset($timeRange['customStartDate']) ? Carbon::parse($timeRange['customStartDate']) : $now->copy()->subDays(30),
                isset($timeRange['customEndDate']) ? Carbon::parse($timeRange['customEndDate']) : $now
            ],
            default => [$now->copy()->subDays(30), $now],
        };
    }
}
