<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Admin\Models\Admin;
use App\Domain\Shared\Services\BaseService;
use App\Services\Admin\AdminDashboardBusinessMetricsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Dashboard Service
 *
 * Handles dashboard metrics and analytics
 */
class DashboardService extends BaseService
{
    public function __construct(
        private readonly AdminDashboardBusinessMetricsService $businessMetrics
    ) {
    }

    /**
     * Build minimal tenant profile payload for a single user.
     * - Returns only fields required by the profile UI.
     * - Visitors and revenue are returned as localized placeholder text.
     */
    public function getTenantProfile(int $userId): ?array
    {
        // Fetch tenant user
        $user = DB::table('users')
            ->where('id', $userId)
            ->where('account_type', 'tenant')
            ->first();

        if (!$user) {
            return null;
        }

        // Resolve display name (site_name -> username)
        $siteName = DB::table('api_general_settings')
            ->where('user_id', $userId)
            ->value('site_name');
        if (in_array($siteName, ['N/A', __('N/A')], true) || $siteName === null || trim((string) $siteName) === '') {
            $siteName = $user->username;
        }
        $displayName = $siteName ?: __('Tenant');

        // Domain from username + app host
        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;
        $domain = null;
        if (!empty($user->username) && $appHost) {
            $domain = "https://{$user->username}.{$appHost}/ar";
        }

        // Latest active membership
        $latestMembership = DB::table('memberships as m')
            ->select('m.id', 'm.package_id', 'm.price', 'm.currency', 'm.start_date', 'm.expire_date', 'm.is_trial', 'm.trial_days', 'p.title as plan_title')
            ->leftJoin('packages as p', 'p.id', '=', 'm.package_id')
            ->where('m.user_id', $userId)
            ->where('m.status', 1)
            ->orderByDesc('m.start_date')
            ->orderByDesc('m.id')
            ->first();

        // Determine account status
        $now = now();
        $status = 'inactive';
        if ($latestMembership && !empty($latestMembership->expire_date)) {
            $expiresAt = \Carbon\Carbon::parse($latestMembership->expire_date);
            $status = $expiresAt->greaterThanOrEqualTo($now) ? 'active' : 'expired';
        }

        // KPI counts (projects/properties)
        $projectsTotal = (int) DB::table('user_projects')
            ->where('user_id', $userId)
            ->count();

        $projectsUnderCreation = (int) DB::table('user_projects')
            ->where('user_id', $userId)
            ->where('published', 0)
            ->count();

        $propertiesTotal = (int) DB::table('user_properties')
            ->where('user_id', $userId)
            ->count();

        $propertiesActivated = (int) DB::table('user_properties')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->count();

        return [
            'user' => [
                'id' => (int) $user->id,
                'name' => $displayName,
                'status' => $status,
            ],
            'kpis' => [
                'revenue_total_sar' => __('coming soon'),
                'visitors_30d' => __('coming soon'),
                'projects_total' => $projectsTotal,
                'projects_under_creation' => $projectsUnderCreation,
                'properties_total' => $propertiesTotal,
                'properties_activated' => $propertiesActivated,
            ],
            'subscription' => [
                'plan_name' => $latestMembership->plan_title ?? __('N/A'),
                'registered_at' => optional($user->created_at)->toDateString() ?? null,
                'expires_at' => $latestMembership->expire_date ?? null,
                'last_login_at' => optional($user->last_login_at)->toISOString() ?? null,
            ],
            'account' => [
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'domain' => $domain ?? null,
                'company_size' => $user->company_size ?? null,
                'industry' => $user->industry_type ?? null,
            ],
        ];
    }
    /**
     * Get dashboard metrics
     *
     * @param string|null $metric Specific metric to return (null = all)
     * @param int $period Period in days for trends (default: 30)
     * @return array
     */
    public function getDashboardMetrics(?string $metric = null, int $period = 30): array
    {
        $metric = $metric ?? 'all';
        $propertiesTypeItems = $this->getPropertyTypeMetrics();
        $businessMetrics = $this->businessMetricsPayload();

        $limitInput = request()->input('tenants.limit');
        if ($limitInput === null) {
            $limitInput = request()->input('limit', 10);
        }
        $limit = (int) $limitInput;
        $limit = max(1, min(100, $limit));

        $offsetInput = request()->input('tenants.offset');
        if ($offsetInput === null) {
            $offsetInput = request()->input('offset', 0);
        }
        $offset = (int) $offsetInput;
        $offset = max(0, $offset);

        $tenantsResult = $this->getTenantsOverview($limit, $offset);
        $totalTenants = $tenantsResult['total'];
        $totalPages = $limit > 0 ? (int) ceil($totalTenants / $limit) : 0;

        $payload = [
            'dashboard' => array_merge(
                $this->getDashboardCards($period),
                ['business_metrics' => $businessMetrics]
            ),
            'metrics' => [
                'activity' => $this->getActivityMetrics($period),
            ],
            'business_metrics' => $businessMetrics,
            'properties_type' => [
                'items' => $propertiesTypeItems,
                'total' => count($propertiesTypeItems),
            ],
            'tenants' => [
                'items' => $tenantsResult['items'],
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => (int) $totalTenants,
                    'total_pages' => $totalPages,
                    'has_next' => ($offset + $limit) < $totalTenants,
                    'has_previous' => $offset > 0,
                ],
                'filters' => $tenantsResult['filters'],
                'filter_options' => $tenantsResult['filter_options'],
            ],
        ];

