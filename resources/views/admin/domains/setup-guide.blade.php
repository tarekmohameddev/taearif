@extends('admin.layout')

@section('content')
@php
    $vercelNsMode = \App\Models\Api\ApiDomainSetting::DNS_MODE_VERCEL_NS;
    $externalDnsMode = \App\Models\Api\ApiDomainSetting::DNS_MODE_EXTERNAL_DNS;
    $nameservers = $nameserverInstructions['nameservers'] ?? [];
    $externalDnsRecords = $externalDnsInstructions ?? [];
    $overviewPaneId = 'dns-guide-overview';
    $vercelPaneId = 'dns-guide-vercel-ns';
    $externalPaneId = 'dns-guide-external-dns';
    $troubleshootingPaneId = 'dns-guide-troubleshooting';
    $supportPaneId = 'dns-guide-support';
    $supportNameservers = $nameservers !== []
        ? implode("\n", array_map(static fn ($nameserver) => '- ' . $nameserver, $nameservers))
        : __('domain_setup_guide.placeholder_nameservers');
    $externalDnsSupportTemplate = __('domain_setup_guide.support_external_message_body', [
        'domain' => __('domain_setup_guide.placeholder_domain'),
        'apex_record_type' => $externalDnsRecords['apex_record_type'] ?? 'A',
        'apex_record_host' => $externalDnsRecords['apex_record_host'] ?? '@',
        'apex_record_value' => $externalDnsRecords['apex_record_value'] ?? '',
        'www_record_type' => $externalDnsRecords['www_record_type'] ?? 'CNAME',
        'www_record_host' => $externalDnsRecords['www_record_host'] ?? 'www',
        'www_record_value' => $externalDnsRecords['www_record_value'] ?? '',
        'ownership_host' => __('domain_setup_guide.placeholder_ownership_host'),
        'ownership_value' => __('domain_setup_guide.placeholder_ownership_value'),
    ]);
    $vercelNsSupportTemplate = __('domain_setup_guide.support_vercel_ns_message_body', [
        'domain' => __('domain_setup_guide.placeholder_domain'),
        'nameservers' => $supportNameservers,
    ]);
