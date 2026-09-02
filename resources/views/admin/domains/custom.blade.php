@extends('admin.layout')

@section('content')
@php
    $domainType = request()->input('type');
    $healthFilter = $healthFilter ?? request()->input('health');
    $domainPageTitles = [
        'pending' => __('Pending Custom Domains'),
        'connected' => __('Connected Custom Domains'),
        'failed' => __('Rejected Custom Domains'),
        'rejected' => __('Rejected Custom Domains'),
    ];
    $domainPageTitle = empty($domainType) ? __('All Custom Domains') : ($domainPageTitles[$domainType] ?? __('All Custom Domains'));
    if (! empty($healthFilter)) {
        if ($healthFilter === 'issues') {
            $domainPageTitle = __('domain_health.filter_confirmed_issues');
        } elseif ($healthFilter === 'unchecked') {
            $domainPageTitle = __('domain_health.filter_unchecked');
        } elseif ($healthFilter === 'linked') {
            $domainPageTitle = __('domain_health.filter_linked');
        } else {
            $domainPageTitle = __('domain_health.filter_code', ['code' => __("domain_health.{$healthFilter}")]);
        }
    }
    $healthFilterParams = array_filter([
        'type' => request()->input('type'),
        'username' => request()->input('username'),
        'domain' => request()->input('domain'),
    ]);
    $globalHealthFilterParams = [];
    $wwwStatesByDomainId = $wwwStatesByDomainId ?? [];
    $capacityBlocked = $capacityBlocked ?? false;
    $inventoryUnreliable = $inventoryUnreliable ?? false;
    $nonProductionSharedProject = $nonProductionSharedProject ?? false;
    $issueHealthCodes = ['unchecked', 'ns_not_pointing', 'not_on_vercel', 'unverified', 'expired', 'provider_error'];
    $linkedCount = $domainHealthCounts['linked'] ?? 0;
    $confirmedIssuesCount = $domainHealthCounts['confirmed_issues'] ?? 0;
    $uncheckedCount = $domainHealthCounts['unchecked'] ?? ($domainHealthCounts['by_code']['unchecked'] ?? 0);
    $healthCodeBadgeClass = function (string $code): string {
        return match ($code) {
            'ns_mismatch', 'ns_not_pointing', 'unverified' => 'badge badge-warning text-dark',
            'provider_error', 'unchecked', 'checks_disabled' => 'badge badge-secondary',
            default => 'badge badge-danger',
        };
    };
    $isIssuesFilter = $healthFilter === 'issues';
    $isUncheckedFilter = $healthFilter === 'unchecked';
    $isLinkedFilter = $healthFilter === 'linked';
    $hasHealthFilter = ! empty($healthFilter);
@endphp
<div class="page-header">
    <h4 class="page-title">{{ $domainPageTitle }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{route('admin.dashboard')}}">
            <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{__('Custom Domains')}}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{ $domainPageTitle }}</a>
        </li>
    </ul>
</div>
@if ($nonProductionSharedProject)
<div class="alert alert-warning">
    {{ __('domain_mutation.non_production_banner') }}
</div>
@endif
@if ($vercelCapacity ?? null)
@php
    $capColors = [
        'success' => '16, 185, 129',
        'warning' => '245, 158, 11',
        'danger'  => '239, 68, 68',
    ];
    $capState = $vercelCapacity['alert_class'];
    $capRgb = $capColors[$capState] ?? $capColors['success'];
    $capHex = ['success' => '#10b981', 'warning' => '#f59e0b', 'danger' => '#ef4444'][$capState] ?? '#10b981';
    $capPercent = min(100, round($vercelCapacity['usage_percent']));
    $showCapFraction = $vercelCapacity['has_cap'] ?? false;