        foreach ([
            'properties' => fn (): array => $this->getPropertiesMetrics($period),
            'revenue' => fn (): array => $this->getRevenueMetrics($period),
            'users' => fn (): array => $this->getUsersMetrics($period),
            'subscriptions' => fn (): array => $this->getSubscriptionsMetrics($period),
        ] as $key => $resolver) {
            if ($metric === 'all' || $metric === $key) {
                $payload[$key] = $resolver();
            }
        }

        if ($metric === 'business_metrics') {
            return ['business_metrics' => $businessMetrics];
        }

        return $payload;
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

            // Active properties (status = 1), optionally consider property_status if column exists
            $activeQuery = DB::table('user_properties')
                ->where('status', 1);
            if (Schema::hasColumn('user_properties', 'property_status')) {
                $activeQuery->orWhereIn('property_status', ['available', 'for_sale', 'for_rent']);
            }
            $active = $activeQuery->count();

            // Calculate change percentage (current month vs previous month)
            $now = now();
            $previousMonth = $now->copy()->subMonth();

            $currentPeriodCount = DB::table('user_properties')
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count();

            $previousPeriodCount = DB::table('user_properties')
                ->whereYear('created_at', $previousMonth->year)
                ->whereMonth('created_at', $previousMonth->month)
                ->count();

