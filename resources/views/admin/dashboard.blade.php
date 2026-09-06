@extends('admin.layout')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/dashboard.css') }}">
@endsection

@php
    $executive = $dashboard['executiveSummary'] ?? [];
    $operations = $dashboard['operationsSnapshot'] ?? [];
    $financial = $dashboard['financialMetrics'] ?? [];
    $trends = $dashboard['trendCharts'] ?? [];
    $breakdowns = $dashboard['breakdowns'] ?? [];
    $recent = $dashboard['recentActivity'] ?? [];
    $visibility = $dashboard['visibility'] ?? [];
    $asOf = $dashboard['asOf'] ?? now('Asia/Riyadh');

    $headlineCards = [];
    if (array_key_exists('activePaidSubscriberUsers', $executive)) {
        $headlineCards[] = [
            'label' => __('Active Paid Subscriber Users'),
            'value' => $executive['activePaidSubscriberUsers'],
            'helper' => __('Tenant users with an active paid subscription, excluding trials and employees'),
            'icon' => 'badge-check',
            'tone' => 'primary',
            'url' => route('admin.register.user'),
        ];
    }
    if (array_key_exists('registeredTenantUsers', $executive)) {
        $headlineCards[] = [
            'label' => __('Registered Tenant Users'),
            'value' => $executive['registeredTenantUsers'],
            'helper' => __('Tenant accounts only, excluding employees and soft-deleted users'),
            'icon' => 'users',
            'tone' => 'info',
            'url' => route('admin.register.user'),
        ];
    }
    if (array_key_exists('uniqueDashboardUsersToday', $executive)) {
        $headlineCards[] = [
            'label' => __('Unique Dashboard Users Today'),
            'value' => $executive['uniqueDashboardUsersToday'],
            'helper' => __('Authenticated tenant-side users who opened the dashboard today in Riyadh time'),
            'icon' => 'activity',
            'tone' => 'success',
        ];
    }
    if (array_key_exists('uniqueTenantsOpenedDashboardToday', $executive)) {
        $headlineCards[] = [
            'label' => __('Unique Tenant Organizations Today'),
            'value' => $executive['uniqueTenantsOpenedDashboardToday'],
            'helper' => __('Distinct tenant organizations represented by today\'s dashboard users'),
            'icon' => 'building',
            'tone' => 'violet',
        ];
    }
    if (array_key_exists('customersTotal', $executive)) {
        $headlineCards[] = [
            'label' => __('Total Customers'),
            'value' => $executive['customersTotal'],
            'helper' => __('Non-deleted CRM customer records'),
            'icon' => 'contact-round',
            'tone' => 'violet',
        ];
    }
    if (array_key_exists('publishedProperties', $executive)) {
        $headlineCards[] = [
            'label' => __('Published Properties'),
            'value' => $executive['publishedProperties'],
            'helper' => __('Listings visible through the publication rules'),
            'icon' => 'building-2',
            'tone' => 'success',
        ];
    }

    $operationCards = [];
    $operationDefinitions = [
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
    foreach ($operationDefinitions as $key => $definition) {
        if (array_key_exists($key, $operations)) {
            $operationCards[] = [
                'label' => $definition[0],
                'value' => $operations[$key],
                'helper' => $definition[1],
                'icon' => $definition[2],
                'tone' => $definition[3],
                'url' => $definition[4],
            ];
        }
    }

    $financialCards = [];
    $financialDefinitions = [
        'projectInventoryValue' => [__('Project Inventory Value'), __('Sum of actual property prices for units linked to projects'), 'landmark', 'primary'],
        'forSaleInventoryValue' => [__('For Sale Inventory Value'), __('Available sale listings only'), 'badge-dollar-sign', 'success'],
        'forRentInventoryValue' => [__('For Rent Inventory Value'), __('Available rental listings only'), 'house-plus', 'info'],
        'completedSalesValue' => [__('Completed Sales Value'), __('Completed sales transactions only'), 'receipt-text', 'warning'],
        'activeRentalContractValue' => [__('Active Rental Contract Value'), __('Active rental contracts only, SAR totals shown separately'), 'wallet', 'violet'],
    ];

    foreach ($financialDefinitions as $key => $definition) {
        if (!empty($financial[$key])) {
            $financialCards[] = array_merge($financial[$key], [
                'label' => $definition[0],
                'helper' => $definition[1],
                'icon' => $definition[2],
                'tone' => $definition[3],
                'asOf' => $asOf,
            ]);
        }
    }

    $breakdownLabels = [
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

    $chartDefinitions = [
        'tenantRegistrations' => [__('Monthly Tenant Registrations'), __('New tenant accounts by month')],
        'customers' => [__('Monthly New Customers'), __('New non-deleted customer records by month')],
    ];

    $availableTrendCharts = [];
    foreach ($chartDefinitions as $chartKey => $chartText) {
        if (isset($trends[$chartKey])) {
            $availableTrendCharts[] = [
                'key' => $chartKey,
                'title' => $chartText[0],
                'subtitle' => $chartText[1],
                'series' => $trends[$chartKey],
            ];
        }
    }

    $defaultTrendKey = $availableTrendCharts[0]['key'] ?? null;
    foreach ($availableTrendCharts as $trendChart) {
        if (empty($trendChart['series']['isEmpty'])) {
            $defaultTrendKey = $trendChart['key'];
            break;
        }
    }

    $formatChartSummaryValue = static function ($value): string {
        return number_format((int) $value);
    };
@endphp

@section('content')
    <div class="admin-dashboard">
        <header class="dashboard-header">
            <div class="dashboard-header-copy">
                <p class="dashboard-eyebrow">{{ __('Platform Operations') }}</p>
                <h2>{{ __('Dashboard') }}</h2>
                <p class="dashboard-subtitle">{{ __('Overview of subscriptions, listings, projects, and customers') }}</p>
            </div>
            <div class="dashboard-as-of">
                <span>{{ __('As of') }}</span>
                <strong>{{ $asOf->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }}</strong>
                <small>{{ __('Riyadh time') }}</small>
            </div>
        </header>

        @if(session('error') || session('success') || session('warning'))
            @foreach(['error' => 'danger', 'success' => 'success', 'warning' => 'warning'] as $flashKey => $flashClass)
                @if(session($flashKey))
                    <div class="alert alert-{{ $flashClass }} alert-dismissible fade show" role="alert">
                        {{ session($flashKey) }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            @endforeach
        @endif

        @if(!empty($headlineCards))
            <section class="dashboard-section dashboard-section-hero" aria-labelledby="executive-summary-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="executive-summary-heading">{{ __('Executive Summary') }}</h3>
                        <p>{{ __('Trusted headline metrics based on current platform data') }}</p>
                    </div>
                </div>
                <div class="row dashboard-grid dashboard-grid-headline">
                    @foreach($headlineCards as $card)
                        <div class="col-sm-6 col-xl-3 d-flex">
                            @include('admin.partials.dashboard.kpi-card', ['card' => $card, 'size' => 'large'])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(!empty($operationCards))
            <section class="dashboard-section dashboard-section-secondary" aria-labelledby="operations-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="operations-heading">{{ __('Operations Snapshot') }}</h3>
                        <p>{{ __('Current subscription and inventory activity') }}</p>
                    </div>
                </div>
                <div class="row dashboard-grid dashboard-grid-secondary">
                    @foreach($operationCards as $card)
                        <div class="col-sm-6 col-lg-4 col-xl-3 d-flex">
                            @include('admin.partials.dashboard.kpi-card', ['card' => $card, 'size' => 'compact'])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(!empty($visibility['financial']) && !empty($financialCards))
            <section class="dashboard-section dashboard-section-financial" aria-labelledby="financial-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="financial-heading">{{ __('Financial Metrics') }}</h3>
                        <p>{{ __('Simplified SAR totals with clear data-quality context') }}</p>
                    </div>
                </div>
                <div class="row dashboard-grid dashboard-grid-financial">
                    @foreach($financialCards as $card)
                        <div class="col-lg-6 d-flex">
                            @include('admin.partials.dashboard.money-card', ['card' => $card])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(!empty($availableTrendCharts) && $defaultTrendKey)
            <section class="dashboard-section dashboard-section-supporting" aria-labelledby="trends-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="trends-heading">{{ __('Platform Trends') }}</h3>
                        <p>{{ __('Use these trend lines to support the headline KPIs') }}</p>
                    </div>
                </div>
                @php
                    $defaultTrend = collect($availableTrendCharts)->firstWhere('key', $defaultTrendKey);
                    $defaultSeries = $defaultTrend['series'];
                    $defaultSummaryId = 'dashboard-chart-summary-' . $defaultTrendKey;
                @endphp
                <article class="card dashboard-panel dashboard-chart-card dashboard-chart-card-focus w-100">
                    <div class="dashboard-trend-toolbar">
                        <div class="dashboard-trend-switcher" role="group" aria-label="{{ __('Platform Trends') }}">
                            @foreach($availableTrendCharts as $trendChart)
                                <button
                                    type="button"
                                    class="dashboard-trend-option{{ $trendChart['key'] === $defaultTrendKey ? ' is-active' : '' }}"
                                    data-dashboard-trend-key="{{ $trendChart['key'] }}"
                                    data-dashboard-trend-title="{{ $trendChart['title'] }}"
                                    data-dashboard-trend-total="{{ (int) ($trendChart['series']['total'] ?? 0) }}"
                                    data-dashboard-trend-period="{{ __('in') }} {{ (int) ($trendChart['series']['year'] ?? $asOf->year) }}"
                                    data-dashboard-trend-summary="dashboard-chart-summary-{{ $trendChart['key'] }}"
                                    aria-pressed="{{ $trendChart['key'] === $defaultTrendKey ? 'true' : 'false' }}"
                                >
                                    {{ $trendChart['title'] }}
                                </button>
                            @endforeach
                        </div>
                        <div class="dashboard-trend-summary" aria-live="polite">
                            <span id="dashboard-trend-summary-label">{{ $defaultTrend['title'] }}</span>
                            <strong id="dashboard-trend-summary-value">{{ number_format((int) ($defaultSeries['total'] ?? 0)) }}</strong>
                            <small id="dashboard-trend-summary-year">{{ __('in') }} {{ $defaultSeries['year'] ?? $asOf->year }}</small>
                        </div>
                    </div>
                    <div class="card-body dashboard-trend-body">
                        <div class="dashboard-chart-container dashboard-chart-container-focus" role="group" aria-labelledby="trends-heading">
                            <canvas
                                data-dashboard-chart-switcher
                                data-dashboard-initial-chart="{{ $defaultTrendKey }}"
                                data-dashboard-current-month="{{ (int) $asOf->format('n') }}"
                                data-dashboard-current-year="{{ (int) $asOf->format('Y') }}"
                                role="img"
                                aria-label="{{ $defaultTrend['title'] }}"
                                aria-describedby="{{ $defaultSummaryId }}"
                                @if(!empty($defaultSeries['isEmpty'])) hidden @endif
                            ></canvas>
                            <div class="dashboard-empty-state dashboard-trend-empty" @if(empty($defaultSeries['isEmpty'])) hidden @endif>
                                <i data-lucide="chart-no-axes-column"></i>
                                <p>{{ __('No activity recorded for this period') }}</p>
                            </div>
                        </div>
                        <div class="dashboard-sr-only">
                            @foreach($availableTrendCharts as $trendChart)
                                <section id="dashboard-chart-summary-{{ $trendChart['key'] }}">
                                    <p>{{ $trendChart['subtitle'] }}</p>
                                    <ul>
                                        @foreach(($trendChart['series']['labels'] ?? []) as $index => $label)
                                            @if((int) ($trendChart['series']['year'] ?? 0) !== (int) $asOf->format('Y') || $index < (int) $asOf->format('n'))
                                                <li>{{ $label }}: {{ $formatChartSummaryValue($trendChart['series']['values'][$index] ?? 0) }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </article>
            </section>
        @endif

        @if(!empty($breakdowns))
            <section class="dashboard-section dashboard-section-supporting" aria-labelledby="breakdowns-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="breakdowns-heading">{{ __('Business Breakdowns') }}</h3>
                        <p>{{ __('Normalized property and project status views') }}</p>
                    </div>
                </div>
                <div class="row dashboard-grid dashboard-grid-breakdowns">
                    @if(!empty($breakdowns['properties']))
                        @foreach([
                            'listingPurpose' => __('Property Listing Purpose'),
                            'availability' => __('Property Availability'),
                            'publication' => __('Property Publication'),
                        ] as $groupKey => $groupTitle)
                            <div class="col-lg-6 d-flex">
                                @include('admin.partials.dashboard.breakdown-card', [
                                    'title' => $groupTitle,
                                    'group' => $breakdowns['properties'][$groupKey],
                                    'labels' => $breakdownLabels,
                                    'chartScope' => 'properties',
                                    'groupKey' => $groupKey,
                                ])
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($breakdowns['projects']))
                        @foreach([
                            'publication' => __('Project Publication'),
                            'featured' => __('Featured Projects'),
                            'completion' => __('Project Completion'),
                        ] as $groupKey => $groupTitle)
                            <div class="col-lg-6 d-flex">
                                @include('admin.partials.dashboard.breakdown-card', [
                                    'title' => $groupTitle,
                                    'group' => $breakdowns['projects'][$groupKey],
                                    'labels' => $breakdownLabels,
                                    'chartScope' => 'projects',
                                    'groupKey' => $groupKey,
                                ])
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>
        @endif

        @if(!empty($recent))
            <section class="dashboard-section dashboard-section-supporting" aria-labelledby="recent-heading">
                <div class="dashboard-section-heading">
                    <div>
                        <h3 id="recent-heading">{{ __('Recent Activity') }}</h3>
                        <p>{{ __('Latest tenant and customer records') }}</p>
                    </div>
                </div>
                <div class="row dashboard-grid dashboard-grid-supporting">
                    @if(array_key_exists('tenants', $recent))
                        <div class="col-lg-6 d-flex">
                            <article class="card dashboard-panel w-100">
                                <div class="card-header dashboard-panel-header">
                                    <div>
                                        <h4>{{ __('Recent Tenant Signups') }}</h4>
                                        <p>{{ __('Five most recently created tenant accounts') }}</p>
                                    </div>
                                </div>
                                <div class="card-body dashboard-activity-list">
                                    @forelse($recent['tenants'] as $tenant)
                                        <div class="dashboard-activity-item">
                                            <span class="dashboard-activity-icon"><i data-lucide="user-round"></i></span>
                                            <div>
                                                <strong>{{ $tenant->company_name ?: $tenant->username }}</strong>
                                                <small>{{ '@' . $tenant->username }}</small>
                                            </div>
                                            <time datetime="{{ optional($tenant->created_at)->toIso8601String() }}">
                                                {{ optional($tenant->created_at)->locale(app()->getLocale())->diffForHumans() }}
                                            </time>
                                        </div>
                                    @empty
                                        <div class="dashboard-empty-state dashboard-empty-state-small">
                                            <p>{{ __('No recent tenant signups') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </article>
                        </div>
                    @endif

                    @if(array_key_exists('customers', $recent))
                        <div class="col-lg-6 d-flex">
                            <article class="card dashboard-panel w-100">
                                <div class="card-header dashboard-panel-header">
                                    <div>
                                        <h4>{{ __('Recent Customers') }}</h4>
                                        <p>{{ __('Five most recently created customer records') }}</p>
                                    </div>
                                </div>
                                <div class="card-body dashboard-activity-list">
                                    @forelse($recent['customers'] as $customer)
                                        <div class="dashboard-activity-item">
                                            <span class="dashboard-activity-icon"><i data-lucide="contact-round"></i></span>
                                            <div>
                                                <strong>{{ $customer->name ?: __('Customer') . ' #' . $customer->id }}</strong>
                                                <small>{{ __('Tenant') }} #{{ $customer->user_id }}</small>
                                            </div>
                                            <time datetime="{{ optional($customer->created_at)->toIso8601String() }}">
                                                {{ optional($customer->created_at)->locale(app()->getLocale())->diffForHumans() }}
                                            </time>
                                        </div>
                                    @empty
                                        <div class="dashboard-empty-state dashboard-empty-state-small">
                                            <p>{{ __('No recent customers') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </article>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/plugin/chart.min.js') }}"></script>
    <script id="dashboard-chart-data" type="application/json">@json($trends)</script>
    <script>
        window.dashboardChartLabels = {
            tenantRegistrations: @json(__('Tenant Registrations')),
            customers: @json(__('New Customers'))
        };
    </script>
    <script src="{{ asset('assets/admin/js/dashboard.js') }}"></script>
@endsection
