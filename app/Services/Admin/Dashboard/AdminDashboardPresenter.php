<?php

declare(strict_types=1);

namespace App\Services\Admin\Dashboard;

use Carbon\CarbonInterface;

class AdminDashboardPresenter
{
    public function present(array $dashboard): array
    {
        $executive = $dashboard['executiveSummary'] ?? [];
        $operations = $dashboard['operationsSnapshot'] ?? [];
        $financial = $dashboard['financialMetrics'] ?? [];
        $trends = $dashboard['trendCharts'] ?? [];
        $breakdowns = $dashboard['breakdowns'] ?? [];
        $recent = $dashboard['recentActivity'] ?? [];
        $visibility = $dashboard['visibility'] ?? [];
        $asOf = $dashboard['asOf'] ?? now('Asia/Riyadh');

        $availableTrendCharts = $this->availableTrendCharts($trends);
        $defaultTrendKey = $this->defaultTrendKey($availableTrendCharts);
        $defaultTrend = $this->trendByKey($availableTrendCharts, $defaultTrendKey);
        $defaultSeries = $defaultTrend['series'] ?? [];

        return [
            'raw' => $dashboard,
            'asOf' => $asOf,
            'visibility' => $visibility,
            'headlineCards' => $this->headlineCards($executive),
            'operationCards' => $this->operationCards($operations, $visibility),
            'financialCards' => $this->financialCards($financial, $asOf),
            'trendCharts' => $availableTrendCharts,
            'defaultTrendKey' => $defaultTrendKey,
            'defaultTrend' => $defaultTrend,
            'defaultTrendSeries' => $defaultSeries,
            'defaultTrendSummaryId' => $defaultTrendKey !== null
                ? 'dashboard-chart-summary-' . $defaultTrendKey
                : null,
            'breakdowns' => $breakdowns,
            'breakdownLabels' => $this->breakdownLabels(),
            'recentActivity' => $recent,
            'chartLabels' => [
                'tenantRegistrations' => __('Tenant Registrations'),
                'customers' => __('New Customers'),
            ],
        ];
    }

    private function headlineCards(array $executive): array
    {
        $cards = [];

        if (array_key_exists('activePaidSubscriberUsers', $executive)) {
            $cards[] = [
                'label' => __('Active Paid Subscriber Users'),
                'value' => $executive['activePaidSubscriberUsers'],
                'helper' => __('Tenant users with an active paid subscription, excluding trials and employees'),
                'icon' => 'badge-check',
                'tone' => 'primary',
                'url' => route('admin.register.user'),
            ];
        }

        if (array_key_exists('registeredTenantUsers', $executive)) {
            $cards[] = [
                'label' => __('Registered Tenant Users'),
                'value' => $executive['registeredTenantUsers'],
                'helper' => __('Tenant accounts only, excluding employees and soft-deleted users'),
                'icon' => 'users',
                'tone' => 'info',
                'url' => route('admin.register.user'),
            ];
        }

        if (array_key_exists('uniqueDashboardUsersToday', $executive)) {
            $cards[] = [
                'label' => __('Unique Dashboard Users Today'),
                'value' => $executive['uniqueDashboardUsersToday'],
                'helper' => __('Authenticated tenant-side users who opened the dashboard today in Riyadh time'),
                'icon' => 'activity',
                'tone' => 'success',
            ];
        }

        if (array_key_exists('uniqueTenantsOpenedDashboardToday', $executive)) {
            $cards[] = [
                'label' => __('Unique Tenant Organizations Today'),
                'value' => $executive['uniqueTenantsOpenedDashboardToday'],
                'helper' => __('Distinct tenant organizations represented by today\'s dashboard users'),
                'icon' => 'building',
                'tone' => 'violet',
            ];
        }

        if (array_key_exists('customersTotal', $executive)) {
            $cards[] = [
                'label' => __('Total Customers'),
                'value' => $executive['customersTotal'],
                'helper' => __('Non-deleted CRM customer records'),
                'icon' => 'contact-round',
                'tone' => 'violet',
            ];
        }

        if (array_key_exists('publishedProperties', $executive)) {
            $cards[] = [
                'label' => __('Published Properties'),
                'value' => $executive['publishedProperties'],
                'helper' => __('Listings visible through the publication rules'),
                'icon' => 'building-2',
                'tone' => 'success',
            ];
        }

        return $cards;
    }