            $changePercentage = $previousPeriodCount > 0
                ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
                : 0;

            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
                'change_percentage' => round($changePercentage, 2),
                'period' => [
                    'current' => $now->format('Y-m'),
                    'previous' => $previousMonth->format('Y-m'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'change_percentage' => 0,
                'period' => [],
                'note' => __('Properties table might not exist'),
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
     * Build dashboard cards payload for UI
     *
     * @param int $period
     * @return array<string, mixed>
     */
    protected function getDashboardCards(int $period): array
    {
        $properties = $this->getPropertiesMetrics($period);
        $projects = $this->getProjectsMetrics($period);
        
        $totalProperties = $properties['total'] ?? 0;
        $activeProperties = $properties['active'] ?? 0;

        $statusBreakdown = $this->getActivePropertiesStatusBreakdown();

        $activePercentage = $totalProperties > 0
            ? round(($activeProperties / $totalProperties) * 100, 2)
            : 0;

        return [
            'active_properties' => [
                'total' => $activeProperties,
                'percentage_of_total' => $activePercentage,
            ],
            'active_properties_status_breakdown' => $statusBreakdown,
            'total_projects' => [
                'total' => $projects['total'] ?? 0,
                'change_percentage' => $projects['change_percentage'] ?? 0,
                'period' => $projects['period'] ?? [],
            ],
            'total_properties' => [
                'total' => $totalProperties,
                'change_percentage' => $properties['change_percentage'] ?? 0,
                'period' => $properties['period'] ?? [],
            ],
        ];
    }

    /**
     * Get active properties grouped by property_status
     *
     * @return array<string, int>
     */
    protected function getActivePropertiesStatusBreakdown(): array
    {
        if (!Schema::hasColumn('user_properties', 'property_status')) {
            return [
                'rented' => 0,
                'for_rent' => 0,
                'sale' => 0,
                'for_sale' => 0,
                'available' => 0,
            ];
        }

        $rawBreakdown = DB::table('user_properties')
            ->where('status', 1)
            ->whereIn('property_status', ['rented', 'for_rent', 'sale', 'for_sale', 'available'])
            ->select('property_status', DB::raw('COUNT(*) as total'))
            ->groupBy('property_status')
            ->pluck('total', 'property_status')
            ->toArray();

        return collect(['rented', 'for_rent', 'sale', 'for_sale', 'available'])
            ->mapWithKeys(fn ($status) => [$status => (int) ($rawBreakdown[$status] ?? 0)])
            ->toArray();
    }

    /**
     * Get activity metrics (visits placeholder, inquiries, active users)
     *
     * @param int $period
     * @return array<string, mixed>
     */
    protected function getActivityMetrics(int $period): array
    {
        $days = max(1, min(365, $period));
        $currentStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);
        $previousEnd = $currentStart;

        // inquiries
        $currentInquiries = DB::table('api_customer_inquiry')
            ->where('created_at', '>=', $currentStart)
            ->count();

        $previousInquiries = DB::table('api_customer_inquiry')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $inquiriesChange = $previousInquiries > 0
            ? (($currentInquiries - $previousInquiries) / $previousInquiries) * 100
            : ($currentInquiries > 0 ? 100 : 0);

        // active users (tenants with active flag)
        $currentActiveUsers = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('active', 1)
            ->where('updated_at', '>=', $currentStart)
            ->count();

        $previousActiveUsers = DB::table('users')
            ->where('account_type', 'tenant')
            ->where('active', 1)
            ->whereBetween('updated_at', [$previousStart, $previousEnd])
            ->count();

        $activeUsersChange = $previousActiveUsers > 0
            ? (($currentActiveUsers - $previousActiveUsers) / $previousActiveUsers) * 100
            : ($currentActiveUsers > 0 ? 100 : 0);

        return [
            'total_visits' => [
                'value' => __('coming soon'),
                'change_percentage' => null,
            ],
            'inquiries' => [
                'value' => $currentInquiries,
                'change_percentage' => round($inquiriesChange, 2),
            ],
            'active_users' => [
                'value' => $currentActiveUsers,
                'change_percentage' => round($activeUsersChange, 2),
            ],
        ];
    }

    /**
     * Get property type breakdown cards
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPropertyTypeMetrics(): array
    {
        if (!Schema::hasTable('user_property_categories')) {
            return [];
        }

        $types = DB::table('user_property_categories as c')
            ->select('c.name', DB::raw('COUNT(up.id) as value'))
            ->leftJoin('user_properties as up', 'up.category_id', '=', 'c.id')
            ->groupBy('c.name')
            ->orderByDesc(DB::raw('COUNT(up.id)'))
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'value' => (int) $row->value,
                ];
            })
            ->values()
            ->toArray();

        return $types;
    }

    /**
     * Get latest tenants overview slice
     *
     * @return array<string, mixed>
     */
    public function getTenantsOverview(int $limit = 10, int $offset = 0): array
    {
        $now = Carbon::now();

        $propertyCountSub = DB::table('user_properties')
            ->select('user_id', DB::raw('COUNT(*) as properties_count'))
            ->groupBy('user_id');

        $projectCountSub = DB::table('user_projects')
            ->select('user_id', DB::raw('COUNT(*) as projects_count'))
            ->groupBy('user_id');

        $latestMembershipIdSub = DB::table('memberships')
            ->select('user_id', DB::raw('MAX(id) as latest_id'))
            ->where('status', 1)
            ->groupBy('user_id');

        $baseQuery = DB::table('users as u')
            ->leftJoin('api_general_settings as gs', 'gs.user_id', '=', 'u.id')
            ->leftJoinSub($latestMembershipIdSub, 'lm', 'lm.user_id', '=', 'u.id')
            ->leftJoin('memberships as m', 'm.id', '=', 'lm.latest_id')
            ->leftJoin('packages as pkg', 'pkg.id', '=', 'm.package_id')
            ->leftJoinSub($propertyCountSub, 'pc', 'pc.user_id', '=', 'u.id')
            ->leftJoinSub($projectCountSub, 'pj', 'pj.user_id', '=', 'u.id')
            ->where('u.account_type', 'tenant')
            ->whereNull('u.deleted_at'); // exclude soft-deleted users

        $filtersApplied = [];
        $tenantFilters = request()->input('tenants');
        if (!is_array($tenantFilters)) {
            $tenantFilters = [];
        }

        $searchInput = $tenantFilters['search'] ?? request()->input('search', '');
        $search = trim((string) $searchInput);
        if ($search !== '') {
            $filtersApplied['search'] = $search;
            $baseQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('u.username', 'like', $like)
                  ->orWhere('u.email', 'like', $like)
                  ->orWhere('u.company_name', 'like', $like)
                  ->orWhere('gs.site_name', 'like', $like);
            });
        }

