{{-- Full-width domain health summary — too much text for a col-md-3 stat card (especially RTL). --}}
<div class="col-12 mb-3">
    <div class="card card-round domain-health-panel">
        <div class="card-body py-3">
            <div class="row align-items-start">
                <div class="col-auto pr-0 pr-sm-2">
                    <div class="icon-big text-center domain-health-panel__icon">
                        <i data-lucide="heart-pulse"></i>
                    </div>
                </div>
                <div class="col min-w-0">
                    <p class="text-muted mb-2 mb-sm-3 font-weight-bold">{{ __('domain_health.counters_title') }}</p>
                    <div class="row mb-2 mb-sm-3">
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="domain-health-stat">
                                <small class="text-muted d-block">{{ __('domain_health.linked_label') }}</small>
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'linked'])) }}"
                                   class="h4 font-weight-bold text-success mb-0 d-block">
                                    <bdi dir="ltr">{{ $domainHealthCounts['linked'] ?? 0 }}</bdi>
                                </a>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <div class="domain-health-stat">
                                <small class="text-muted d-block">{{ __('domain_health.confirmed_issues_label') }}</small>
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'issues'])) }}"
                                   class="h4 font-weight-bold text-danger mb-0 d-block">
                                    <bdi dir="ltr">{{ $domainHealthCounts['confirmed_issues'] ?? 0 }}</bdi>
                                </a>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="domain-health-stat">
                                <small class="text-muted d-block">{{ __('domain_health.unchecked') }}</small>
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'unchecked'])) }}"
                                   class="h4 font-weight-bold text-secondary mb-0 d-block">
                                    <bdi dir="ltr">{{ $uncheckedCount }}</bdi>
                                </a>
                            </div>
                        </div>
                    </div>
                    @if (! empty($domainHealthCounts['by_code']))
                        <div class="domain-health-badges d-flex flex-wrap">
                            @foreach ($issueHealthCodes as $code)
                                @if (($domainHealthCounts['by_code'][$code] ?? 0) > 0)
                                    @php
                                        $badgeLabel = __("domain_health.{$code}_short");
                                        if ($badgeLabel === "domain_health.{$code}_short") {
                                            $badgeLabel = __("domain_health.{$code}");
                                        }
                                    @endphp
                                    <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => $code])) }}"
                                       class="{{ $healthCodeBadgeClass($code) }} domain-health-badge mb-2 mr-2"
                                       title="{{ __("domain_health.{$code}") }}">
                                        <span class="domain-health-badge__count">{{ $domainHealthCounts['by_code'][$code] }}</span>
                                        <span class="domain-health-badge__label">{{ $badgeLabel }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