@endphp
<div class="row">

    {{-- Total Vercel project entries --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba({{ $capRgb }}, 0.1); color: {{ $capHex }}; border-radius: 12px; padding: 10px;">
                            <i data-lucide="layers"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('vercel_capacity.total_entries') }}</p>
                            <h4 class="card-title font-weight-bold mb-0">
                                <bdi dir="ltr">
                                    @if ($showCapFraction)
                                        {{ $vercelCapacity['entries_used'] }} / {{ $vercelCapacity['entries_total'] }}
                                    @else
                                        {{ $vercelCapacity['entries_used'] }}
                                    @endif
                                </bdi>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer apex domains on the project --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5; border-radius: 12px; padding: 10px;">
                            <i data-lucide="globe"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('vercel_capacity.customer_apex') }}</p>
                            <h4 class="card-title font-weight-bold mb-0">{{ $vercelCapacity['customer_apex'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Optional www redirects --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 12px; padding: 10px;">
                            <i data-lucide="arrow-right-left"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('vercel_capacity.www_redirects') }}</p>
                            <h4 class="card-title font-weight-bold mb-0">{{ $vercelCapacity['www_redirects'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Free entries or usage percentage --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba({{ $capRgb }}, 0.1); color: {{ $capHex }}; border-radius: 12px; padding: 10px;">
                            <i data-lucide="gauge"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            @if ($showCapFraction)
                                <p class="card-category text-muted mb-1">{{ __('vercel_capacity.free_entries') }}</p>
                                <h4 class="card-title font-weight-bold mb-0" style="color: {{ $capHex }};">{{ $vercelCapacity['free_entries'] ?? '—' }}</h4>
                                <small class="text-muted"><bdi dir="ltr">{{ $capPercent }}%</bdi> {{ __('vercel_capacity.used_label') }}</small>
                            @else
                                <p class="card-category text-muted mb-1">{{ __('vercel_capacity.no_cap_configured') }}</p>
                                <h4 class="card-title font-weight-bold mb-0">—</h4>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@if ($domainHealthCounts ?? null)
<div class="row">
    @include('admin.domains.partials.health-panel')
</div>
@endif
@php
    $dbDomainCount = ($domainHealthCounts ?? null) ? ($domainHealthCounts['db_domain_count'] ?? null) : null;
    $vercelCustomerCount = $vercelCapacity['customer_apex'];
@endphp
<div class="row">
    @include('admin.domains.partials.capacity-context', [
        'vercelCapacity' => $vercelCapacity,
        'capPercent' => $capPercent,
        'capHex' => $capHex,
        'dbDomainCount' => $dbDomainCount,
        'vercelCustomerCount' => $vercelCustomerCount,
        'inventoryUnreliable' => $inventoryUnreliable,
    ])
</div>
@if ($reconciliationSummary ?? null)
<div class="row">
    @include('admin.domains.partials.reconciliation-panel', ['reconciliationSummary' => $reconciliationSummary])
</div>
@endif
@elseif ($domainHealthCounts ?? null)
<div class="row">
    @include('admin.domains.partials.health-panel')
</div>
@endif
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{ $domainPageTitle }}</div>
                        @if ($isLinkedFilter)
                            <span class="badge badge-success ml-2 align-middle">{{ __('domain_health.linked_label') }}</span>
                        @elseif ($isIssuesFilter)
                            <span class="badge badge-danger ml-2 align-middle">{{ __('domain_health.confirmed_issues_label') }}</span>
                        @elseif ($isUncheckedFilter)
                            <span class="badge badge-secondary ml-2 align-middle">{{ __('domain_health.unchecked') }}</span>
                        @elseif ($hasHealthFilter)
                            <span class="badge badge-warning text-dark ml-2 align-middle">{{ __("domain_health.{$healthFilter}") }}</span>
                        @endif
                    </div>
                    <div class="col-lg-8 mt-2 mt-lg-0">
                        <div class="float-lg-right d-flex flex-wrap align-items-center justify-content-lg-end domain-domains-toolbar">
                        <div class="btn-group btn-group-sm mr-2">
                            @if ($isLinkedFilter)
                                <span class="btn btn-success disabled">{{ __('domain_health.show_working_only_count', ['count' => $linkedCount]) }}</span>
                            @else
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'linked'])) }}"
                                   class="btn btn-outline-success">{{ __('domain_health.show_working_only_count', ['count' => $linkedCount]) }}</a>
                            @endif
                            @if ($isUncheckedFilter)
                                <span class="btn btn-secondary disabled">{{ __('domain_health.show_not_checked_count', ['count' => $uncheckedCount]) }}</span>
                            @else
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'unchecked'])) }}"
                                   class="btn btn-outline-secondary">{{ __('domain_health.show_not_checked_count', ['count' => $uncheckedCount]) }}</a>
                            @endif
                            @if ($isIssuesFilter)
                                <span class="btn btn-warning disabled">{{ __('domain_health.show_confirmed_issues_count', ['count' => $confirmedIssuesCount]) }}</span>
                            @else
                                <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'issues'])) }}"
                                   class="btn btn-outline-danger">{{ __('domain_health.show_confirmed_issues_count', ['count' => $confirmedIssuesCount]) }}</a>
                            @endif
                            @if ($hasHealthFilter)
                                <a href="{{ route('admin.custom-domain.index', $healthFilterParams) }}"
                                   class="btn btn-secondary">{{ __('domain_health.show_all_domains') }}</a>
                            @endif
                        </div>
                        <button class="btn btn-danger btn-sm ml-2 d-none bulk-delete" data-href="{{route('admin.custom-domain.bulk.delete')}}"><i class="flaticon-interface-5"></i> {{__('Delete')}}</button>
                        <form action="{{request()->url()}}" class="d-flex">
                            @if (!empty(request()->input('type')))
                                <input type="hidden" name="type" value="{{request()->input('type')}}">
                            @endif
                            @if (!empty(request()->input('health')))
                                <input type="hidden" name="health" value="{{request()->input('health')}}">
                            @endif
                            <input name="username" class="min-w-180 form-control mr-2" type="text" placeholder="{{ __('Search by Username') }}" value="{{request()->input('username')}}">
                            <input name="domain" class="min-w-180 form-control" type="text" placeholder="{{ __('Search by Domain') }}" value="{{request()->input('domain')}}">
                            <button type="submit" class="d-none"></button>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (count($rcDomains) == 0)
                        <h3 class="text-center">{{__('NO REQUEST FOUND')}}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mt-3 domain-table">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th>{{__('Username')}}</th>
                                        <th scope="col">{{__('Requested Domain')}}</th>
                                        <th scope="col">{{ __('domain_www.column') }}</th>
                                        <th scope="col">{{ __('domain_health.column') }}</th>
                                        <th scope="col">{{__('SSL')}}</th>
                                        <th scope="col">{{__('Status')}}</th>
                                        <th>{{__('Action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rcDomains as $rcDomain)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{$rcDomain->id}}">
                                        </td>
                                        @if (!empty($rcDomain->user))
                                        <td class="domain-user-cell"><a href="{{route('admin.register.user.view', $rcDomain->user->id)}}" target="_blank" title="{{$rcDomain->user->username}}">{{$rcDomain->user->username}}</a></td>
                                        @else
                                        <td class="domain-user-cell">-</td>
                                        @endif
                                        <td class="domain-name-cell">
                                            @if (!empty($rcDomain->custom_name))
                                            <a href="//{{$rcDomain->custom_name}}" target="_blank" title="{{$rcDomain->custom_name}}">{{$rcDomain->custom_name}}</a>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $wwwState = $wwwStatesByDomainId[$rcDomain->id] ?? ['mode' => 'unknown', 'can_enable' => false, 'can_disable' => false];
                                            @endphp
                                            @if ($wwwState['mode'] === 'apex_and_www')
                                                <span class="badge badge-info">{{ __('domain_www.apex_and_www') }}</span>
                                            @else
                                                <span class="badge badge-light text-dark border">{{ __('domain_www.apex_only') }}</span>
                                            @endif
                                        </td>
                                        <td class="domain-health-cell">
                                            @php
                                                $health = $rcDomain->health;
                                                $healthTooltip = $health['reason'] ?? '';
                                                if (!empty($health['checked_at'])) {
                                                    $checkedAgo = \Illuminate\Support\Carbon::parse($health['checked_at'])->locale(app()->getLocale())->diffForHumans();
                                                    $healthTooltip = trim($healthTooltip . ' ' . __('domain_health.checked_ago', ['time' => $checkedAgo]));
                                                }
                                                $badgeClass = 'badge badge-' . ($health['class'] ?? 'secondary');
                                                if (($health['class'] ?? '') === 'warning') {
                                                    $badgeClass .= ' text-dark';
                                                }
                                            @endphp
                                            <div class="domain-health-cell__inner">
                                                <span class="{{ $badgeClass }}"
                                                      title="{{ $healthTooltip }}">{{ $health['label'] }}</span>
                                                @if (! empty($health['reason']))
                                                    <div class="domain-health-cell__reason" dir="auto">{{ $health['reason'] }}</div>
                                                @elseif ($health['code'] === 'unchecked')
                                                    <div class="domain-health-cell__reason">{{ __('domain_health.unchecked_hint') }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($rcDomain->ssl)
                                                <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                            @endif
                                        </td>
                                        <td class="domain-status-cell">
                                            @php
                                                if ($rcDomain->status == 'active') {
                                                    $statusBadgeClass = 'badge badge-success';
                                                    $statusLabel = __('Connected');
                                                } elseif ($rcDomain->status == 'failed' || $rcDomain->status == 'rejected') {
                                                    $statusBadgeClass = 'badge badge-danger';
                                                    $statusLabel = __('Rejected');
                                                } else {
                                                    $statusBadgeClass = 'badge badge-warning text-dark';
                                                    $statusLabel = __('Pending');
                                                }
                                            @endphp
                                            <span class="{{ $statusBadgeClass }} domain-status-badge">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="domain-actions-cell">
                                            <div class="domain-actions-wrap">
                                            <button class="btn btn-secondary btn-sm editbtn" data-toggle="modal" data-target="#mailModal" data-email="{{!empty($rcDomain->user) ? $rcDomain->user->email : ''}}" title="{{ __('Mail') }}" aria-label="{{ __('Mail') }}"><i class="fas fa-envelope"></i></button>
                                            <form class="domain-action-form" action="{{ route('admin.custom-domain.recheck') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $rcDomain->id }}">
                                                <button type="submit" class="btn btn-info btn-sm" title="{{ __('domain_health.recheck') }}" aria-label="{{ __('domain_health.recheck') }}"><i class="fas fa-sync"></i></button>
                                            </form>
                                            @if ($wwwState['can_enable'] ?? false)
                                            <form class="domain-action-form domain-www-form" action="{{ route('admin.custom-domain.www.enable') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $rcDomain->id }}">
                                                <button type="button"
                                                        class="btn btn-outline-primary btn-sm domain-www-enablebtn"
                                                        data-domain="{{ $rcDomain->custom_name }}"
                                                        title="{{ __('domain_www.enable') }}"
                                                        aria-label="{{ __('domain_www.enable') }}"><i class="fas fa-plus"></i></button>
                                            </form>
                                            @elseif ($wwwState['can_disable'] ?? false)
                                            <form class="domain-action-form domain-www-form" action="{{ route('admin.custom-domain.www.disable') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $rcDomain->id }}">
                                                <button type="button"
                                                        class="btn btn-outline-secondary btn-sm domain-www-disablebtn"
                                                        data-domain="{{ $rcDomain->custom_name }}"
                                                        title="{{ __('domain_www.disable') }}"
                                                        aria-label="{{ __('domain_www.disable') }}"><i class="fas fa-minus"></i></button>
                                            </form>
                                            @endif
                                            <form class="domain-action-form deleteform" action="{{route('admin.custom-domain.delete')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{$rcDomain->id}}">
                                                <button type="button" class="btn btn-danger btn-sm domain-deletebtn" data-domain="{{ $rcDomain->custom_name }}" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
                                                <span class="domain-delete-label d-none">{{__('Delete')}}</span>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer">
                {{$rcDomains->appends(['type' => request()->input('type'), 'health' => request()->input('health'), 'username' => request()->input('username'), 'domain' => request()->input('domain')])->links()}}
            </div>


            <!-- Send Mail Modal -->
            <div class="modal fade" id="mailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLongTitle">{{__('Send Mail')}}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="ajaxEditForm" class="" action="{{route('admin.custom-domain.mail')}}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="">{{__('Email Address')}} **</label>
                                    <input id="inemail" type="text" class="form-control" name="email" value="" placeholder="{{ __('Enter Your Email') }}">
                                    <p id="eerremail" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="">{{__('Subject')}} **</label>
                                    <input id="insubject" type="text" class="form-control " name="subject" value="" placeholder="{{__('Enter subject')}}">
                                    <p id="eerrsubject" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="">{{__('Message')}} **</label>
                                    <textarea id="inmessage" class="form-control summernote" name="message" placeholder="{{__('Enter message')}}" data-height="150"></textarea>
                                    <p id="eerrmessage" class="mb-0 text-danger em"></p>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Close')}}</button>
                            <button id="updateBtn" type="button" class="btn btn-primary">{{__('Send Mail')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .domain-health-panel__icon {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border-radius: 12px;
        padding: 12px;
        min-width: 52px;
    }
    .domain-health-stat {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fafafa;
    }
    .domain-health-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: normal;
        text-align: start;
        max-width: 100%;
        padding: 6px 10px;
        font-size: 0.8rem;
        line-height: 1.35;
        text-decoration: none;
    }
    .domain-health-badge:hover {
        text-decoration: none;
        opacity: 0.92;
    }
    .domain-health-badge__count {
        font-weight: 700;
        font-size: 0.95rem;
        min-width: 1.25rem;
    }
    .domain-health-badge__label {
        flex: 1;
        min-width: 0;
    }
    .domain-capacity-context--full {
        border-top: 3px solid #ef4444;
    }
    .domain-capacity-ops summary {
        cursor: pointer;
        user-select: none;
    }
    .domain-capacity-context code {
        font-size: 0.85em;
        word-break: break-all;
    }
    @media (max-width: 991.98px) {
        .domain-domains-toolbar {
            justify-content: flex-start !important;
            margin-top: 0.75rem;
        }
        .domain-domains-toolbar .btn-group {
            margin-bottom: 0.5rem;
        }
    }
    .domain-table td, .domain-table th { vertical-align: middle; }
    .domain-table td { padding-top: .4rem; padding-bottom: .4rem; }
    .domain-health-cell {
        min-width: 9rem;
        max-width: 14rem;
        vertical-align: middle !important;
    }
    .domain-health-cell__inner {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.35rem;
        max-width: 100%;
    }
    [dir="rtl"] .domain-health-cell__inner {
        align-items: flex-end;
        text-align: right;
    }
    .domain-health-cell__reason {
        font-size: 0.8rem;
        line-height: 1.45;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 14rem;
    }
    .domain-actions-cell {
        white-space: nowrap;
        min-width: 8.5rem;
        vertical-align: middle !important;
    }
    .domain-actions-wrap {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: .35rem;
    }
    .domain-action-form {
        display: inline-block;
        margin: 0;
    }
    .domain-table td.domain-user-cell,
    .domain-table td.domain-name-cell {
        white-space: nowrap;
        max-width: 8.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .domain-table td.domain-user-cell a,
    .domain-table td.domain-name-cell a {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .domain-health-cell {
        max-width: 11rem !important;
    }
    .domain-table td.domain-status-cell {
        white-space: nowrap;
    }
    .domain-status-badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery === 'undefined') {
            return;
        }

        jQuery('.bulk-check[data-val="all"]').on('change', function () {
            var isChecked = jQuery(this).is(':checked');
            jQuery('.bulk-check[data-val!="all"]').prop('checked', isChecked);
            jQuery('.bulk-delete').toggleClass('d-none', !isChecked);
        });

        jQuery('.bulk-check[data-val!="all"]').on('change', function () {
            var checkedCount = jQuery('.bulk-check[data-val!="all"]:checked').length;
            jQuery('.bulk-delete').toggleClass('d-none', checkedCount === 0);
        });

        var deleteTitleTpl = @json(__('domain_health.delete_title'));
        var deleteBodyTpl = @json(__('domain_health.delete_body'));
        var bulkDeleteTitle = @json(__('domain_health.bulk_delete_title'));
        var bulkDeleteBody = @json(__('domain_health.bulk_delete_body'));
        var sharedProjectWarning = @json(__('domain_mutation.shared_project_notice'));
        var confirmPrompt = @json(__('domain_mutation.type_domain_to_confirm'));
        var wwwEnableTitle = @json(__('domain_www.enable_title'));
        var wwwEnableBody = @json(__('domain_www.enable_body'));
        var wwwDisableTitle = @json(__('domain_www.disable_title'));
        var wwwDisableBody = @json(__('domain_www.disable_body'));

        function confirmDomainAction(options) {
            jQuery('.request-loader').addClass('show');

            swal({
                title: options.title,
                text: options.text + '\n\n' + sharedProjectWarning,
                type: 'warning',
                content: {
                    element: 'input',
                    attributes: {
                        placeholder: options.domain,
                        value: '',
                        autocapitalize: 'off',
                        autocorrect: 'off',
                    },
                },
                buttons: {
                    confirm: {
                        text: options.confirmText,
                        className: 'btn btn-success',
                    },
                    cancel: {
                        visible: true,
                        text: @json(__('Cancel')),
                        className: 'btn btn-danger',
                    },
                },
            }).then(function (value) {
                if (value && String(value).trim().toLowerCase() === String(options.domain).trim().toLowerCase()) {
                    options.onConfirm(String(value).trim());
                } else {
                    swal.close();
                    jQuery('.request-loader').removeClass('show');
                }
            });
        }

        jQuery('.bulk-delete').on('click', function (e) {
            e.preventDefault();

            var $btn = jQuery(this);
            var href = $btn.data('href');
            var ids = [];
            jQuery('.bulk-check[data-val!="all"]:checked').each(function () {
                ids.push(jQuery(this).data('val'));
            });

            if (ids.length === 0) {
                return;
            }

            var domainNames = [];
            ids.forEach(function (id) {
                var name = jQuery('.bulk-check[data-val="' + id + '"]').closest('tr').find('.domain-deletebtn').data('domain');
                if (name) {
                    domainNames.push(name);
                }
            });

            jQuery('.request-loader').addClass('show');

            swal({
                title: bulkDeleteTitle,
                text: bulkDeleteBody.replace(':domains', domainNames.join(', ') || ids.length) + '\n\n' + sharedProjectWarning + '\n\n' + confirmPrompt,
                type: 'warning',
                content: {
                    element: 'input',
                    attributes: {
                        placeholder: domainNames.join(', '),
                        value: '',
                        autocapitalize: 'off',
                        autocorrect: 'off',
                    },
                },
                buttons: {
                    confirm: {
                        text: @json(__('domain_health.delete_confirm')),
                        className: 'btn btn-success',
                    },
                    cancel: {
                        visible: true,
                        text: @json(__('Cancel')),
                        className: 'btn btn-danger',
                    },
                },
            }).then(function (value) {
                if (! value) {
                    swal.close();
                    jQuery('.request-loader').removeClass('show');
                    return;
                }

                var typedDomains = String(value).split(',').map(function (part) {
                    return part.trim().toLowerCase();
                }).filter(Boolean).sort();
                var expectedDomains = domainNames.map(function (name) {
                    return String(name).trim().toLowerCase();
                }).sort();

                if (typedDomains.join('|') !== expectedDomains.join('|')) {
                    swal.close();
                    jQuery('.request-loader').removeClass('show');
                    return;
                }

                jQuery.post(href, {
                    _token: @json(csrf_token()),
                    ids: ids,
                    confirm_domains: domainNames,
                }).done(function () {
                    location.reload();
                }).fail(function () {
                    jQuery('.request-loader').removeClass('show');
                });
            });
        });

        jQuery('.domain-deletebtn').on('click', function (e) {
            e.preventDefault();

            var $btn = jQuery(this);
            var domain = $btn.data('domain') || '';
            var $form = $btn.closest('.deleteform');

            confirmDomainAction({
                title: deleteTitleTpl.replace(':domain', domain),
                text: deleteBodyTpl.replace(':domain', domain) + '\n\n' + confirmPrompt,
                domain: domain,
                confirmText: @json(__('domain_health.delete_confirm')),
                onConfirm: function (typed) {
                    $form.find('input[name="confirm_domain"]').remove();
                    $form.append(jQuery('<input>', {
                        type: 'hidden',
                        name: 'confirm_domain',
                        value: typed,
                    }));
                    $form.trigger('submit');
                },
            });
        });

        jQuery('.domain-www-enablebtn').on('click', function (e) {
            e.preventDefault();
            var $btn = jQuery(this);
            var domain = $btn.data('domain') || '';
            var $form = $btn.closest('.domain-www-form');

            confirmDomainAction({
                title: wwwEnableTitle.replace(':domain', domain),
                text: wwwEnableBody.replace(':domain', domain) + '\n\n' + confirmPrompt,
                domain: domain,
                confirmText: @json(__('domain_www.enable_confirm')),
                onConfirm: function (typed) {
                    $form.find('input[name="confirm_domain"]').remove();
                    $form.append(jQuery('<input>', {
                        type: 'hidden',
                        name: 'confirm_domain',
                        value: typed,
                    }));
                    $form.trigger('submit');
                },
            });
        });

        jQuery('.domain-www-disablebtn').on('click', function (e) {
            e.preventDefault();
            var $btn = jQuery(this);
            var domain = $btn.data('domain') || '';
            var $form = $btn.closest('.domain-www-form');

            confirmDomainAction({
                title: wwwDisableTitle.replace(':domain', domain),
                text: wwwDisableBody.replace(':domain', domain) + '\n\n' + confirmPrompt,
                domain: domain,
                confirmText: @json(__('domain_www.disable_confirm')),
                onConfirm: function (typed) {
                    $form.find('input[name="confirm_domain"]').remove();
                    $form.append(jQuery('<input>', {
                        type: 'hidden',
                        name: 'confirm_domain',
                        value: typed,
                    }));
                    $form.trigger('submit');
                },
            });
        });
    });
</script>
@endsection
