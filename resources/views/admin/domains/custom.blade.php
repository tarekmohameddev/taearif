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
    $issueHealthCodes = ['unchecked', 'ns_mismatch', 'not_on_vercel', 'unverified', 'expired', 'provider_error'];
    $linkedCount = $domainHealthCounts['linked'] ?? 0;
    $confirmedIssuesCount = $domainHealthCounts['confirmed_issues'] ?? 0;
    $uncheckedCount = $domainHealthCounts['unchecked'] ?? ($domainHealthCounts['by_code']['unchecked'] ?? 0);
    $healthCodeBadgeClass = function (string $code): string {
        return match ($code) {
            'ns_mismatch', 'unverified' => 'badge badge-warning text-dark',
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
@endphp
<div class="row">

    {{-- Entries consumed on the Vercel project --}}
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
                            <p class="card-category text-muted mb-1">{{ __('Vercel entries') }}</p>
                            {{-- bdi + dir=ltr: without isolation the RTL layout renders
                                 "49 / 50" as "50 / 49", which reads as nonsense. --}}
                            <h4 class="card-title font-weight-bold mb-0"><bdi dir="ltr">{{ $vercelCapacity['entries_used'] }} / {{ $vercelCapacity['entries_total'] }}</bdi></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actual customer domains, which is entries minus platform, halved --}}
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
                            <p class="card-category text-muted mb-1">{{ __('Customer domains') }}</p>
                            <h4 class="card-title font-weight-bold mb-0">{{ $vercelCapacity['customer_domains_in_use'] }}</h4>
                            <small class="text-muted">{{ __('vercel_capacity.customer_card_hint') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Headroom in whole customer domains --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba({{ $capRgb }}, 0.1); color: {{ $capHex }}; border-radius: 12px; padding: 10px;">
                            <i data-lucide="plus-circle"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('Can still add') }}</p>
                            <h4 class="card-title font-weight-bold mb-0" style="color: {{ $capHex }};">{{ $vercelCapacity['customer_domains_remaining'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Percentage of the project cap consumed --}}
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
                            <p class="card-category text-muted mb-1">{{ __('Capacity used') }}</p>
                            <h4 class="card-title font-weight-bold mb-0" style="color: {{ $capHex }};">{{ $capPercent }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($domainHealthCounts ?? null)
    {{-- Whole-table link health: linked vs issues --}}
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; padding: 10px;">
                            <i data-lucide="heart-pulse"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('domain_health.counters_title') }}</p>
                            {{-- bdi + dir=ltr: keep "linked / issues" order in RTL layouts --}}
                            <h4 class="card-title font-weight-bold mb-0">
                                <bdi dir="ltr">
                                    <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'linked'])) }}" class="text-success">{{ $domainHealthCounts['linked'] ?? 0 }}</a>
                                    /
                                    <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'issues'])) }}" class="text-danger">{{ $domainHealthCounts['confirmed_issues'] ?? 0 }}</a>
                                </bdi>
                            </h4>
                            <small class="text-muted">{{ __('domain_health.linked_label') }} / {{ __('domain_health.confirmed_issues_label') }}</small>
                            @if ($confirmedIssuesCount > 0 || $uncheckedCount > 0)
                                <small class="text-muted d-block mt-1">{{ __('domain_health.breakdown_summary', ['confirmed' => $confirmedIssuesCount, 'unchecked' => $uncheckedCount]) }}</small>
                            @endif
                            @if (! empty($domainHealthCounts['by_code']))
                                <div class="mt-2">
                                    @foreach ($issueHealthCodes as $code)
                                        @if (($domainHealthCounts['by_code'][$code] ?? 0) > 0)
                                            <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => $code])) }}"
                                               class="{{ $healthCodeBadgeClass($code) }} mr-1 mb-1">
                                                {{ __("domain_health.{$code}") }}: {{ $domainHealthCounts['by_code'][$code] }}
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
    </div>
    @endif

    {{-- Context bar: explain entries vs customer domains and DB vs Vercel --}}
    <div class="col-md-12">
        <div class="card card-round mb-3">
            <div class="card-body py-3">
                <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $capPercent }}%; background-color: {{ $capHex }};"
                         aria-valuenow="{{ $capPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted d-block mb-2">
                    {{ __('Each customer domain uses two Vercel entries (apex and www).') }}
                </small>
                <small class="text-muted d-block mb-2">
                    <bdi dir="ltr">{{ __('vercel_capacity.breakdown', [
                        'platform' => $vercelCapacity['platform_entries'],
                        'customer_entries' => $vercelCapacity['customer_entries_used'],
                        'customer_domains' => $vercelCapacity['customer_domains_in_use'],
                        'used' => $vercelCapacity['entries_used'],
                        'total' => $vercelCapacity['entries_total'],
                    ]) }}</bdi>
                </small>
                @php
                    $dbDomainCount = $domainHealthCounts['db_domain_count'] ?? null;
                    $vercelCustomerCount = $vercelCapacity['customer_domains_in_use'];
                @endphp
                @if ($dbDomainCount !== null && $dbDomainCount !== $vercelCustomerCount)
                    <div class="alert alert-warning py-2 px-3 mb-2 small">
                        <strong>{{ __('vercel_capacity.db_mismatch_title') }}</strong>
                        {{ __('vercel_capacity.db_mismatch_body', ['db' => $dbDomainCount, 'vercel' => $vercelCustomerCount]) }}
                        <ul class="mb-0 mt-2 pl-3">
                            <li><code>php artisan domains:reconcile-vercel</code> — {{ __('vercel_capacity.reconcile_hint') }}</li>
                            <li><code>php artisan domains:sync-vercel-status</code> — {{ __('vercel_capacity.sync_hint') }}</li>
                        </ul>
                    </div>
                @endif
                @if ($vercelCapacity['customer_domains_remaining'] === 0)
                    <small class="d-block" style="color: {{ $capHex }};">
                        <strong>{{ __('The project is at its limit — new customer domains will fail until capacity is increased.') }}</strong>
                    </small>
                @endif
                @if ($vercelCapacity['is_lower_bound'])
                    <small class="text-muted d-block mt-1">{{ __('Count capped at 1,000 entries; the real total may be higher.') }}</small>
                @endif
            </div>
        </div>
    </div>

