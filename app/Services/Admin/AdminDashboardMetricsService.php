<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Admin\Models\Admin;
use App\Models\ApiCustomer;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardMetricsService
{
    public const BUSINESS_TIMEZONE = 'Asia/Riyadh';

    public function __construct(
        private readonly AdminDashboardBusinessMetricsService $businessMetrics
    ) {
    }

    /**
     * Build only the dashboard sections the current platform admin may see.
     */
    public function build(Admin $admin, ?CarbonImmutable $clock = null): array
    {
        $clock = ($clock ?? CarbonImmutable::now(self::BUSINESS_TIMEZONE))
            ->setTimezone(self::BUSINESS_TIMEZONE);

        $visibility = $this->visibility($admin);
        $today = $clock->toDateString();
        $monthStart = $clock->startOfMonth();
        $nextMonthStart = $monthStart->addMonth();
        $yearStart = $clock->startOfYear();
        $nextYearStart = $yearStart->addYear();
        $businessSnapshot = $this->businessMetrics->snapshot($clock);

        $dashboard = [
            'asOf' => $businessSnapshot['asOf'],
            'visibility' => $visibility,
            'executiveSummary' => [],
            'operationsSnapshot' => [],
            'financialMetrics' => [],
            'trendCharts' => [],
            'breakdowns' => [],
            'recentActivity' => [],
        ];

        if ($visibility['users']) {
            $membershipSummary = $this->membershipSummary($today);
            $tenantSummary = User::query()
                ->where('account_type', 'tenant')
                ->whereNull('deleted_at')
                ->selectRaw(
                    'COUNT(*) AS total, '
                    . 'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS registered_this_month',
                    [$monthStart, $nextMonthStart]
                )
                ->first();
            $canonicalSummary = $businessSnapshot['executiveSummary'];

            $dashboard['executiveSummary']['activePremiumUsers'] = (int) $membershipSummary->premium_users;
            $dashboard['executiveSummary']['tenantUsers'] = (int) $tenantSummary->total;
            $dashboard['executiveSummary']['activePaidSubscriberUsers'] = (int) $canonicalSummary['activePaidSubscriberUsers'];
            $dashboard['executiveSummary']['registeredTenantUsers'] = (int) $canonicalSummary['registeredTenantUsers'];
            $dashboard['executiveSummary']['uniqueDashboardUsersToday'] = (int) $canonicalSummary['uniqueDashboardUsersToday'];
            $dashboard['executiveSummary']['uniqueTenantsOpenedDashboardToday'] = (int) $canonicalSummary['uniqueTenantsOpenedDashboardToday'];
            $dashboard['operationsSnapshot']['activePaidSubscriptions'] = (int) $membershipSummary->paid_subscriptions;
            $dashboard['operationsSnapshot']['activeTrials'] = (int) $membershipSummary->trials;
            $dashboard['operationsSnapshot']['freeUsers'] = (int) $membershipSummary->free_users;
            $dashboard['operationsSnapshot']['tenantRegistrationsThisMonth'] = (int) $tenantSummary->registered_this_month;

            $dashboard['trendCharts']['tenantRegistrations'] = $this->monthlyCountSeries(
                User::query()->where('account_type', 'tenant'),
                $yearStart,
                $nextYearStart,
                $clock
            );

            $dashboard['recentActivity']['tenants'] = User::query()
                ->where('account_type', 'tenant')
                ->select(['id', 'username', 'company_name', 'created_at'])
                ->with('basic_setting:id,user_id,company_name')
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->each(function (User $tenant): void {
                    $tenant->setAttribute('display_name', $this->tenantDisplayName($tenant));
                });
        }

        if ($visibility['packages']) {
            $packageSummary = Package::query()
                ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active')
                ->first();

            $dashboard['operationsSnapshot']['packagesTotal'] = (int) $packageSummary->total;
            $dashboard['operationsSnapshot']['packagesActive'] = (int) $packageSummary->active;
        }

        if ($visibility['tenantOperations']) {
            $customerSummary = ApiCustomer::query()
                ->selectRaw(
                    'COUNT(*) AS total, '
                    . 'SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS created_this_month',
                    [$monthStart, $nextMonthStart]
                )
                ->first();
            $propertyMetrics = $this->propertyMetrics();
            $projectMetrics = $this->projectMetrics();

            $dashboard['executiveSummary']['customersTotal'] = (int) $customerSummary->total;
            $dashboard['executiveSummary']['publishedProperties'] = $propertyMetrics['published'];
            $dashboard['operationsSnapshot']['customersNewThisMonth'] = (int) $customerSummary->created_this_month;
            $dashboard['operationsSnapshot']['projectsTotal'] = $projectMetrics['total'];
            $dashboard['operationsSnapshot']['projectsPublished'] = $projectMetrics['published'];
            $dashboard['operationsSnapshot']['propertiesTotal'] = $propertyMetrics['total'];
            $dashboard['operationsSnapshot']['publishedFeaturedProperties'] = $propertyMetrics['publishedFeatured'];

            $dashboard['trendCharts']['customers'] = $this->monthlyCountSeries(
                ApiCustomer::query(),
                $yearStart,
                $nextYearStart,
                $clock
            );

            $dashboard['breakdowns']['properties'] = $propertyMetrics['breakdowns'];
            $dashboard['breakdowns']['projects'] = $projectMetrics['breakdowns'];

            $dashboard['recentActivity']['customers'] = ApiCustomer::query()
                ->select(['id', 'name', 'user_id', 'created_at'])
                ->latest('created_at')
                ->limit(5)
                ->get();
        }

        if ($visibility['financial']) {
            $dashboard['financialMetrics'] = $businessSnapshot['financialMetrics'];
        }

        return $dashboard;
    }

    private function visibility(Admin $admin): array
    {
        return [
            'users' => $admin->hasPermission('Registered Users'),
            'packages' => $admin->hasPermission('Packages'),
            'tenantOperations' => is_null($admin->role_id),
            'financial' => $this->canViewFinancialMetrics($admin),
        ];
    }

    private function canViewFinancialMetrics(Admin $admin): bool
    {
        return $admin->hasPermission('Dashboard Financial Metrics')
            || $admin->hasPermission('Payment Log');
    }

    private function tenantDisplayName(User $tenant): string
    {
        foreach ([
            $tenant->basic_setting?->company_name,
            $tenant->company_name,
        ] as $candidate) {
            if ($this->isUsableTenantName($candidate)) {
                return trim((string) $candidate);
            }
        }

        $username = trim((string) $tenant->username);

        return $username !== '' ? $username : __('Tenant');
    }

    private function isUsableTenantName(?string $name): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return false;
        }

        $placeholders = [
            'n/a',
            strtolower((string) __('N/A')),
            strtolower((string) __('N/A', [], 'en')),
            strtolower((string) __('N/A', [], 'ar')),
        ];

        return ! in_array(strtolower($name), $placeholders, true);
    }

    private function activeMemberships(string $today): Builder
    {
        return Membership::query()
            ->whereHas('user', fn (Builder $query): Builder => $query
                ->where('account_type', 'tenant'))
            ->where('status', 1)
            ->where('start_date', '<=', $today)
            ->where('expire_date', '>=', $today);
    }

    private function membershipSummary(string $today): object
    {
        [$paidYearly, $paidMonthly] = $this->paidPackageIds();
        [$trialSeven, $trialThirty] = $this->trialPackageIds();
        $free = $this->freePackageId();

        return $this->activeMemberships($today)
            ->selectRaw(
                'SUM(CASE WHEN package_id IN (?, ?) THEN 1 ELSE 0 END) AS paid_subscriptions, '
                . 'COUNT(DISTINCT CASE WHEN package_id IN (?, ?) THEN user_id END) AS premium_users, '
                . 'SUM(CASE WHEN package_id IN (?, ?) THEN 1 ELSE 0 END) AS trials, '
                . 'COUNT(DISTINCT CASE WHEN package_id = ? THEN user_id END) AS free_users',
                [
                    $paidYearly,
                    $paidMonthly,
                    $paidYearly,
                    $paidMonthly,
                    $trialSeven,
                    $trialThirty,
                    $free,
                ]
            )
            ->first();
    }

    private function freePackageId(): int
    {
        return (int) config('membership.free_package_id', MembershipService::FREE_PACKAGE_ID);
    }

    private function paidPackageIds(): array
    {
        return [
            MembershipService::PAID_YEARLY_PACKAGE_ID,
            MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ];
    }

    private function trialPackageIds(): array
    {
        return [
            (int) config('membership.trial_package_id', MembershipService::TRIAL_PACKAGE_ID),
            (int) config('membership.trial_monthly_package_id', MembershipService::TRIAL_MONTHLY_PACKAGE_ID),
        ];
    }

    private function monthlyCountSeries(
        Builder $query,
        CarbonImmutable $yearStart,
        CarbonImmutable $nextYearStart,
        CarbonImmutable $clock
    ): array {
        $counts = $query
            ->where('created_at', '>=', $yearStart)
            ->where('created_at', '<', $nextYearStart)
            ->selectRaw('MONTH(created_at) AS month, COUNT(*) AS total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $labels = [];
        $values = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = CarbonImmutable::create(
                $clock->year,
                $month,
                1,
                0,
                0,
                0,
                self::BUSINESS_TIMEZONE
            )->locale(app()->getLocale())->translatedFormat('M');
            $values[] = (int) ($counts[$month] ?? 0);
        }

        return [
            'year' => $clock->year,
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
            'isEmpty' => array_sum($values) === 0,
        ];
    }

    private function propertyMetrics(): array
    {
        $summary = Property::query()
            ->selectRaw(
                'COUNT(*) AS total, '
                . "SUM(CASE WHEN listing_purpose = 'sale' THEN 1 ELSE 0 END) AS sale, "
                . "SUM(CASE WHEN listing_purpose = 'rent' THEN 1 ELSE 0 END) AS rent, "
                . "SUM(CASE WHEN unit_status = 'available' THEN 1 ELSE 0 END) AS available, "
                . "SUM(CASE WHEN unit_status = 'sold' THEN 1 ELSE 0 END) AS sold, "
                . "SUM(CASE WHEN unit_status = 'rented' THEN 1 ELSE 0 END) AS rented"
            )
            ->first();
        $total = (int) $summary->total;
        $published = Property::query()->publishedForPublic()->count();
        $unpublished = Property::query()->unpublishedForPublic()->count();
        $publishedFeatured = Property::query()
            ->publishedForPublic()
            ->where('featured', true)
            ->count();

        return [
            'total' => $total,
            'published' => $published,
            'publishedFeatured' => $publishedFeatured,
            'breakdowns' => [
                'listingPurpose' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'sale', 'value' => (int) $summary->sale],
                        ['key' => 'rent', 'value' => (int) $summary->rent],
                        ['key' => 'unknown', 'value' => max(0, $total - (int) $summary->sale - (int) $summary->rent)],
                    ],
                ],
                'availability' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'available', 'value' => (int) $summary->available],
                        ['key' => 'sold', 'value' => (int) $summary->sold],
                        ['key' => 'rented', 'value' => (int) $summary->rented],
                        ['key' => 'unknown', 'value' => max(0, $total - (int) $summary->available - (int) $summary->sold - (int) $summary->rented)],
                    ],
                ],
                'publication' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'published', 'value' => $published],
                        ['key' => 'draft', 'value' => $unpublished],
                        ['key' => 'unknown', 'value' => max(0, $total - $published - $unpublished)],
                    ],
                ],
            ],
        ];
    }

    private function projectMetrics(): array
    {
        $summary = Project::query()
            ->selectRaw(
                'COUNT(*) AS total, '
                . 'SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) AS published_count, '
                . 'SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) AS featured_count, '
                . 'SUM(CASE WHEN complete_status = 0 THEN 1 ELSE 0 END) AS in_progress, '
                . 'SUM(CASE WHEN complete_status = 1 THEN 1 ELSE 0 END) AS finished, '
                . 'SUM(CASE WHEN complete_status = 2 THEN 1 ELSE 0 END) AS not_started'
            )
            ->first();
        $total = (int) $summary->total;
        $published = (int) $summary->published_count;
        $featured = (int) $summary->featured_count;
        $knownCompletionTotal = (int) $summary->in_progress
            + (int) $summary->finished
            + (int) $summary->not_started;

        return [
            'total' => $total,
            'published' => $published,
            'breakdowns' => [
                'publication' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'published', 'value' => $published],
                        ['key' => 'draft', 'value' => max(0, $total - $published)],
                    ],
                ],
                'featured' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'featured', 'value' => $featured],
                        ['key' => 'not_featured', 'value' => max(0, $total - $featured)],
                    ],
                ],
                'completion' => [
                    'total' => $total,
                    'items' => [
                        ['key' => 'in_progress', 'value' => (int) $summary->in_progress],
                        ['key' => 'finished', 'value' => (int) $summary->finished],
                        ['key' => 'not_started', 'value' => (int) $summary->not_started],
                        ['key' => 'unknown', 'value' => max(0, $total - $knownCompletionTotal)],
                    ],
                ],
            ],
        ];
    }
}
