<article class="card dashboard-panel dashboard-breakdown-card w-100">
    <div class="card-header dashboard-panel-header">
        <div>
            <h4>{{ $title }}</h4>
            <p>{{ number_format((int) ($group['total'] ?? 0)) }} {{ __('total records') }}</p>
        </div>
    </div>
    <div class="card-body">
        @php
            $total = max(0, (int) ($group['total'] ?? 0));
            $chartScope = isset($chartScope) ? (string) $chartScope : 'breakdown';
            $groupKey = isset($groupKey) ? (string) $groupKey : 'group';
            $chartKey = \Illuminate\Support\Str::slug($chartScope . '-' . $groupKey);
            $canvasId = 'dashboard-breakdown-chart-' . $chartKey;
            $summaryId = 'dashboard-breakdown-summary-' . $chartKey;
            $chartItems = [];
            $chartPayload = [];

            foreach (($group['items'] ?? []) as $item) {
                $itemKey = (string) ($item['key'] ?? 'unknown');
                $value = max(0, (int) ($item['value'] ?? 0));
                $percentage = $total > 0 ? round(($value / $total) * 100, 1) : 0;

                $chartItems[] = [
                    'key' => $itemKey,
                    'label' => $labels[$itemKey] ?? __($itemKey),
                    'value' => $value,
                    'percentage' => $percentage,
                ];
            }

            $hasChartData = $total > 0 && collect($chartItems)->contains(static function ($item) {
                return (int) ($item['value'] ?? 0) > 0;
            });

            if ($hasChartData) {
                $chartPayload = [
                    'chartKey' => $chartKey,
                    'title' => $title,
                    'total' => $total,
                    'totalLabel' => __('Total'),
                    'shareLabel' => __('Share of total'),
                    'items' => $chartItems,
                ];
            }
        @endphp

        @if($hasChartData)
            <div class="dashboard-breakdown-layout">
                <div
                    class="dashboard-breakdown-chart-shell"
                    role="group"
                    aria-label="{{ $title }}"
                    aria-describedby="{{ $summaryId }}"
                >
                    <div class="dashboard-breakdown-chart-wrap">
                        <canvas
                            id="{{ $canvasId }}"
                            class="dashboard-breakdown-canvas"
                            data-dashboard-breakdown='@json($chartPayload)'
                            role="img"
                            aria-label="{{ $title }}"
                            aria-describedby="{{ $summaryId }}"
                        ></canvas>
                    </div>
                </div>

                <div class="dashboard-breakdown-legend-wrap">
                    <ul id="{{ $summaryId }}" class="dashboard-breakdown-legend" aria-label="{{ $title }}">
                        @foreach($chartItems as $item)
                            <li class="dashboard-breakdown-row">
                                <span class="dashboard-breakdown-meta">
                                    <span class="dashboard-breakdown-copy">
                                        <span class="dashboard-breakdown-label-wrap">
                                            <span class="dashboard-breakdown-marker dashboard-breakdown-marker-{{ \Illuminate\Support\Str::slug($item['key']) }}" aria-hidden="true"></span>
                                            <span class="dashboard-breakdown-label">{{ $item['label'] }}</span>
                                        </span>
                                        <small class="dashboard-breakdown-share">{{ __('Share of total') }}</small>
                                    </span>
                                    <strong>
                                        {{ number_format((int) $item['value']) }}
                                        <small>{{ $item['percentage'] }}%</small>
                                    </strong>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="dashboard-empty-state dashboard-empty-state-small">
                <p>{{ __('No breakdown data available') }}</p>
            </div>
        @endif
    </div>
</article>
