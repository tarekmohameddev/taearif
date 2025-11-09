<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Shared\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard Service
 *
 * Handles dashboard metrics and analytics
 */
class DashboardService extends BaseService
{
    /**
     * Get dashboard metrics
     *
     * @param string|null $metric Specific metric to return (null = all)
     * @param int $period Period in days for trends (default: 30)
     * @return array
     */
    public function getDashboardMetrics(?string $metric = null, int $period = 30): array
    {
        $metrics = [];

        // If no specific metric requested or 'properties' requested
        if (!$metric || $metric === 'properties') {
            $metrics['properties'] = $this->getPropertiesMetrics($period);
        }

        // If no specific metric requested or 'revenue' requested
        if (!$metric || $metric === 'revenue') {
            $metrics['revenue'] = $this->getRevenueMetrics($period);
        }

        // If no specific metric requested or 'users' requested
        if (!$metric || $metric === 'users') {
            $metrics['users'] = $this->getUsersMetrics($period);
        }

        // If no specific metric requested or 'subscriptions' requested
        if (!$metric || $metric === 'subscriptions') {
            $metrics['subscriptions'] = $this->getSubscriptionsMetrics($period);
        }

        return $metrics;
    }

    /**
     * Get properties metrics
     *
     * @param int $period
     * @return array
     */
    protected function getPropertiesMetrics(int $period): array
    {
        try {
            // Total properties
            $total = DB::table('user_properties')->count();

            // Active properties (status = 1 or active)
            $active = DB::table('user_properties')
                ->where(function ($query) {
                    $query->where('status', 1)
                          ->orWhere('is_active', 1);
                })
                ->count();

            // Calculate change percentage (last period vs current period)
            $previousPeriodStart = now()->subDays($period * 2);
            $previousPeriodEnd = now()->subDays($period);

            $currentPeriodCount = DB::table('user_properties')
                ->where('created_at', '>=', now()->subDays($period))
                ->count();

            $previousPeriodCount = DB::table('user_properties')
                ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
                ->count();

            $changePercentage = $previousPeriodCount > 0
                ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
                : 0;

            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
                'change_percentage' => round($changePercentage, 2),
                'period_days' => $period,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'change_percentage' => 0,
                'period_days' => $period,
                'note' => 'Properties table might not exist',
            ];
        }
    }

    /**
     * Get revenue metrics
     *
     * @param int $period
     * @return array
     */
    protected function getRevenueMetrics(int $period): array
    {
        // Total revenue from paid memberships
        $totalRevenue = DB::table('memberships')
            ->where('status', 1)
            ->sum('price');

        // Revenue for current period
        $currentPeriodRevenue = DB::table('memberships')
            ->where('status', 1)
            ->where('created_at', '>=', now()->subDays($period))
            ->sum('price');

        // Revenue for previous period
        $previousPeriodRevenue = DB::table('memberships')
            ->where('status', 1)
            ->whereBetween('created_at', [
                now()->subDays($period * 2),
                now()->subDays($period)
            ])
            ->sum('price');

        // Calculate change percentage
        $changePercentage = $previousPeriodRevenue > 0
            ? (($currentPeriodRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100
            : 0;

        // Monthly revenue trend (last 12 months)
        $monthlyTrend = DB::table('memberships')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(price) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 1)
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->revenue,
                    'count' => $item->count,
                ];
            });

        return [
            'total' => (float) $totalRevenue,
            'current_period' => (float) $currentPeriodRevenue,
            'previous_period' => (float) $previousPeriodRevenue,
            'change_percentage' => round($changePercentage, 2),
            'monthly_trend' => $monthlyTrend,
            'period_days' => $period,
        ];
    }

    /**
     * Get users metrics
     *
     * @param int $period
     * @return array
     */
    protected function getUsersMetrics(int $period): array
    {
        // Total tenant users
        $total = DB::table('users')
            ->where('account_type', 'tenant')
            ->count();

        // Active users (with active subscription)
        $active = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('active', 1)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('memberships')
                      ->whereColumn('memberships.user_id', 'users.id')
                      ->where('memberships.status', 1)
                      ->where('memberships.expire_date', '>=', now());
            })
            ->count();

        // New users this month
        $newThisMonth = DB::table('users')
            ->where('account_type', 'tenant')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Users in current period
        $currentPeriodCount = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('created_at', '>=', now()->subDays($period))
            ->count();

        // Users in previous period
        $previousPeriodCount = DB::table('users')
            ->where('account_type', 'tenant')
            ->whereBetween('created_at', [
                now()->subDays($period * 2),
                now()->subDays($period)
            ])
            ->count();

        // Calculate change percentage
        $changePercentage = $previousPeriodCount > 0
            ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
            : 0;

        // Monthly user growth trend (last 12 months)
        $monthlyTrend = DB::table('users')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('account_type', 'tenant')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'count' => $item->count,
                ];
            });

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'new_this_month' => $newThisMonth,
            'change_percentage' => round($changePercentage, 2),
            'monthly_trend' => $monthlyTrend,
            'period_days' => $period,
        ];
    }

    /**
     * Get subscriptions metrics
     *
     * @param int $period
     * @return array
     */
    protected function getSubscriptionsMetrics(int $period): array
    {
        // Active subscriptions
        $active = DB::table('memberships')
            ->where('status', 1)
            ->where('expire_date', '>=', now())
            ->count();

        // Expiring soon (within 7 days)
        $expiringSoon = DB::table('memberships')
            ->where('status', 1)
            ->whereBetween('expire_date', [
                now(),
                now()->addDays(7)
            ])
            ->count();

        // Expired (in last 30 days)
        $expired = DB::table('memberships')
            ->where('status', 1)
            ->whereBetween('expire_date', [
                now()->subDays(30),
                now()
            ])
            ->where('expire_date', '<', now())
            ->count();

        // Trial subscriptions
        $trial = DB::table('memberships')
            ->where('status', 1)
            ->where('is_trial', 1)
            ->where('expire_date', '>=', now())
            ->count();

        // Not renewed (expired and not renewed)
        $notRenewed = DB::table('memberships')
            ->where('status', 1)
            ->where('expire_date', '<', now()->subDays(7))
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('memberships as m2')
                      ->whereColumn('m2.user_id', 'memberships.user_id')
                      ->where('m2.status', 1)
                      ->where('m2.expire_date', '>=', now());
            })
            ->count();

        return [
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
            'trial' => $trial,
            'not_renewed' => $notRenewed,
        ];
    }

    /**
     * Get quick stats summary
     *
     * @return array
     */
    public function getQuickStats(): array
    {
        return [
            'total_users' => DB::table('users')->where('account_type', 'tenant')->count(),
            'active_subscriptions' => DB::table('memberships')
                ->where('status', 1)
                ->where('expire_date', '>=', now())
                ->count(),
            'total_revenue' => (float) DB::table('memberships')
                ->where('status', 1)
                ->sum('price'),
            'total_properties' => DB::table('user_properties')->count() ?? 0,
        ];
    }
}