        $minProperties = $tenantFilters['min_properties'] ?? request()->input('min_properties');
        if ($minProperties !== null && $minProperties !== '') {
            $minProperties = (int) $minProperties;
            $filtersApplied['min_properties'] = $minProperties;
            $baseQuery->whereRaw('COALESCE(pc.properties_count, 0) >= ?', [$minProperties]);
        }

        $minProjects = $tenantFilters['min_projects'] ?? request()->input('min_projects');
        if ($minProjects !== null && $minProjects !== '') {
            $minProjects = (int) $minProjects;
            $filtersApplied['min_projects'] = $minProjects;
            $baseQuery->whereRaw('COALESCE(pj.projects_count, 0) >= ?', [$minProjects]);
        }

        $plan = $tenantFilters['plan'] ?? request()->input('plan');
        if ($plan !== null && $plan !== '' && $plan !== 'all') {
            $filtersApplied['plan'] = $plan;
            $baseQuery->where(function ($q) use ($plan) {
                if (is_numeric($plan)) {
                    $q->where('m.package_id', (int) $plan);
                } else {
                    $like = '%' . $plan . '%';
                    $q->where('pkg.slug', $plan)
                      ->orWhere('pkg.title', 'like', $like);
                }
            });
        }

        $status = $tenantFilters['status'] ?? request()->input('status');
        if ($status !== null && $status !== '' && $status !== 'all') {
            $filtersApplied['status'] = $status;
            switch ($status) {
                case 'active':
                    $baseQuery->whereNotNull('m.id')
                        ->whereDate('m.expire_date', '>=', $now->toDateString());
                    break;
                case 'expired':
                    $baseQuery->whereNotNull('m.id')
                        ->whereDate('m.expire_date', '<', $now->toDateString());
                    break;
                case 'inactive':
                    $baseQuery->whereNull('m.id');
                    break;
            }
        }

