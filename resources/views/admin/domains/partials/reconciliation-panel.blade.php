@if (! empty($reconciliationSummary))
<div class="col-12 mb-3">
    <details class="card card-round domain-reconciliation-panel">
        <summary class="card-header py-3 cursor-pointer mb-0">
            <strong>{{ __('vercel_reconciliation.title') }}</strong>
            <span class="text-muted small ml-2">{{ __('vercel_reconciliation.report_only') }}</span>
        </summary>
        <div class="card-body py-3">
            <div class="row mb-3">
                @foreach ($reconciliationSummary['summary'] as $category => $count)
                    <div class="col-sm-4 col-md-2 mb-2">
                        <div class="domain-health-stat">
                            <small class="text-muted d-block">{{ __("vercel_reconciliation.{$category}") }}</small>
                            <span class="h5 font-weight-bold mb-0"><bdi dir="ltr">{{ $count }}</bdi></span>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach (['db_only', 'vercel_only', 'protected_platform', 'ownership_required', 'dns_issues', 'unpaired_www'] as $section)
                @php $items = $reconciliationSummary[$section] ?? []; @endphp
                @if ($items !== [])
                    <details class="mb-2">
                        <summary class="font-weight-bold small">{{ __("vercel_reconciliation.{$section}") }} ({{ count($items) }})</summary>
                        <ul class="small mb-0 mt-2 pl-3">
                            @foreach (array_slice($items, 0, 20) as $item)
                                <li dir="auto">
                                    @if (! empty($item['custom_name']))
                                        {{ $item['custom_name'] }}
                                    @elseif (! empty($item['name']))
                                        {{ $item['name'] }}
                                    @elseif (! empty($item['vercel_name']))
                                        {{ $item['vercel_name'] }} → {{ $item['missing'] ?? '' }}
                                    @endif
                                </li>
                            @endforeach
                            @if (count($items) > 20)
                                <li class="text-muted">{{ __('vercel_reconciliation.truncated', ['count' => count($items) - 20]) }}</li>
                            @endif
                        </ul>
                    </details>
                @endif
            @endforeach
        </div>
    </details>
</div>
@endif
