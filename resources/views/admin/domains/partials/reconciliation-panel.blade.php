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

    // Sections that carry a remediation action get a subtle accent.
    $sectionTone = [
        'db_only' => 'muted',
        'vercel_only' => 'danger',
        'protected_platform' => 'muted',
        'ownership_required' => 'warning',
        'dns_issues' => 'warning',
        'legacy_table_orphan' => 'danger',
        'unpaired_www' => 'warning',
    ];
@endphp
<div class="col-12 mb-3">
    <details class="card card-round domain-reconciliation-panel">
        <summary class="card-header py-3 cursor-pointer mb-0 recon-header">
            <span class="recon-header__title">
                <i data-lucide="git-compare-arrows" class="recon-header__icon"></i>
                <strong>{{ __('vercel_reconciliation.title') }}</strong>
            </span>
            <span class="badge badge-light border text-muted recon-header__tag">{{ __('vercel_reconciliation.report_only') }}</span>
        </summary>
        <div class="card-body">
            {{-- Summary metrics --}}
            <div class="recon-stats mb-4">
                @foreach ($reconciliationSummary['summary'] as $category => $count)
                    <div class="recon-stat {{ (int) $count > 0 ? 'recon-stat--active' : 'recon-stat--zero' }}">
                        <span class="recon-stat__label">{{ __("vercel_reconciliation.{$category}") }}</span>
                        <span class="recon-stat__count"><bdi dir="ltr">{{ $count }}</bdi></span>
                    </div>
                @endforeach
            </div>

            {{-- Detailed, actionable lists --}}
            <div class="recon-sections">
                @foreach (['db_only', 'vercel_only', 'protected_platform', 'ownership_required', 'dns_issues', 'legacy_table_orphan', 'unpaired_www'] as $section)
                    @php $items = $reconciliationSummary[$section] ?? []; @endphp
                    @if ($items !== [])
                        <details class="recon-section recon-section--{{ $sectionTone[$section] ?? 'muted' }}">
                            <summary class="recon-section__summary">
                                <span class="recon-section__chevron"><i data-lucide="chevron-right"></i></span>
                                <span class="recon-section__title">{{ __("vercel_reconciliation.{$section}") }}</span>
                                <span class="recon-section__count">{{ count($items) }}</span>
                            </summary>
                            <ul class="recon-list">
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
                                    <li class="recon-item">
                                        <span class="recon-item__name" dir="ltr" title="{{ $label }}">
                                            @if (! empty($item['custom_name']))
                                                {{ $item['custom_name'] }}
                                            @elseif (! empty($item['name']))
                                                {{ $item['name'] }}
                                            @elseif (! empty($item['vercel_name']))
                                                {{ $item['vercel_name'] }}
                                                <i data-lucide="arrow-right" class="recon-item__arrow"></i>
                                                {{ $item['missing'] ?? '' }}
                                            @elseif (! empty($item['apex']))
                                                {{ $item['apex'] }}
                                            @endif
                                            @if (! empty($item['requested_domain']) && empty($item['custom_name']))
                                                <span class="recon-item__meta">({{ $item['requested_domain'] }})</span>
                                            @endif
                                        </span>
                                        <span class="recon-item__actions">
                                            @if ($section === 'legacy_table_orphan' && ! empty($item['id']) && ! $protected)
                                                <form class="domain-reconciliation-form" action="{{ route('admin.custom-domain.legacy-orphan.adopt') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="legacy_id" value="{{ $item['id'] }}">
                                                    <button type="submit" class="btn btn-outline-success btn-sm recon-btn">{{ __('domain_reconciliation.legacy_adopt') }}</button>
                                                </form>
                                                <form class="domain-reconciliation-form domain-legacy-delete-form" action="{{ route('admin.custom-domain.legacy-orphan.delete') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="legacy_id" value="{{ $item['id'] }}">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm recon-btn domain-legacy-deletebtn"
                                                            data-domain="{{ $apex ?? $label }}">{{ __('domain_reconciliation.legacy_delete') }}</button>
                                                </form>
                                            @elseif ($section === 'vercel_only' && ! empty($apex) && ! $protected)
                                                <form class="domain-reconciliation-form domain-vercel-orphan-form" action="{{ route('admin.custom-domain.cleanup-vercel-orphan') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="apex" value="{{ $apex }}">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm recon-btn domain-vercel-orphan-btn"
                                                            data-domain="{{ $apex }}">{{ __('domain_reconciliation.cleanup_orphan') }}</button>
                                                </form>
                                            @elseif ($section === 'unpaired_www' && ($item['type'] ?? '') === 'www_without_apex' && ! empty($item['vercel_name']) && ! $protected)
                                                <form class="domain-reconciliation-form domain-stray-www-form" action="{{ route('admin.custom-domain.stray-www.remove') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="www" value="{{ $item['vercel_name'] }}">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm recon-btn domain-stray-www-btn"
                                                            data-domain="{{ $item['vercel_name'] }}">{{ __('domain_reconciliation.remove_stray_www') }}</button>
                                                </form>
                                            @elseif ($protected)
                                                <span class="recon-item__locked" title="{{ __('vercel_reconciliation.protected_platform') }}"><i data-lucide="lock"></i></span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                                @if (count($items) > 20)
                                    <li class="recon-item recon-item--more">{{ __('vercel_reconciliation.truncated', ['count' => count($items) - 20]) }}</li>
                                @endif
                            </ul>
                        </details>
                    @endif
                @endforeach
            </div>
        </div>
    </details>
</div>
@endif
