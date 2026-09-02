@if (! empty($reconciliationSummary))
@php
    $platformDomains = array_map('strtolower', (array) config('services.vercel.platform_domains', []));
    $isProtectedHostname = static function (?string $name) use ($platformDomains): bool {
        $name = strtolower(trim((string) $name));
        if ($name === '' || str_contains($name, '*')) {
            return true;
        }
        if (in_array($name, $platformDomains, true)) {
            return true;
        }
        if (str_starts_with($name, 'www.')) {
            $apex = substr($name, 4);
            if (in_array($apex, $platformDomains, true)) {
                return true;
            }
        }

        return false;
    };
@endphp
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

            @foreach (['db_only', 'vercel_only', 'protected_platform', 'ownership_required', 'dns_issues', 'legacy_table_orphan', 'unpaired_www'] as $section)
                @php $items = $reconciliationSummary[$section] ?? []; @endphp
                @if ($items !== [])
                    <details class="mb-2">
                        <summary class="font-weight-bold small">{{ __("vercel_reconciliation.{$section}") }} ({{ count($items) }})</summary>
                        <ul class="small mb-0 mt-2 pl-3 list-unstyled">
                            @foreach (array_slice($items, 0, 20) as $item)
                                @php
                                    $label = $item['custom_name']
                                        ?? $item['name']
                                        ?? $item['vercel_name']
                                        ?? $item['apex']
                                        ?? '';
                                    $apex = $item['apex'] ?? (isset($item['missing']) ? $item['missing'] : null);
                                    if ($apex === null && isset($item['vercel_name']) && str_starts_with((string) $item['vercel_name'], 'www.')) {
                                        $apex = substr((string) $item['vercel_name'], 4);
                                    }
                                    if ($apex === null && $label !== '' && ! str_starts_with((string) $label, 'www.')) {
                                        $apex = $label;
                                    }
                                    $actionHostname = $item['vercel_name'] ?? $item['name'] ?? $apex ?? $label;
                                    $protected = $isProtectedHostname($actionHostname)
                                        || ($apex !== null && $isProtectedHostname($apex));
                                @endphp
                                <li dir="auto" class="mb-2 d-flex flex-wrap align-items-center">
                                    <span class="mr-2">
                                        @if (! empty($item['custom_name']))
                                            {{ $item['custom_name'] }}
                                        @elseif (! empty($item['name']))
                                            {{ $item['name'] }}
                                        @elseif (! empty($item['vercel_name']))
                                            {{ $item['vercel_name'] }} → {{ $item['missing'] ?? '' }}
                                        @elseif (! empty($item['apex']))
                                            {{ $item['apex'] }}
                                        @endif
                                        @if (! empty($item['requested_domain']) && empty($item['custom_name']))
                                            <span class="text-muted">({{ $item['requested_domain'] }})</span>
                                        @endif
                                    </span>
                                    @if ($section === 'legacy_table_orphan' && ! empty($item['id']) && ! $protected)
                                        <form class="d-inline domain-reconciliation-form mr-1" action="{{ route('admin.custom-domain.legacy-orphan.adopt') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="legacy_id" value="{{ $item['id'] }}">
                                            <button type="submit" class="btn btn-outline-success btn-xs btn-sm py-0 px-2">{{ __('domain_reconciliation.legacy_adopt') }}</button>
                                        </form>
                                        <form class="d-inline domain-reconciliation-form domain-legacy-delete-form" action="{{ route('admin.custom-domain.legacy-orphan.delete') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="legacy_id" value="{{ $item['id'] }}">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-xs btn-sm py-0 px-2 domain-legacy-deletebtn"
                                                    data-domain="{{ $apex ?? $label }}">{{ __('domain_reconciliation.legacy_delete') }}</button>
                                        </form>
                                    @elseif ($section === 'vercel_only' && ! empty($apex) && ! $protected)
                                        <form class="d-inline domain-reconciliation-form domain-vercel-orphan-form" action="{{ route('admin.custom-domain.cleanup-vercel-orphan') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="apex" value="{{ $apex }}">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-xs btn-sm py-0 px-2 domain-vercel-orphan-btn"
                                                    data-domain="{{ $apex }}">{{ __('domain_reconciliation.cleanup_orphan') }}</button>
                                        </form>
                                    @elseif ($section === 'unpaired_www' && ($item['type'] ?? '') === 'www_without_apex' && ! empty($item['vercel_name']) && ! $protected)
                                        <form class="d-inline domain-reconciliation-form domain-stray-www-form" action="{{ route('admin.custom-domain.stray-www.remove') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="www" value="{{ $item['vercel_name'] }}">
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-xs btn-sm py-0 px-2 domain-stray-www-btn"
                                                    data-domain="{{ $item['vercel_name'] }}">{{ __('domain_reconciliation.remove_stray_www') }}</button>
                                        </form>
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
