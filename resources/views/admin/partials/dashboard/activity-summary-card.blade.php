<article class="card dashboard-kpi dashboard-kpi-large dashboard-kpi-activity-summary dashboard-tone-{{ $card['tone'] ?? 'neutral' }} w-100">
    <div class="card-body">
        <div class="dashboard-kpi-topline">
            <span class="dashboard-kpi-icon"><i data-lucide="{{ $card['icon'] }}"></i></span>
        </div>
        <p class="dashboard-kpi-label">{{ $card['label'] }}</p>
        <div class="dashboard-kpi-split">
            <div class="dashboard-kpi-split-item" data-dashboard-activity-metric="users">
                <strong class="dashboard-kpi-value">{{ number_format((int) $card['userValue']) }}</strong>
                <span>{{ __('Active Users') }}</span>
            </div>
            <div class="dashboard-kpi-split-item" data-dashboard-activity-metric="employees">
                <strong class="dashboard-kpi-value">{{ number_format((int) $card['employeeValue']) }}</strong>
                <span>{{ __('Active Employees') }}</span>
            </div>
        </div>
        <p class="dashboard-kpi-helper">{{ $card['helper'] }}</p>
        <p class="dashboard-kpi-meta">{{ $card['meta'] }}</p>
    </div>
</article>
