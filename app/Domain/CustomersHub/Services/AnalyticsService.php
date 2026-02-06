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
     * Get trends data with multiple metrics over time.
     */
    public function getTrends(int $userId, array $timeRange, array $metrics = ['newCustomers', 'completedTasks', 'appointments']): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $trends = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $trendData = ['date' => $dateStr];

            foreach ($metrics as $metric) {
                switch ($metric) {
                    case 'newCustomers':
                        $trendData['newCustomers'] = DB::table('api_customers')
                            ->where('user_id', $userId)
                            ->whereBetween('created_at', [$dayStart, $dayEnd])
                            ->count();
                        break;

                    case 'completedTasks':
                        $trendData['completedTasks'] = DB::table('reminders')
                            ->where('user_id', $userId)
                            ->whereBetween('created_at', [$dayStart, $dayEnd])
                            ->whereNotNull('completed_at')
                            ->whereNull('deleted_at')
                            ->count();
                        break;

                    case 'appointments':
                        $trendData['appointments'] = DB::table('users_api_customers_appointments')
                            ->where('user_id', $userId)
                            ->whereBetween('created_at', [$dayStart, $dayEnd])
                            ->count();
                        break;
                }
            }

            $trends[] = $trendData;
            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get source analytics with conversion rates.
     */
    public function getSources(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $totalCustomers = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $sources = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('source')
            ->select([
                'source',
                DB::raw('COUNT(*) as count'),
            ])
            ->get();

        $result = [];
        foreach ($sources as $source) {
            $sourceName = $source->source ?? 'other';
            $count = $source->count;
            $percentage = $totalCustomers > 0 ? round(($count / $totalCustomers) * 100, 2) : 0;

            // Calculate conversion rate (customers in closing/post_sale stages)
            $converted = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('source', $source->source)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('stage_id', function ($q) use ($userId) {
                    $q->select('id')->from('users_api_customers_stages')
                        ->where('user_id', $userId)
                        ->where(function($query) {
                            $query->where('stage_name', 'LIKE', '%closing%')
                                  ->orWhere('stage_name', 'LIKE', '%post_sale%');
                        });
                })
                ->count();

            $conversionRate = $count > 0 ? round(($converted / $count) * 100, 2) : 0;

            $result[] = [
                'source' => $sourceName,
                'count' => $count,
                'percentage' => $percentage . '%',
                'conversionRate' => $conversionRate . '%',
            ];
        }

        return $result;
    }

    /**
     * Get performance analytics for employees.
     */
    public function getPerformance(int $userId, array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        // Get all employees for this user
        $employees = DB::table('users')
            ->where('parent_id', $userId)
            ->orWhere('id', $userId)
            ->select('id', 'name')
            ->get();

        $result = [];
        foreach ($employees as $employee) {
            $employeeId = $employee->id;

            // Customers managed
            $customersManaged = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Tasks completed
            $tasksCompleted = DB::table('reminders')
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('completed_at')
                ->whereNull('deleted_at')
                ->count();

            // Conversion rate
            $totalCustomers = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $closedDeals = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('stage_id', function ($q) use ($userId) {
                    $q->select('id')->from('users_api_customers_stages')
                        ->where('user_id', $userId)
                        ->where(function($query) {
                            $query->where('stage_name', 'LIKE', '%closing%')
                                  ->orWhere('stage_name', 'LIKE', '%post_sale%');
                        });
                })
                ->count();

            $conversionRate = $totalCustomers > 0 ? round(($closedDeals / $totalCustomers) * 100, 2) : 0;

            // Average response time (simplified - using reminder response times)
            $avgResponseTime = DB::table('reminders')
                ->where('user_id', $userId)
                ->where('employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('completed_at')
                ->whereNull('deleted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
                ->value('avg_hours');

            $result[] = [
                'id' => $employeeId,
                'name' => $employee->name,
                'customersManaged' => $customersManaged,
                'tasksCompleted' => $tasksCompleted,
                'avgResponseTime' => $avgResponseTime ? round($avgResponseTime, 1) . ' hours' : 'N/A',
                'conversionRate' => $conversionRate . '%',
                'totalDeals' => $closedDeals,
            ];
        }

        return $result;
    }

    /**
     * Get time range start and end dates as array.
     */
    public function getTimeRangeDates(array $timeRange): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);
        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
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
