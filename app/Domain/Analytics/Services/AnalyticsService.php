<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Shared\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Analytics Service
 *
 * Provides comprehensive SaaS business analytics including:
 * - MRR/ARR tracking
 * - Churn analysis
 * - Plan performance
 * - Customer lifetime value
 * - Cohort analysis
 * - Revenue forecasting
 */
class AnalyticsService extends BaseService
{
    /**
     * Get MRR (Monthly Recurring Revenue) analytics
     *
     * @param array $filters
     * @return array
     */
    public function getMrrAnalytics(array $filters = []): array
    {
        $months = $filters['months'] ?? 12;
        
        // Get monthly recurring revenue for each month
        $mrrData = DB::table('memberships')
            ->select(
                DB::raw('DATE_FORMAT(memberships.created_at, "%Y-%m") as month'),
                DB::raw('SUM(CASE 
                    WHEN packages.term = "monthly" THEN memberships.price
                    WHEN packages.term = "yearly" THEN memberships.price / 12
                    WHEN packages.term = "daily" THEN memberships.price * 30
                    WHEN packages.term = "weekly" THEN memberships.price * 4
                    ELSE 0
                END) as mrr'),
                DB::raw('COUNT(*) as subscription_count')
            )
            ->join('packages', 'memberships.package_id', '=', 'packages.id')
            ->where('memberships.status', 1)
            ->where('memberships.created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Calculate MRR breakdown (New, Expansion, Contraction, Churn)
        $currentMonth = now()->format('Y-m');
        $previousMonth = now()->subMonth()->format('Y-m');

        $currentMrr = $this->calculateMonthlyMrr($currentMonth);
        $previousMrr = $this->calculateMonthlyMrr($previousMonth);

        // MRR Growth
        $mrrGrowth = $previousMrr > 0 
            ? (($currentMrr - $previousMrr) / $previousMrr) * 100 
            : 0;

        // Calculate ARR (Annual Recurring Revenue)
        $arr = $currentMrr * 12;

        return [
            'current_mrr' => round($currentMrr, 2),
            'previous_mrr' => round($previousMrr, 2),
            'mrr_growth_rate' => round($mrrGrowth, 2),
            'arr' => round($arr, 2),
            'monthly_trend' => $mrrData->map(function ($item) {
                return [
                    'month' => $item->month,
                    'mrr' => round($item->mrr, 2),
                    'subscription_count' => $item->subscription_count,
                ];
            }),
            'mrr_breakdown' => $this->getMrrBreakdown($currentMonth),
        ];
    }

    /**
     * Calculate MRR for a specific month
     */
    protected function calculateMonthlyMrr(string $month): float
    {
        return DB::table('memberships')
            ->join('packages', 'memberships.package_id', '=', 'packages.id')
            ->where('memberships.status', 1)
            ->where('memberships.start_date', '<=', $month . '-31')
            ->where('memberships.expire_date', '>=', $month . '-01')
            ->sum(DB::raw('CASE 
                WHEN packages.term = "monthly" THEN memberships.price
                WHEN packages.term = "yearly" THEN memberships.price / 12
                WHEN packages.term = "daily" THEN memberships.price * 30
                WHEN packages.term = "weekly" THEN memberships.price * 4
                ELSE 0
            END'));
    }

    /**
     * Get MRR breakdown (New, Expansion, Contraction, Churned)
     */
    protected function getMrrBreakdown(string $month): array
    {
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        // New MRR (new subscriptions this month)
        $newMrr = DB::table('memberships')
            ->join('packages', 'memberships.package_id', '=', 'packages.id')
            ->where('memberships.status', 1)
            ->whereBetween('memberships.created_at', [$startOfMonth, $endOfMonth])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'memberships.user_id')
                    ->where('m2.created_at', '<', DB::raw('memberships.created_at'));
            })
            ->sum(DB::raw('CASE 
                WHEN packages.term = "monthly" THEN memberships.price
                WHEN packages.term = "yearly" THEN memberships.price / 12
                WHEN packages.term = "daily" THEN memberships.price * 30
                WHEN packages.term = "weekly" THEN memberships.price * 4
                ELSE 0
            END'));

        // Churned MRR (subscriptions that expired this month and not renewed)
        $churnedMrr = DB::table('memberships')
            ->join('packages', 'memberships.package_id', '=', 'packages.id')
            ->where('memberships.status', 1)
            ->whereBetween('memberships.expire_date', [$startOfMonth, $endOfMonth])
            ->whereNotExists(function ($query) use ($endOfMonth) {
                $query->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'memberships.user_id')
                    ->where('m2.created_at', '>', $endOfMonth);
            })
            ->sum(DB::raw('CASE 
                WHEN packages.term = "monthly" THEN memberships.price
                WHEN packages.term = "yearly" THEN memberships.price / 12
                WHEN packages.term = "daily" THEN memberships.price * 30
                WHEN packages.term = "weekly" THEN memberships.price * 4
                ELSE 0
            END'));

        return [
            'new_mrr' => round($newMrr, 2),
            'expansion_mrr' => 0, // Placeholder - implement upgrade logic
            'contraction_mrr' => 0, // Placeholder - implement downgrade logic
            'churned_mrr' => round($churnedMrr, 2),
        ];
    }

    /**
     * Get churn analytics
     *
     * @param array $filters
     * @return array
     */
    public function getChurnAnalytics(array $filters = []): array
    {
        $months = $filters['months'] ?? 12;
        $startDate = now()->subMonths($months);

        // Calculate customer churn rate
        $totalCustomersStart = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('created_at', '<', $startDate)
            ->count();

        $churnedCustomers = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('created_at', '<', $startDate)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships')
                    ->whereColumn('memberships.user_id', 'users.id')
                    ->where('memberships.status', 1)
                    ->where('memberships.expire_date', '>=', now());
            })
            ->count();

        $customerChurnRate = $totalCustomersStart > 0 
            ? ($churnedCustomers / $totalCustomersStart) * 100 
            : 0;

        // Monthly churn trend
        $monthlyChurn = DB::table('memberships')
            ->select(
                DB::raw('DATE_FORMAT(expire_date, "%Y-%m") as month'),
                DB::raw('COUNT(DISTINCT user_id) as churned_customers')
            )
            ->where('status', 1)
            ->where('expire_date', '>=', $startDate)
            ->where('expire_date', '<', now())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'memberships.user_id')
                    ->where('m2.created_at', '>', DB::raw('memberships.expire_date'));
            })
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Churn by plan
        $churnByPlan = DB::table('memberships')
            ->select(
                'packages.title',
                DB::raw('COUNT(DISTINCT memberships.user_id) as churned_count')
            )
            ->join('packages', 'memberships.package_id', '=', 'packages.id')
            ->where('memberships.status', 1)
            ->where('memberships.expire_date', '>=', $startDate)
            ->where('memberships.expire_date', '<', now())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'memberships.user_id')
                    ->where('m2.created_at', '>', DB::raw('memberships.expire_date'));
            })
            ->groupBy('packages.id', 'packages.title')
            ->get();

        return [
            'customer_churn_rate' => round($customerChurnRate, 2),
            'total_customers_at_start' => $totalCustomersStart,
            'churned_customers' => $churnedCustomers,
            'monthly_churn_trend' => $monthlyChurn,
            'churn_by_plan' => $churnByPlan,
            'period_months' => $months,
        ];
    }

    /**
     * Get plan performance analytics
     *
     * @return array
     */
    public function getPlanPerformance(): array
    {
        $plans = DB::table('packages')
            ->select(
                'packages.*',
                DB::raw('COUNT(memberships.id) as total_subscriptions'),
                DB::raw('SUM(CASE WHEN memberships.status = 1 AND memberships.expire_date >= NOW() THEN 1 ELSE 0 END) as active_subscriptions'),
                DB::raw('SUM(CASE WHEN memberships.status = 1 THEN memberships.price ELSE 0 END) as total_revenue'),
                DB::raw('AVG(CASE WHEN memberships.status = 1 THEN memberships.price ELSE NULL END) as avg_revenue')
            )
            ->leftJoin('memberships', 'packages.id', '=', 'memberships.package_id')
            ->where('packages.is_active', 1)
            ->groupBy('packages.id')
            ->get();

        // Calculate conversion rates
        $plansWithMetrics = $plans->map(function ($plan) {
            // Trial to paid conversion
            $trialSubscriptions = DB::table('memberships')
                ->where('package_id', $plan->id)
                ->where('is_trial', 1)
                ->count();

            $paidAfterTrial = DB::table('memberships')
                ->where('package_id', $plan->id)
                ->where('is_trial', 0)
                ->whereExists(function ($query) use ($plan) {
                    $query->select(DB::raw(1))
                        ->from('memberships as m2')
                        ->whereColumn('m2.user_id', 'memberships.user_id')
                        ->where('m2.package_id', $plan->id)
                        ->where('m2.is_trial', 1)
                        ->where('m2.created_at', '<', DB::raw('memberships.created_at'));
                })
                ->count();

            $conversionRate = $trialSubscriptions > 0 
                ? ($paidAfterTrial / $trialSubscriptions) * 100 
                : 0;

            return [
                'plan_id' => $plan->id,
                'plan_name' => $plan->title,
                'plan_price' => (float) $plan->price,
                'plan_term' => $plan->term,
                'total_subscriptions' => $plan->total_subscriptions,
                'active_subscriptions' => $plan->active_subscriptions,
                'total_revenue' => round($plan->total_revenue, 2),
                'avg_revenue_per_subscription' => round($plan->avg_revenue, 2),
                'trial_to_paid_conversion_rate' => round($conversionRate, 2),
            ];
        });

        return [
            'plans' => $plansWithMetrics,
            'total_active_plans' => $plans->count(),
        ];
    }

    /**
     * Get subscription lifecycle analytics
     *
     * @return array
     */
    public function getLifecycleAnalytics(): array
    {
        // New subscriptions trend
        $newSubscriptions = DB::table('memberships')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Renewal rate
        $totalExpired = DB::table('memberships')
            ->where('status', 1)
            ->where('expire_date', '<', now())
            ->where('expire_date', '>=', now()->subMonths(3))
            ->count();

        $renewed = DB::table('memberships as m1')
            ->where('m1.status', 1)
            ->where('m1.expire_date', '<', now())
            ->where('m1.expire_date', '>=', now()->subMonths(3))
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'm1.user_id')
                    ->where('m2.created_at', '>', DB::raw('m1.expire_date'))
                    ->where('m2.created_at', '<', DB::raw('DATE_ADD(m1.expire_date, INTERVAL 30 DAY)'));
            })
            ->count();

        $renewalRate = $totalExpired > 0 ? ($renewed / $totalExpired) * 100 : 0;

        // Average subscription duration
        $avgDuration = DB::table('memberships')
            ->where('status', 1)
            ->whereNotNull('start_date')
            ->whereNotNull('expire_date')
            ->select(DB::raw('AVG(DATEDIFF(expire_date, start_date)) as avg_days'))
            ->first();

        return [
            'new_subscriptions_trend' => $newSubscriptions,
            'renewal_rate' => round($renewalRate, 2),
            'total_expired_in_period' => $totalExpired,
            'renewed_count' => $renewed,
            'average_subscription_duration_days' => round($avgDuration->avg_days ?? 0, 0),
        ];
    }

    /**
     * Get Customer Lifetime Value (CLV) analytics
     *
     * @return array
     */
    public function getClvAnalytics(): array
    {
        // Average CLV calculation
        $customerMetrics = DB::table('users')
            ->select(
                DB::raw('AVG(total_spent) as avg_clv'),
                DB::raw('MAX(total_spent) as max_clv'),
                DB::raw('MIN(total_spent) as min_clv')
            )
            ->from(DB::raw('(
                SELECT 
                    users.id,
                    COALESCE(SUM(memberships.price), 0) as total_spent
                FROM users
                LEFT JOIN memberships ON users.id = memberships.user_id AND memberships.status = 1
                WHERE users.account_type = "tenant"
                GROUP BY users.id
            ) as user_spending'))
            ->first();

        // CLV by plan
        $clvByPlan = DB::table('packages')
            ->select(
                'packages.title',
                DB::raw('AVG(user_spending.total_spent) as avg_clv'),
                DB::raw('COUNT(DISTINCT user_spending.user_id) as customer_count')
            )
            ->join(DB::raw('(
                SELECT 
                    memberships.user_id,
                    memberships.package_id,
                    SUM(memberships.price) as total_spent
                FROM memberships
                WHERE memberships.status = 1
                GROUP BY memberships.user_id, memberships.package_id
            ) as user_spending'), 'packages.id', '=', 'user_spending.package_id')
            ->groupBy('packages.id', 'packages.title')
            ->get();

        return [
            'average_clv' => round($customerMetrics->avg_clv ?? 0, 2),
            'max_clv' => round($customerMetrics->max_clv ?? 0, 2),
            'min_clv' => round($customerMetrics->min_clv ?? 0, 2),
            'clv_by_plan' => $clvByPlan->map(function ($item) {
                return [
                    'plan_name' => $item->title,
                    'average_clv' => round($item->avg_clv, 2),
                    'customer_count' => $item->customer_count,
                ];
            }),
        ];
    }

    /**
     * Get cohort analysis
     *
     * @param array $filters
     * @return array
     */
    public function getCohortAnalytics(array $filters = []): array
    {
        $months = $filters['months'] ?? 12;
        $startDate = now()->subMonths($months)->startOfMonth();

        // Revenue cohorts by signup month
        $cohorts = [];
        
        for ($i = 0; $i < $months; $i++) {
            $cohortMonth = now()->subMonths($months - $i - 1)->format('Y-m');
            $cohortStart = Carbon::parse($cohortMonth . '-01')->startOfMonth();
            $cohortEnd = Carbon::parse($cohortMonth . '-01')->endOfMonth();

            // Get users who signed up in this cohort
            $cohortUsers = DB::table('users')
                ->where('account_type', 'tenant')
                ->whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->pluck('id');

            if ($cohortUsers->isEmpty()) {
                continue;
            }

            // Calculate revenue for each subsequent month
            $monthlyRevenue = [];
            for ($j = 0; $j <= min($i, 11); $j++) {
                $revenueMonth = Carbon::parse($cohortMonth)->addMonths($j)->format('Y-m');
                $revenueStart = Carbon::parse($revenueMonth . '-01')->startOfMonth();
                $revenueEnd = Carbon::parse($revenueMonth . '-01')->endOfMonth();

                $revenue = DB::table('memberships')
                    ->whereIn('user_id', $cohortUsers)
                    ->where('status', 1)
                    ->whereBetween('created_at', [$revenueStart, $revenueEnd])
                    ->sum('price');

                $monthlyRevenue[] = [
                    'month_offset' => $j,
                    'revenue' => round($revenue, 2),
                ];
            }

            $cohorts[] = [
                'cohort_month' => $cohortMonth,
                'cohort_size' => $cohortUsers->count(),
                'monthly_revenue' => $monthlyRevenue,
            ];
        }

        return [
            'cohorts' => $cohorts,
            'period_months' => $months,
        ];
    }

    /**
     * Get revenue forecast
     *
     * @param int $forecastMonths
     * @return array
     */
    public function getRevenueForecast(int $forecastMonths = 6): array
    {
        // Get historical MRR for last 12 months
        $historicalMrr = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $mrr = $this->calculateMonthlyMrr($month);
            $historicalMrr[] = [
                'month' => $month,
                'mrr' => $mrr,
            ];
        }

        // Simple linear regression for forecast
        $n = count($historicalMrr);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historicalMrr as $index => $data) {
            $x = $index;
            $y = $data['mrr'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Generate forecast
        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $month = now()->addMonths($i)->format('Y-m');
            $predictedMrr = $slope * ($n + $i - 1) + $intercept;
            
            $forecast[] = [
                'month' => $month,
                'predicted_mrr' => round(max(0, $predictedMrr), 2),
            ];
        }

        return [
            'historical_mrr' => array_map(function ($item) {
                return [
                    'month' => $item['month'],
                    'mrr' => round($item['mrr'], 2),
                ];
            }, $historicalMrr),
            'forecast' => $forecast,
            'forecast_months' => $forecastMonths,
            'growth_rate' => round($slope, 2),
        ];
    }

    /**
     * Get geographic analytics
     *
     * @return array
     */
    public function getGeographicAnalytics(): array
    {
        // Revenue by country (if country data exists in users table)
        $revenueByCountry = DB::table('users')
            ->select(
                'users.country',
                DB::raw('COUNT(DISTINCT users.id) as user_count'),
                DB::raw('COALESCE(SUM(memberships.price), 0) as total_revenue')
            )
            ->leftJoin('memberships', function ($join) {
                $join->on('users.id', '=', 'memberships.user_id')
                    ->where('memberships.status', '=', 1);
            })
            ->where('users.account_type', 'tenant')
            ->whereNotNull('users.country')
            ->groupBy('users.country')
            ->orderByDesc('total_revenue')
            ->get();

        return [
            'revenue_by_country' => $revenueByCountry->map(function ($item) {
                return [
                    'country' => $item->country ?? 'Unknown',
                    'user_count' => $item->user_count,
                    'total_revenue' => round($item->total_revenue, 2),
                ];
            }),
            'total_countries' => $revenueByCountry->count(),
        ];
    }

    /**
     * Get tenant activity metrics
     *
     * @return array
     */
    public function getActivityMetrics(): array
    {
        // Active vs inactive tenants
        $totalTenants = DB::table('users')
            ->where('account_type', 'tenant')
            ->count();

        $activeTenants = DB::table('users')
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

        // Feature usage
        $propertyUsage = DB::table('user_properties')
            ->select(
                DB::raw('COUNT(*) as total_properties'),
                DB::raw('COUNT(DISTINCT user_id) as users_with_properties'),
                DB::raw('AVG(properties_per_user) as avg_properties_per_user')
            )
            ->from(DB::raw('(
                SELECT user_id, COUNT(*) as properties_per_user
                FROM user_properties
                GROUP BY user_id
            ) as property_counts'))
            ->first();

        $customerUsage = DB::table('api_customers')
            ->select(
                DB::raw('COUNT(*) as total_customers'),
                DB::raw('COUNT(DISTINCT user_id) as users_with_customers')
            )
            ->first();

        return [
            'total_tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'inactive_tenants' => $totalTenants - $activeTenants,
            'activity_rate' => $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100, 2) : 0,
            'feature_usage' => [
                'properties' => [
                    'total' => $propertyUsage->total_properties ?? 0,
                    'users_using' => $propertyUsage->users_with_properties ?? 0,
                    'avg_per_user' => round($propertyUsage->avg_properties_per_user ?? 0, 2),
                ],
                'customers' => [
                    'total' => $customerUsage->total_customers ?? 0,
                    'users_using' => $customerUsage->users_with_customers ?? 0,
                ],
            ],
        ];
    }

    /**
     * Get referral analytics
     *
     * @return array
     */
    public function getReferralAnalytics(): array
    {
        // Total referral revenue
        $totalReferralRevenue = DB::table('affiliate_transactions')
            ->sum('amount');

        // Top affiliates
        $topAffiliates = DB::table('api_affiliate_users')
            ->select(
                'api_affiliate_users.*',
                DB::raw('COUNT(affiliate_transactions.id) as transaction_count'),
                DB::raw('COALESCE(SUM(affiliate_transactions.amount), 0) as total_earnings')
            )
            ->leftJoin('affiliate_transactions', 'api_affiliate_users.id', '=', 'affiliate_transactions.affiliate_id')
            ->groupBy('api_affiliate_users.id')
            ->orderByDesc('total_earnings')
            ->limit(10)
            ->get();

        // Referral conversion rate
        $totalReferrals = DB::table('users')
            ->whereNotNull('referred_by')
            ->count();

        $convertedReferrals = DB::table('users')
            ->whereNotNull('referred_by')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('memberships')
                    ->whereColumn('memberships.user_id', 'users.id')
                    ->where('memberships.status', 1);
            })
            ->count();

        $conversionRate = $totalReferrals > 0 ? ($convertedReferrals / $totalReferrals) * 100 : 0;

        return [
            'total_referral_revenue' => round($totalReferralRevenue, 2),
            'referral_conversion_rate' => round($conversionRate, 2),
            'total_referrals' => $totalReferrals,
            'converted_referrals' => $convertedReferrals,
            'top_affiliates' => $topAffiliates->map(function ($affiliate) {
                return [
                    'affiliate_id' => $affiliate->id,
                    'name' => $affiliate->name ?? 'N/A',
                    'email' => $affiliate->email ?? 'N/A',
                    'transaction_count' => $affiliate->transaction_count,
                    'total_earnings' => round($affiliate->total_earnings, 2),
                ];
            }),
        ];
    }

    /**
     * Get comprehensive analytics overview
     *
     * @return array
     */
    public function getOverview(): array
    {
        return [
            'mrr' => $this->getMrrAnalytics(['months' => 3]),
            'churn' => $this->getChurnAnalytics(['months' => 3]),
            'lifecycle' => $this->getLifecycleAnalytics(),
            'clv' => $this->getClvAnalytics(),
            'activity' => $this->getActivityMetrics(),
        ];
    }
}