@endphp
<div class="domain-setup-guide">
    <div class="page-header">
        <h4 class="page-title">{{ __('domain_setup_guide.title') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.custom-domain.index') }}">{{ __('Custom Domains') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('domain_setup_guide.title') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info" dir="auto">
                <div class="font-weight-bold mb-1">{{ __('domain_setup_guide.diagnostics_title') }}</div>
                <div>{{ __('domain_setup_guide.diagnostics_body') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pb-0">
            <div class="domain-setup-guide__tabs-scroll">
                <ul class="nav nav-tabs domain-setup-guide__tabs" id="domainSetupGuideTabs" role="tablist">
                    <li class="nav-item">
                        <a
                            class="nav-link active"
                            id="dns-guide-overview-tab"
                            data-toggle="tab"
                            href="#{{ $overviewPaneId }}"
                            role="tab"
                            aria-controls="{{ $overviewPaneId }}"
                            aria-selected="true">
                            {{ __('domain_setup_guide.tab_overview') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="dns-guide-vercel-ns-tab"
                            data-toggle="tab"
                            href="#{{ $vercelPaneId }}"
                            role="tab"
                            aria-controls="{{ $vercelPaneId }}"
                            aria-selected="false">
                            {{ __('domain_setup_guide.tab_vercel_ns') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="dns-guide-external-dns-tab"
                            data-toggle="tab"
                            href="#{{ $externalPaneId }}"
                            role="tab"
                            aria-controls="{{ $externalPaneId }}"
                            aria-selected="false">
                            {{ __('domain_setup_guide.tab_external_dns') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="dns-guide-troubleshooting-tab"
                            data-toggle="tab"
                            href="#{{ $troubleshootingPaneId }}"
                            role="tab"
                            aria-controls="{{ $troubleshootingPaneId }}"
                            aria-selected="false">
                            {{ __('domain_setup_guide.tab_troubleshooting') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            id="dns-guide-support-tab"
                            data-toggle="tab"
                            href="#{{ $supportPaneId }}"
                            role="tab"
                            aria-controls="{{ $supportPaneId }}"
                            aria-selected="false">
                            {{ __('domain_setup_guide.tab_support') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tab-content pt-4" id="domainSetupGuideTabContent">
                <div
                    class="tab-pane fade show active"
                    id="{{ $overviewPaneId }}"
                    role="tabpanel"
                    aria-labelledby="dns-guide-overview-tab">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{{ __('domain_setup_guide.workflow_title') }}</div>
                                </div>
                                <div class="card-body">
                                    <ol class="mb-0 pl-3" dir="auto">
                                        <li>{{ __('domain_setup_guide.workflow_1') }}</li>
                                        <li>{!! __('domain_setup_guide.workflow_2', ['vercel_ns' => '<code dir="ltr">' . e($dnsModeOptions[$vercelNsMode] ?? $vercelNsMode) . '</code>', 'external_dns' => '<code dir="ltr">' . e($dnsModeOptions[$externalDnsMode] ?? $externalDnsMode) . '</code>']) !!}</li>
                                        <li>{{ __('domain_setup_guide.workflow_3') }}</li>
                                        <li>{{ __('domain_setup_guide.workflow_4') }}</li>
                                        <li>{{ __('domain_setup_guide.workflow_5') }}</li>
                                        <li>{{ __('domain_setup_guide.workflow_6') }}</li>
                                        <li>{{ __('domain_setup_guide.workflow_7') }}</li>
                                        <li>{{ __('domain_setup_guide.workflow_8') }}</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{{ __('domain_setup_guide.policy_title') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning mb-3" dir="auto">
                                        {{ __('domain_setup_guide.policy_warning') }}
                                    </div>
                                    <ul class="mb-0 pl-3" dir="auto">
                                        <li>{{ __('domain_setup_guide.policy_1') }}</li>
                                        <li>{{ __('domain_setup_guide.policy_2') }}</li>
                                        <li>{{ __('domain_setup_guide.policy_3') }}</li>
                                        <li>{{ __('domain_setup_guide.policy_4') }}</li>
                                        <li>{{ __('domain_setup_guide.policy_5') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{{ __('domain_setup_guide.mode_summary_title') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="font-weight-bold mb-2">{{ __('domain_setup_guide.choose_method_title') }}</div>
                                    <p class="text-muted" dir="auto">{{ __('domain_setup_guide.choose_method_intro') }}</p>
                                    <div class="mb-3">
                                        <div class="font-weight-bold"><code dir="ltr">{{ $dnsModeOptions[$vercelNsMode] ?? $vercelNsMode }}</code></div>
                                        <div class="text-muted" dir="auto">{{ __('domain_setup_guide.mode_summary_vercel_ns') }}</div>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm mt-3 domain-setup-guide__method-btn"
                                            data-guide-target="#{{ $vercelPaneId }}">
                                            {{ __('domain_setup_guide.method_choice_vercel_nameservers') }}
                                        </button>
                                    </div>
                                    <div class="domain-setup-guide__divider"></div>
                                    <div class="pt-3">
                                        <div class="font-weight-bold"><code dir="ltr">{{ $dnsModeOptions[$externalDnsMode] ?? $externalDnsMode }}</code></div>
                                        <div class="text-muted" dir="auto">{{ __('domain_setup_guide.mode_summary_external_dns') }}</div>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm mt-3 domain-setup-guide__method-btn"
                                            data-guide-target="#{{ $externalPaneId }}">
                                            {{ __('domain_setup_guide.method_choice_external_dns') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="{{ $vercelPaneId }}"
                    role="tabpanel"
                    aria-labelledby="dns-guide-vercel-ns-tab">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{!! __('domain_setup_guide.mode_a_title', ['mode' => '<code dir="ltr">' . e($dnsModeOptions[$vercelNsMode] ?? $vercelNsMode) . '</code>']) !!}</div>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3" dir="auto">{{ __('domain_setup_guide.mode_a_intro') }}</p>
                                    <div class="alert alert-danger" dir="auto">
                                        {{ __('domain_setup_guide.mode_a_warning') }}
                                    </div>
                                    @if ($nameservers !== [])
                                        <div class="mb-3">
                                            <div class="font-weight-bold mb-2">{{ __('domain_setup_guide.nameservers_title') }}</div>
                                            <ul class="mb-0 pl-3 domain-setup-guide__ltr-list">
                                                @foreach ($nameservers as $nameserver)
                                                    <li><code dir="ltr">{{ $nameserver }}</code></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <ol class="mb-0 pl-3" dir="auto">
                                        <li>{{ __('domain_setup_guide.mode_a_step_1') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_2') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_3') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_4') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_5') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_6') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_a_step_7') }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="{{ $externalPaneId }}"
                    role="tabpanel"
                    aria-labelledby="dns-guide-external-dns-tab">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{!! __('domain_setup_guide.mode_b_title', ['mode' => '<code dir="ltr">' . e($dnsModeOptions[$externalDnsMode] ?? $externalDnsMode) . '</code>']) !!}</div>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3" dir="auto">{{ __('domain_setup_guide.mode_b_intro') }}</p>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('domain_dns.record_type') }}</th>
                                                    <th>{{ __('domain_dns.record_name') }}</th>
                                                    <th>{{ __('domain_dns.record_value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['apex_record_type'] ?? 'A' }}</code></td>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['apex_record_host'] ?? '@' }}</code></td>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['apex_record_value'] ?? '' }}</code></td>
                                                </tr>
                                                <tr>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['www_record_type'] ?? 'CNAME' }}</code></td>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['www_record_host'] ?? 'www' }}</code></td>
                                                    <td><code dir="ltr">{{ $externalDnsRecords['www_record_value'] ?? '' }}</code></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <ul class="mb-0 pl-3" dir="auto">
                                        <li>{{ __('domain_setup_guide.mode_b_step_1') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_2') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_3') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_4') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_5') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_6') }}</li>
                                        <li>{{ __('domain_setup_guide.mode_b_step_7') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="{{ $troubleshootingPaneId }}"
                    role="tabpanel"
                    aria-labelledby="dns-guide-troubleshooting-tab">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{{ __('domain_setup_guide.troubleshooting_title') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="domain-setup-guide__troubleshooting">
                                        <div><strong>{{ __('domain_setup_guide.trouble_apex_www_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_apex_www_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_ssl_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_ssl_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_ownership_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_ownership_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_tenant_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_tenant_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_external_ns_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_external_ns_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_vercel_ns_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_vercel_ns_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_stale_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_stale_body') }}</span></div>
                                        <div><strong>{{ __('domain_setup_guide.trouble_provider_title') }}</strong> <span dir="auto">{{ __('domain_setup_guide.trouble_provider_body') }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="tab-pane fade"
                    id="{{ $supportPaneId }}"
                    role="tabpanel"
                    aria-labelledby="dns-guide-support-tab">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card border">
                                <div class="card-header">
                                    <div class="card-title">{{ __('domain_setup_guide.support_title') }}</div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted" dir="auto">{{ __('domain_setup_guide.support_intro') }}</p>

                                    <div class="card border domain-setup-guide__support-card">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('domain_setup_guide.support_external_title') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted" dir="auto">{{ __('domain_setup_guide.support_external_intro') }}</p>
                                            <label class="sr-only" for="domainSetupGuideExternalDnsTemplate">{{ __('domain_setup_guide.support_external_textarea_label') }}</label>
                                            <textarea id="domainSetupGuideExternalDnsTemplate" class="form-control domain-setup-guide__support-template" rows="22" readonly dir="auto">{{ $externalDnsSupportTemplate }}</textarea>
                                            <div class="d-flex flex-wrap align-items-center mt-3">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm mr-2 domain-setup-guide__copy-btn"
                                                    id="domainSetupGuideExternalDnsCopyBtn"
                                                    data-copy-target="domainSetupGuideExternalDnsTemplate"
                                                    data-copy-status="domainSetupGuideExternalDnsCopyStatus"
                                                    data-copy-fallback="domainSetupGuideExternalDnsCopyFallback"
                                                    aria-describedby="domainSetupGuideExternalDnsCopyStatus">
                                                    <i class="fas fa-copy"></i> {{ __('domain_setup_guide.copy_button') }}
                                                </button>
                                                <span id="domainSetupGuideExternalDnsCopyStatus" class="text-muted small" role="status" aria-live="polite">
                                                    {{ __('domain_setup_guide.copy_idle') }}
                                                </span>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0" id="domainSetupGuideExternalDnsCopyFallback">{{ __('domain_setup_guide.copy_hint') }}</p>
                                        </div>
                                    </div>

                                    <div class="card border domain-setup-guide__support-card mb-0">
                                        <div class="card-header">
                                            <div class="card-title">{{ __('domain_setup_guide.support_vercel_ns_title') }}</div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted" dir="auto">{{ __('domain_setup_guide.support_vercel_ns_intro') }}</p>
                                            <label class="sr-only" for="domainSetupGuideVercelNsTemplate">{{ __('domain_setup_guide.support_vercel_ns_textarea_label') }}</label>
                                            <textarea id="domainSetupGuideVercelNsTemplate" class="form-control domain-setup-guide__support-template" rows="18" readonly dir="auto">{{ $vercelNsSupportTemplate }}</textarea>
                                            <div class="d-flex flex-wrap align-items-center mt-3">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm mr-2 domain-setup-guide__copy-btn"
                                                    id="domainSetupGuideVercelNsCopyBtn"
                                                    data-copy-target="domainSetupGuideVercelNsTemplate"
                                                    data-copy-status="domainSetupGuideVercelNsCopyStatus"
                                                    data-copy-fallback="domainSetupGuideVercelNsCopyFallback"
                                                    aria-describedby="domainSetupGuideVercelNsCopyStatus">
                                                    <i class="fas fa-copy"></i> {{ __('domain_setup_guide.copy_button') }}
                                                </button>
                                                <span id="domainSetupGuideVercelNsCopyStatus" class="text-muted small" role="status" aria-live="polite">
                                                    {{ __('domain_setup_guide.copy_idle') }}
                                                </span>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0" id="domainSetupGuideVercelNsCopyFallback">{{ __('domain_setup_guide.copy_hint') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .domain-setup-guide {
        overflow-x: hidden;
    }

    .domain-setup-guide .card.border {
        border-color: #e9ecef !important;
    }

    .domain-setup-guide__tabs-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }

    .domain-setup-guide__tabs {
        flex-wrap: nowrap;
        min-width: max-content;
        border-bottom: 1px solid #dee2e6;
    }

    .domain-setup-guide__tabs .nav-link {
        white-space: nowrap;
        border-radius: 0.35rem 0.35rem 0 0;
        border-width: 1px 1px 0;
        font-weight: 500;
    }

    .domain-setup-guide__tabs .nav-link:focus {
        outline: 2px solid #80bdff;
        outline-offset: 2px;
    }

    .domain-setup-guide__tabs .nav-link.active {
        font-weight: 700;
        box-shadow: inset 0 -3px 0 currentColor;
    }

    .domain-setup-guide__divider {
        border-top: 1px solid #e9ecef;
    }

    .domain-setup-guide__method-btn {
        white-space: normal;
        text-align: start;
    }

    .domain-setup-guide__troubleshooting {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .domain-setup-guide__ltr-list {
        direction: ltr;
    }

    .domain-setup-guide__support-card + .domain-setup-guide__support-card {
        margin-top: 1.25rem;
    }

    .domain-setup-guide__support-template {
        font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        line-height: 1.75;
    }

    @media (max-width: 575.98px) {
        .domain-setup-guide .card-body {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .domain-setup-guide__tabs .nav-link {
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery === 'undefined') {
            return;
        }

        var validHashes = {
            '#{{ $overviewPaneId }}': '#dns-guide-overview-tab',
            '#{{ $vercelPaneId }}': '#dns-guide-vercel-ns-tab',
            '#{{ $externalPaneId }}': '#dns-guide-external-dns-tab',
            '#{{ $troubleshootingPaneId }}': '#dns-guide-troubleshooting-tab',
            '#{{ $supportPaneId }}': '#dns-guide-support-tab'
        };

        function replaceHash(hash) {
            if (! window.history || ! window.history.replaceState) {
                return;
            }

            var url = window.location.pathname + window.location.search + hash;
            window.history.replaceState(null, document.title, url);
        }

        function showGuideTab(hash) {
            var safeHash = validHashes[hash] ? hash : '#{{ $overviewPaneId }}';
            var triggerSelector = validHashes[safeHash];
            var trigger = triggerSelector ? document.querySelector(triggerSelector) : null;

            if (trigger) {
                jQuery(trigger).tab('show');
            }

            replaceHash(safeHash);
        }

        showGuideTab(window.location.hash);

        jQuery('#domainSetupGuideTabs a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            var targetHash = event.target.getAttribute('href');
            if (! validHashes[targetHash]) {
                return;
            }

            replaceHash(targetHash);
        });

        jQuery(document).on('click', '.domain-setup-guide__method-btn', function () {
            var targetHash = this.getAttribute('data-guide-target') || '';
            showGuideTab(targetHash);
        });

        jQuery(document).on('click', '.domain-setup-guide__copy-btn', function () {
            var copyButton = this;
            var targetId = copyButton.getAttribute('data-copy-target');
            var statusId = copyButton.getAttribute('data-copy-status');
            var fallbackId = copyButton.getAttribute('data-copy-fallback');
            var target = targetId ? document.getElementById(targetId) : null;
            var statusNode = statusId ? document.getElementById(statusId) : null;
            var fallbackNode = fallbackId ? document.getElementById(fallbackId) : null;

            if (! target || ! statusNode) {
                return;
            }

            var text = target.value || '';
            var setManualState = function () {
                target.focus();
                target.select();
                statusNode.textContent = @json(__('domain_setup_guide.copy_manual'));
                if (fallbackNode) {
                    fallbackNode.textContent = @json(__('domain_setup_guide.copy_manual_hint'));
                }
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    statusNode.textContent = @json(__('domain_setup_guide.copy_success'));
                }).catch(function () {
                    try {
                        target.focus();
                        target.select();
                        if (document.execCommand('copy')) {
                            statusNode.textContent = @json(__('domain_setup_guide.copy_success'));
                            return;
                        }
                    } catch (error) {
                    }

                    setManualState();
                });

                return;
            }

            try {
                target.focus();
                target.select();
                if (document.execCommand('copy')) {
                    statusNode.textContent = @json(__('domain_setup_guide.copy_success'));
                    return;
                }
            } catch (error) {
            }

            setManualState();
        });
    });
</script>
@endsection
