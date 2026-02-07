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

        // Average days in pipeline - use direct aggregate method for better performance
        $avgDays = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));

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
                            ->where('status', 'completed')
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
            ->where(function($query) use ($userId) {
                $query->where('tenant_id', $userId)
                      ->where('account_type', 'employee');
            })
            ->orWhere('id', $userId)  // Include the tenant owner themselves
            ->selectRaw('id, CASE WHEN account_type = "employee" THEN CONCAT(first_name, " ", last_name) ELSE username END as name')
            ->get();

        $result = [];
        foreach ($employees as $employee) {
            $employeeId = $employee->id;

            // Customers managed
            $customersManaged = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('responsible_employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Tasks completed (join through customers since reminders don't have employee_id)
            $tasksCompleted = DB::table('reminders')
                ->join('api_customers', 'reminders.customer_id', '=', 'api_customers.id')
                ->where('reminders.user_id', $userId)
                ->where('api_customers.responsible_employee_id', $employeeId)
                ->whereBetween('reminders.created_at', [$startDate, $endDate])
                ->where('reminders.status', 'completed')
                ->whereNull('reminders.deleted_at')
                ->count();

            // Conversion rate
            $totalCustomers = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('responsible_employee_id', $employeeId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $closedDeals = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('responsible_employee_id', $employeeId)
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

            // Average response time (using updated_at when status changed to completed)
            $avgResponseTime = DB::table('reminders')
                ->join('api_customers', 'reminders.customer_id', '=', 'api_customers.id')
                ->where('reminders.user_id', $userId)
                ->where('api_customers.responsible_employee_id', $employeeId)
                ->whereBetween('reminders.created_at', [$startDate, $endDate])
                ->where('reminders.status', 'completed')
                ->whereNull('reminders.deleted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, reminders.created_at, reminders.updated_at)) as avg_hours')
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
     * Get pipeline health data (dynamic stages with count, percentage, totalValue, avgDays).
     */
    public function getPipelineHealth(int $userId, array $timeRange, array $filters = []): array
    {
        [$startDate, $endDate] = $this->parseTimeRange($timeRange);

        $stages = DB::table('customers_hub_stages')
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['stage_id', 'stage_name_ar', 'stage_name_en', 'color', 'order']);

        $stageCounts = [];
        $stageValues = [];
        $stageAvgDays = [];
        $total = 0;

        foreach ($stages as $stage) {
            $baseQuery = DB::table('api_customers')
                ->where('user_id', $userId)
                ->where('customers_hub_stage_id', $stage->stage_id)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $this->applyPipelineHealthFilters($baseQuery, $filters);

            $count = (clone $baseQuery)->count();
            $stageCounts[$stage->stage_id] = $count;
            $total += $count;

            $totalValue = 0;
            try {
                $totalValue = (clone $baseQuery)->sum(DB::raw('COALESCE(deal_value, 0)'));
            } catch (\Exception $e) {
                // deal_value column may not exist
            }
            $stageValues[$stage->stage_id] = (float) $totalValue;

            $avgDays = (clone $baseQuery)->avg(DB::raw(
                'DATEDIFF(NOW(), COALESCE(customers_hub_stage_changed_at, updated_at))'
            ));
            $stageAvgDays[$stage->stage_id] = $avgDays !== null ? (int) round($avgDays) : 0;
        }

        $stagesData = [];
        foreach ($stages as $stage) {
            $count = $stageCounts[$stage->stage_id] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;

            $stagesData[] = [
                'stageId' => $stage->stage_id,
                'stageName' => $stage->stage_name_ar,
                'stageNameEn' => $stage->stage_name_en,
                'count' => $count,
                'percentage' => $percentage,
                'totalValue' => $stageValues[$stage->stage_id] ?? 0,
                'avgDays' => $stageAvgDays[$stage->stage_id] ?? 0,
                'color' => $stage->color,
            ];
        }

        return [
            'pipelineHealth' => [
                'stages' => $stagesData,
                'total' => $total,
            ],
            'timeRange' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
        ];
    }

    /**
     * Apply filters to pipeline health base query.
     */
    private function applyPipelineHealthFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        if (!empty($filters['priority']) && is_array($filters['priority'])) {
            $query->whereIn('priority_id', $filters['priority']);
        }
        if (!empty($filters['source']) && is_array($filters['source'])) {
            $query->whereIn('source', $filters['source']);
        }
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