        $createdFrom = $tenantFilters['created_from'] ?? request()->input('created_from');
        if ($createdFrom) {
            try {
                $from = Carbon::parse($createdFrom)->startOfDay();
                $baseQuery->where('u.created_at', '>=', $from);
                $filtersApplied['created_from'] = $from->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $createdTo = $tenantFilters['created_to'] ?? request()->input('created_to');
        if ($createdTo) {
            try {
                $to = Carbon::parse($createdTo)->endOfDay();
                $baseQuery->where('u.created_at', '<=', $to);
                $filtersApplied['created_to'] = $to->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $expiresFrom = $tenantFilters['expires_from'] ?? request()->input('expires_from');
        if ($expiresFrom) {
            try {
                $from = Carbon::parse($expiresFrom)->startOfDay();
                $baseQuery->whereNotNull('m.expire_date')
                    ->where('m.expire_date', '>=', $from);
                $filtersApplied['expires_from'] = $from->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $expiresTo = $tenantFilters['expires_to'] ?? request()->input('expires_to');
        if ($expiresTo) {
            try {
                $to = Carbon::parse($expiresTo)->endOfDay();
                $baseQuery->whereNotNull('m.expire_date')
                    ->where('m.expire_date', '<=', $to);
                $filtersApplied['expires_to'] = $to->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $lastLoginFrom = $tenantFilters['last_login_from'] ?? request()->input('last_login_from');
        if ($lastLoginFrom) {
            try {
                $from = Carbon::parse($lastLoginFrom)->startOfDay();
                $baseQuery->whereNotNull('u.last_login_at')
                    ->where('u.last_login_at', '>=', $from);
                $filtersApplied['last_login_from'] = $from->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $lastLoginTo = $tenantFilters['last_login_to'] ?? request()->input('last_login_to');
        if ($lastLoginTo) {
            try {
                $to = Carbon::parse($lastLoginTo)->endOfDay();
                $baseQuery->whereNotNull('u.last_login_at')
                    ->where('u.last_login_at', '<=', $to);
                $filtersApplied['last_login_to'] = $to->toDateString();
            } catch (\Exception $e) {
                // ignore invalid date
            }
        }

        $countQuery = clone $baseQuery;
        $totalTenants = $countQuery->count();

        $rowsQuery = clone $baseQuery;
        $rows = $rowsQuery
            ->orderByDesc('u.created_at')
            ->orderByDesc('u.id')
            ->offset($offset)
            ->limit($limit)
            ->get([
                'u.id',
                'u.email',
                'u.username',
                'gs.site_name',
                'm.expire_date',
                'pkg.title as plan_title',
                DB::raw('COALESCE(pc.properties_count, 0) as properties_count'),
                DB::raw('COALESCE(pj.projects_count, 0) as projects_count'),
            ]);

        if ($rows->isEmpty()) {
            return [
                'items' => [],
                'filters' => $filtersApplied,
                'total' => $totalTenants,
                'filter_options' => [
                    'plans' => [],
                    'statuses' => [],
                ],
            ];
        }

        $appUrl = config('app.url');
        $appHost = $appUrl ? parse_url($appUrl, PHP_URL_HOST) : null;

        $items = $rows->map(function ($tenant) use ($appHost, $now) {
            $expiresAt = $tenant->expire_date ? Carbon::parse($tenant->expire_date) : null;

            $status = 'inactive';
            if ($expiresAt) {
                $status = $expiresAt->greaterThanOrEqualTo($now) ? 'active' : 'expired';
            }

            $siteName = $tenant->site_name ?? null;
            if (in_array($siteName, ['N/A', __('N/A')], true) || $siteName === null || trim($siteName) === '') {
                $siteName = $tenant->username;
            }
            $name = $siteName ?: __('Tenant');

            $domain = null;
            if ($tenant->username && $appHost) {
                $domain = "https://{$tenant->username}.{$appHost}/ar";
            }

            return [
                'id' => $tenant->id,
                'name' => $name,
                'domain' => $domain,
                'email' => $tenant->email,
                'properties' => (int) $tenant->properties_count,
                'projects' => (int) $tenant->projects_count,
                'plan_name' => $tenant->plan_title ?? __('N/A'),
                'plan_expires_at' => $tenant->expire_date ?? null,
                'status' => $status,
                'visitors' => __('coming soon'),
            ];
        })->toArray();

        $planOptions = DB::table('packages')
            ->select('id', 'title', 'slug')
            ->orderBy('id')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'slug' => $plan->slug,
                ];
            })
            ->values()
            ->toArray();

        // Build dynamic statuses from database
        $today = $now->toDateString();
        $activeCount = DB::table('memberships')
            ->where('status', 1)
            ->whereDate('expire_date', '>=', $today)
            ->count();

        $expiredCount = DB::table('memberships')
            ->where('status', 1)
            ->whereDate('expire_date', '<', $today)
            ->count();

        $inactiveCount = DB::table('users as u')
            ->where('u.account_type', 'tenant')
            ->whereNull('u.deleted_at')
            ->whereNotExists(function ($q) use ($today) {
                $q->select(DB::raw(1))
                  ->from('memberships as m2')
                  ->whereColumn('m2.user_id', 'u.id')
                  ->where('m2.status', 1)
                  ->whereDate('m2.expire_date', '>=', $today);
            })
            ->count();

        $statusOptions = [];
        if ($activeCount > 0)   $statusOptions[] = ['value' => 'active',   'label' => 'active'];
        if ($expiredCount > 0)  $statusOptions[] = ['value' => 'expired',  'label' => 'expired'];
        if ($inactiveCount > 0) $statusOptions[] = ['value' => 'inactive', 'label' => 'inactive'];

        return [
            'items' => $items,
            'filters' => $filtersApplied,
            'total' => $totalTenants,
            'filter_options' => [
                'plans' => $planOptions,
                'statuses' => $statusOptions,
            ],
        ];
    }

    /**
     * Get projects metrics
     *
     * @param int $period
     * @return array<string, mixed>
     */
    protected function getProjectsMetrics(int $period): array
    {
        try {
            $total = DB::table('user_projects')->count();

            $now = now();
            $previousMonth = $now->copy()->subMonth();

            $currentPeriodCount = DB::table('user_projects')
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count();

            $previousPeriodCount = DB::table('user_projects')
                ->whereYear('created_at', $previousMonth->year)
                ->whereMonth('created_at', $previousMonth->month)
                ->count();

            $changePercentage = $previousPeriodCount > 0
                ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
                : 0;

            return [
                'total' => $total,
                'change_percentage' => round($changePercentage, 2),
                'period' => [
                    'current' => $now->format('Y-m'),
                    'previous' => $previousMonth->format('Y-m'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'change_percentage' => 0,
                'period' => [],
                'note' => __('Projects table might not exist'),
            ];
        }
    }

    /**
     * Get pending review metrics
     *
     * @param int $period
     * @return array<string, mixed>
     */
    protected function getPendingReviewMetrics(int $period): array
    {
        try {
            $total = DB::table('user_properties')
                ->where('status', 0)
                ->count();

            $now = now();
            $previousMonth = $now->copy()->subMonth();

            $currentPeriodCount = DB::table('user_properties')
                ->where('status', 0)
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count();

            $previousPeriodCount = DB::table('user_properties')
                ->where('status', 0)
                ->whereYear('created_at', $previousMonth->year)
                ->whereMonth('created_at', $previousMonth->month)
                ->count();

            $changePercentage = $previousPeriodCount > 0
                ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
                : ($currentPeriodCount > 0 ? 100 : 0);

            return [
                'total' => $total,
                'change_percentage' => round($changePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'change_percentage' => 0,
                'note' => __('Properties table might not exist'),
            ];
        }
    }

    /**
     * Get quick stats summary
     *
     * @return array
     */
    public function getQuickStats(): array
    {
        $businessMetrics = $this->businessMetrics->snapshot();

        return [
            'total_users' => $businessMetrics['executiveSummary']['registeredTenantUsers'],
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

    private function businessMetricsPayload(): array
    {
        $snapshot = $this->businessMetrics->snapshot();
        $admin = auth(config('admin-api.guard'))->user();

        return array_filter([
            'as_of' => $snapshot['asOf']->toIso8601String(),
            'timezone' => $snapshot['timezone'],
            'executive_summary' => $snapshot['executiveSummary'],
            'financial_metrics' => $this->canViewFinancialMetrics($admin) ? $snapshot['financialMetrics'] : null,
            'visibility' => [
                'financial' => $this->canViewFinancialMetrics($admin),
            ],
        ], static fn ($value): bool => $value !== null);
    }

    private function canViewFinancialMetrics($admin): bool
    {
        if (!$admin instanceof Admin) {
            return false;
        }

        return $admin->hasPermission('Dashboard Financial Metrics')
            || $admin->hasPermission('Payment Log');
    }
}