    private function operationCards(array $operations, array $visibility): array
    {
        $cards = [];
        $definitions = [
            'activePaidSubscriptions' => [__('Active Paid Subscriptions'), __('Valid paid membership records'), 'credit-card', 'primary', !empty($visibility['users']) ? route('admin.register.user') : null],
            'activeTrials' => [__('Active Trials'), __('Valid 7-day and 30-day trial packages'), 'timer', 'warning', !empty($visibility['users']) ? route('admin.register.user') : null],
            'freeUsers' => [__('Free Users'), __('Tenants currently using the free package'), 'user-round', 'neutral', !empty($visibility['users']) ? route('admin.register.user') : null],
            'tenantRegistrationsThisMonth' => [__('Tenant Registrations This Month'), __('Created during the current Riyadh month'), 'user-plus', 'info', !empty($visibility['users']) ? route('admin.register.user') : null],
            'customersNewThisMonth' => [__('New Customers This Month'), __('Non-deleted customers created this month'), 'contact-round', 'violet', null],
            'projectsTotal' => [__('Total Projects'), __('All tenant real-estate projects'), 'landmark', 'warning', null],
            'projectsPublished' => [__('Published Projects'), __('Projects currently marked as published'), 'circle-check-big', 'success', null],
            'propertiesTotal' => [__('Total Properties'), __('All tenant property records'), 'house', 'info', null],
            'publishedFeaturedProperties' => [__('Published Featured Properties'), __('Featured listings that also pass publication rules'), 'star', 'warning', null],
            'packagesTotal' => [__('Total Packages'), __('All configured subscription packages'), 'package', 'neutral', !empty($visibility['packages']) ? route('admin.package.index') : null],
            'packagesActive' => [__('Active Packages'), __('Packages enabled through is_active'), 'package-check', 'success', !empty($visibility['packages']) ? route('admin.package.index') : null],
        ];

        foreach ($definitions as $key => $definition) {
            if (!array_key_exists($key, $operations)) {
                continue;
            }

            $cards[] = [
                'label' => $definition[0],
                'value' => $operations[$key],
                'helper' => $definition[1],
                'icon' => $definition[2],
                'tone' => $definition[3],
                'url' => $definition[4],
            ];
        }

        return $cards;
    }

    private function financialCards(array $financial, CarbonInterface $asOf): array
    {
        $cards = [];
        $definitions = [
            'projectInventoryValue' => [__('Project Inventory Value'), __('Sum of actual property prices for units linked to projects'), 'landmark', 'primary'],
            'forSaleInventoryValue' => [__('For Sale Inventory Value'), __('Available sale listings only'), 'badge-dollar-sign', 'success'],
            'forRentInventoryValue' => [__('For Rent Inventory Value'), __('Available rental listings only'), 'house-plus', 'info'],
            'completedSalesValue' => [__('Completed Sales Value'), __('Completed sales transactions only'), 'receipt-text', 'warning'],
            'activeRentalContractValue' => [__('Active Rental Contract Value'), __('Active rental contracts only, SAR totals shown separately'), 'wallet', 'violet'],
        ];

        foreach ($definitions as $key => $definition) {
            if (!array_key_exists($key, $financial)) {
                continue;
            }

            $cards[] = array_merge($financial[$key], [
                'label' => $definition[0],
                'helper' => $definition[1],
                'icon' => $definition[2],
                'tone' => $definition[3],
                'asOf' => $asOf,
            ]);
        }

        return $cards;
    }

    private function availableTrendCharts(array $trends): array
    {
        $definitions = [
            'tenantRegistrations' => [__('Monthly Tenant Registrations'), __('New tenant accounts by month')],
            'customers' => [__('Monthly New Customers'), __('New non-deleted customer records by month')],
        ];

        $charts = [];

        foreach ($definitions as $chartKey => $chartText) {
            if (!isset($trends[$chartKey])) {
                continue;
            }

            $charts[] = [
                'key' => $chartKey,
                'title' => $chartText[0],
                'subtitle' => $chartText[1],
                'series' => $trends[$chartKey],
            ];
        }

        return $charts;
    }

    private function defaultTrendKey(array $availableTrendCharts): ?string
    {
        $default = $availableTrendCharts[0]['key'] ?? null;

        foreach ($availableTrendCharts as $trendChart) {
            if (empty($trendChart['series']['isEmpty'])) {
                return $trendChart['key'];
            }
        }

        return $default;
    }

    private function trendByKey(array $availableTrendCharts, ?string $key): array
    {
        foreach ($availableTrendCharts as $trendChart) {
            if (($trendChart['key'] ?? null) === $key) {
                return $trendChart;
            }
        }

        return [];
    }

    private function breakdownLabels(): array
    {
        return [
            'sale' => __('Sale'),
            'rent' => __('Rent'),
            'available' => __('Available'),
            'sold' => __('Sold'),
            'rented' => __('Rented'),
            'published' => __('Published'),
            'draft' => __('Draft'),
            'featured' => __('Featured'),
            'not_featured' => __('Not Featured'),
            'in_progress' => __('In Progress'),
            'finished' => __('Finished'),
            'not_started' => __('Not Started'),
            'unknown' => __('Unknown'),
        ];
    }
}