</div>
@elseif ($domainHealthCounts ?? null)
<div class="row">
    <div class="col-sm-6 col-md-3">
        <div class="card card-stats card-round">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-4">
                        <div class="icon-big text-center" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; padding: 10px;">
                            <i data-lucide="heart-pulse"></i>
                        </div>
                    </div>
                    <div class="col-8 col-stats">
                        <div class="numbers">
                            <p class="card-category text-muted mb-1">{{ __('domain_health.counters_title') }}</p>
                            <h4 class="card-title font-weight-bold mb-0">
                                <bdi dir="ltr">
                                    <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'linked'])) }}" class="text-success">{{ $domainHealthCounts['linked'] ?? 0 }}</a>
                                    /
                                    <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => 'issues'])) }}" class="text-danger">{{ $domainHealthCounts['confirmed_issues'] ?? 0 }}</a>
                                </bdi>
                            </h4>
                            <small class="text-muted">{{ __('domain_health.linked_label') }} / {{ __('domain_health.confirmed_issues_label') }}</small>
                            @if ($confirmedIssuesCount > 0 || $uncheckedCount > 0)
                                <small class="text-muted d-block mt-1">{{ __('domain_health.breakdown_summary', ['confirmed' => $confirmedIssuesCount, 'unchecked' => $uncheckedCount]) }}</small>
                            @endif
                            @if (! empty($domainHealthCounts['by_code']))
                                <div class="mt-2">
                                    @foreach ($issueHealthCodes as $code)
                                        @if (($domainHealthCounts['by_code'][$code] ?? 0) > 0)
                                            <a href="{{ route('admin.custom-domain.index', array_merge($healthFilterParams, ['health' => $code])) }}"
                                               class="{{ $healthCodeBadgeClass($code) }} mr-1 mb-1">
                                                {{ __("domain_health.{$code}") }}: {{ $domainHealthCounts['by_code'][$code] }}
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
    </div>
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
                        <div class="float-right d-flex flex-wrap align-items-center justify-content-end">
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
                            <input name="username" class="min-w-250 form-control mr-2" type="text" placeholder="{{ __('Search by Username') }}" value="{{request()->input('username')}}">
                            <input name="domain" class="min-w-250 form-control" type="text" placeholder="{{ __('Search by Domain') }}" value="{{request()->input('domain')}}">
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
                            <table class="table table-striped mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th>{{__('Username')}}</th>
                                        <th scope="col">{{__('Requested Domain')}}</th>
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
                                        <td><a href="{{route('admin.register.user.view', $rcDomain->user->id)}}" target="_blank">{{$rcDomain->user->username}}</a></td>
                                        @else
                                        <td>-</td>
                                        @endif
                                        <td>
                                            @if (!empty($rcDomain->custom_name))
                                            <a href="//{{$rcDomain->custom_name}}" target="_blank">{{$rcDomain->custom_name}}</a>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $health = $rcDomain->health;
                                                $healthTooltip = $health['reason'] ?? '';
                                                if (!empty($health['checked_at'])) {
                                                    $checkedAgo = \Illuminate\Support\Carbon::parse($health['checked_at'])->diffForHumans();
                                                    $healthTooltip = trim($healthTooltip . ' ' . __('domain_health.checked_ago', ['time' => $checkedAgo]));
                                                }
                                                $badgeClass = 'badge badge-' . ($health['class'] ?? 'secondary');
                                                if (($health['class'] ?? '') === 'warning') {
                                                    $badgeClass .= ' text-dark';
                                                }
                                            @endphp
                                            <span class="{{ $badgeClass }}"
                                                  title="{{ $healthTooltip }}">{{ $health['label'] }}</span>
                                            @if (! empty($health['reason']))
                                                <small class="text-muted d-block mt-1" style="max-width: 240px; line-height: 1.3;">{{ $health['reason'] }}</small>
                                            @elseif ($health['code'] === 'unchecked')
                                                <small class="text-muted d-block mt-1">{{ __('domain_health.unchecked_hint') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rcDomain->ssl)
                                                <span class="badge badge-success">{{ __('Enabled') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form id="statusForm{{$rcDomain->id}}" action="{{route('admin.custom-domain.status')}}" method="POST">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{$rcDomain->id}}">
                                                <select class="max-w-130 form-control form-control-sm
                                                    @if($rcDomain->status == 'pending')
                                                    bg-warning text-white
                                                    @elseif($rcDomain->status == 'active')
                                                    bg-success text-white
                                                    @elseif($rcDomain->status == 'failed' || $rcDomain->status == 'rejected')
                                                    bg-danger text-white
                                                    @endif
                                                    " name="status" onchange="document.getElementById('statusForm{{$rcDomain->id}}').submit();">
                                                <option value="pending" {{$rcDomain->status == 'pending' ? 'selected' : ''}}>{{__('Pending')}}</option>
                                                <option value="active" {{$rcDomain->status == 'active' ? 'selected' : ''}}>{{__('Connected')}}</option>
                                                <option value="failed" {{$rcDomain->status == 'failed' || $rcDomain->status == 'rejected' ? 'selected' : ''}}>{{__('Rejected')}}</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <button class="btn btn-secondary btn-sm editbtn" data-toggle="modal" data-target="#mailModal" data-email="{{!empty($rcDomain->user) ? $rcDomain->user->email : ''}}">{{__('Mail')}}</button>
                                            <form class="d-inline-block" action="{{ route('admin.custom-domain.recheck') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{ $rcDomain->id }}">
                                                <button type="submit" class="btn btn-info btn-sm">{{ __('domain_health.recheck') }}</button>
                                            </form>
                                            <form class="d-inline-block deleteform" action="{{route('admin.custom-domain.delete')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="domain_id" value="{{$rcDomain->id}}">
                                                <button type="submit" class="btn btn-danger btn-sm domain-deletebtn" data-domain="{{ $rcDomain->custom_name }}">
                                                <span class="btn-label">
                                                <i class="fas fa-trash"></i>
                                                </span>
                                                {{__('Delete')}}
                                                </button>
                                            </form>
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
                text: bulkDeleteBody.replace(':domains', domainNames.join(', ') || ids.length),
                type: 'warning',
                buttons: {
                    confirm: {
                        text: @json(__('domain_health.delete_confirm')),
                        className: 'btn btn-success'
                    },
                    cancel: {
                        visible: true,
                        text: @json(__('Cancel')),
                        className: 'btn btn-danger'
                    }
                }
            }).then(function (confirmed) {
                if (confirmed) {
                    jQuery.post(href, {
                        _token: @json(csrf_token()),
                        ids: ids
                    }).done(function () {
                        location.reload();
                    }).fail(function () {
                        jQuery('.request-loader').removeClass('show');
                    });
                } else {
                    swal.close();
                    jQuery('.request-loader').removeClass('show');
                }
            });
        });

        jQuery('.domain-deletebtn').on('click', function (e) {
            e.preventDefault();

            var $btn = jQuery(this);
            var domain = $btn.data('domain') || '';

            jQuery('.request-loader').addClass('show');

            swal({
                title: deleteTitleTpl.replace(':domain', domain),
                text: deleteBodyTpl.replace(':domain', domain),
                type: 'warning',
                buttons: {
                    confirm: {
                        text: @json(__('domain_health.delete_confirm')),
                        className: 'btn btn-success'
                    },
                    cancel: {
                        visible: true,
                        text: @json(__('Cancel')),
                        className: 'btn btn-danger'
                    }
                }
            }).then(function (confirmed) {
                if (confirmed) {
                    $btn.closest('.deleteform').trigger('submit');
                } else {
                    swal.close();
                    jQuery('.request-loader').removeClass('show');
                }
            });
        });
    });
</script>
@endsection
