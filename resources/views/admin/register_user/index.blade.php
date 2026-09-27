@extends('admin.layout')

@section('styles')
<style>
    .table.register-users-table thead th,
    .table.register-users-table thead td,
    .table.register-users-table tbody td {
        padding-top: 0.3rem !important;
        padding-bottom: 0.3rem !important;
        padding-left: 0.2rem !important;
        padding-right: 0.2rem !important;
    }

    .ru-stats-card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0, 0, 0, .07); overflow: hidden; }
    .ru-stats-header { padding: .6rem 1.1rem; background: linear-gradient(45deg, #000, #333); }
    .ru-stats-header h5 { font-size: 1rem; font-weight: 600; }
    .ru-stats-body { padding: .85rem 1.1rem; }

    .ru-stats-label { font-size: .78rem; font-weight: 600; color: #6c757d; margin-bottom: .4rem; }
    .ru-stats-hint { font-size: .72rem; font-weight: 400; }
    .ru-stats-divider { margin: .85rem 0 .7rem; border-top: 1px dashed #dee2e6; }

    .ru-stats-grid { display: flex; flex-wrap: wrap; gap: .45rem; }
    .ru-stat-tile {
        flex: 1 1 118px; min-width: 118px;
        padding: .45rem .5rem;
        border-radius: 9px;
        background-color: rgba(0, 0, 0, .045);
        text-align: center;
    }
    .ru-stat-tile-filtered { background-color: rgba(13, 110, 253, .08); border: 1px solid rgba(13, 110, 253, .18); }

    .ru-stat-title { font-size: .72rem; color: #6c757d; line-height: 1.25; min-height: 2.5em; }
    .ru-stat-count { font-size: 1.35rem; font-weight: 700; color: #000; line-height: 1.2; }
    .ru-stat-unit  { font-size: .68rem; color: #adb5bd; }

    .ru-toolbar { display: flex; flex-direction: column; gap: .75rem; width: 100%; }
    .ru-toolbar-top,
    .ru-toolbar-bottom {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem .75rem;
    }
    .ru-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .4rem;
    }
    .ru-search-form {
        flex: 1 1 280px;
        max-width: 480px;
        margin: 0;
        display: flex;
        align-items: stretch;
        gap: .35rem;
    }
    .ru-search-field {
        position: relative;
        flex: 1 1 auto;
        min-width: 0;
    }
    .ru-search-form .form-control {
        width: 100%;
        padding-inline-end: 2rem;
    }
    .ru-search-clear {
        position: absolute;
        inset-inline-end: .35rem;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #6c757d;
        width: 1.6rem;
        height: 1.6rem;
        padding: 0;
        line-height: 1;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .ru-search-clear:hover {
        color: #212529;
        background: rgba(0, 0, 0, .06);
    }
    .ru-search-field.is-filled .ru-search-clear {
        display: inline-flex;
    }
    .ru-search-form .btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .ru-toolbar-top,
        .ru-toolbar-bottom { align-items: stretch; }
        .ru-toolbar-actions { width: 100%; }
        .ru-toolbar-actions .btn { flex: 1 1 auto; }
        .ru-search-form { max-width: none; width: 100%; }
    }
</style>
@endsection

@section('content')
<style>
    .date-range-filter {
        background: #f8f9fa;
        padding: 2px;
        border-radius: 5px;
    }

    .dropdown-menu .dropdown-item {
        transition: transform 0.5s ease;
        transform-origin: center;
    }

    .dropdown-menu .dropdown-item:hover {
        transform: scale(1.05);
    }
</style>
<div class="page-header">
    <h4 class="page-title">
        {{ __('Registered Users') }}
    </h4>
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
            <a href="#">{{ __('Registered Users') }}</a>
        </li>
    </ul>
</div>

{{-- Flash Messages --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ __('Error') }}!</strong> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ __('Success') }}!</strong> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>{{ __('Warning') }}!</strong> {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif


@php
    $hasActiveFilters = collect(Arr::except($userListQuery, ['page']))
        ->filter(fn ($v) => $v !== '' && $v !== null)
        ->isNotEmpty();
@endphp
<div class="row mb-3">
    <div class="col-12">
        <div class="card ru-stats-card">
            <div class="card-header ru-stats-header text-white">
                <h5 class="mb-0">إحصائيات عامة</h5>
            </div>
            <div class="card-body ru-stats-body">

                {{-- Top: global totals, never affected by filters --}}
                <div class="ru-stats-label">
                    كل المستخدمين
                    <span class="badge badge-dark">{{ $tenantsTotal }} مستخدم</span>
                </div>
                <div class="ru-stats-grid">
                    @foreach ($statsTotal as $stat)
                        <div class="ru-stat-tile">
                            <div class="ru-stat-title">{{ $stat['title'] }}</div>
                            <div class="ru-stat-count">{{ $stat['count'] }}</div>
                            <div class="ru-stat-unit">{{ $stat['unit'] }}</div>
                        </div>
                    @endforeach
                </div>

                <hr class="ru-stats-divider">

                {{-- Bottom: recomputed against every active filter --}}
                <div class="ru-stats-label">
                    ضمن الفلترة الحالية
                    <span class="badge badge-primary">{{ $users->total() }} مستخدم</span>
                    @unless ($hasActiveFilters)
                        <span class="text-muted ru-stats-hint">(لا توجد فلاتر مفعّلة — مطابق للإجمالي)</span>
                    @endunless
                </div>
                <div class="ru-stats-grid">
                    @foreach ($statsFiltered as $stat)
                        <div class="ru-stat-tile ru-stat-tile-filtered">
                            <div class="ru-stat-title">{{ $stat['title'] }}</div>
                            <div class="ru-stat-count">{{ $stat['count'] }}</div>
                            <div class="ru-stat-unit">{{ $stat['unit'] }}</div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #000000;
    }

    .register-users-table {
        width: auto !important;
        max-width: 100%;
    }

    .register-users-table thead th,
    .register-users-table thead td {
        text-align: center;
        vertical-align: middle;
    }

    .table.register-users-table thead th,
    .table.register-users-table thead td,
    .table.register-users-table td {
        padding-top: 0.3rem !important;
        padding-bottom: 0.3rem !important;
        padding-left: 0.2rem !important;
        padding-right: 0.2rem !important;
        white-space: nowrap;
        width: 1%;
        vertical-align: middle !important;
        line-height: 1.3;
    }

    .register-users-table .col-phone,
    .register-users-table .col-website {
        font-size: 12px;
    }

    .register-users-table.table .col-website {
        direction: rtl;
        text-align: right !important;
        white-space: normal;
    }

    .register-users-table .col-website a {
        direction: ltr;
        unicode-bidi: isolate;
        display: block;
    }

    .register-users-table .col-website .badge {
        display: inline-block;
        margin-top: 2px;
        padding: 0.15em 0.4em;
        font-size: 10px;
    }

    /* The Actions dropdown is clipped twice over:
       1. `.table-responsive` own overflow (Bootstrap + atlantis.css:6456).
       2. The theme's `.main-panel > .content { overflow: hidden }`
          (atlantis.css:1281), which hard-clips with no scrollbar. This is the
          one that cuts the menu on the last rows, where it would extend past
          the bottom of the content box.
       `overflow-y: visible` fixes neither — per the CSS overflow spec a
       `visible` value computes to `auto` when the other axis is not visible.

       (2) is unconditional: releasing it cannot cause horizontal page overflow,
       because the table is still contained by `.table-responsive`.
       (1) is gated to xl, where the table already fits without horizontal
       scrolling — below that the scroll container is kept.

       This <style> only ships on this page, so `.content` is untouched
       everywhere else in the admin. */
    .main-panel > .content {
        overflow: visible;
    }

    @media (min-width: 1200px) {
        .register-users-table-wrapper.table-responsive {
            overflow: visible;
        }
    }
</style>


<div class="row">
    <div class="col-md-12">

        <div class="card">
            <div class="card-header">
                <div class="ru-toolbar">
                    <div class="ru-toolbar-top">
                        <div class="card-title mb-0">
                            {{ ($showDeleted ?? false) ? __('Deleted Users') : __('Registered Users') }}
                        </div>
                        <div class="ru-toolbar-actions">
                            <button class="btn btn-danger btn-sm d-none bulk-delete" data-href="{{ route('admin.register.user.bulk.delete') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                            @if ($showDeleted ?? false)
                                <a href="{{ route('admin.register.user', Arr::except($userListQuery, ['show_deleted', 'page'])) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-users"></i> {{ __('Show Active Users') }}
                                </a>
                            @else
                                <a href="{{ route('admin.register.user', array_merge(Arr::except($userListQuery, ['page']), ['show_deleted' => '1'])) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-trash-alt"></i> {{ __('Show Deleted Users') }}
                                </a>
                            @endif
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                                <i class="fas fa-plus"></i> {{ __('Add User') }}
                            </button>
                        </div>
                    </div>

                    <div class="ru-toolbar-bottom">
                        <form action="{{ route('admin.register.user') }}" method="GET" class="ru-search-form" id="ruSearchForm">
                            @foreach ($userListQuery as $filterName => $filterValue)
                                @if ($filterName !== 'term')
                                    <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                                @endif
                            @endforeach
                            <div class="ru-search-field {{ request()->filled('term') ? 'is-filled' : '' }}">
                                <input type="text" name="term" id="ruSearchTerm" class="form-control form-control-sm" value="{{ request()->input('term') }}" placeholder="{{ __('Search by name / email / phone number') }}" autocomplete="off">
                                <button type="button" class="ru-search-clear" id="ruSearchClear" title="{{ __('Clear') }}" aria-label="{{ __('Clear') }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i> {{ __('Search') }}
                            </button>
                        </form>
                        <script>
                            (function () {
                                var field = document.querySelector('#ruSearchForm .ru-search-field');
                                var input = document.getElementById('ruSearchTerm');
                                var clearBtn = document.getElementById('ruSearchClear');
                                if (!field || !input || !clearBtn) return;

                                var syncClear = function () {
                                    field.classList.toggle('is-filled', input.value.trim().length > 0);
                                };

                                input.addEventListener('input', syncClear);
                                clearBtn.addEventListener('click', function () {
                                    input.value = '';
                                    syncClear();
                                    // Drop term but keep other filters
                                    var form = document.getElementById('ruSearchForm');
                                    var termInput = form.querySelector('[name="term"]');
                                    if (termInput) termInput.disabled = true;
                                    form.submit();
                                });
                            })();
                        </script>
                        @php
                            $advancedFilterActive = collect([
                                'start_date', 'end_date', 'subscription_start', 'subscription_end',
                                'active_membership', 'paid_member', 'referred_by', 'has_whatsapp',
                            ])->contains(fn ($key) => request()->filled($key));
                        @endphp
                        <button class="btn btn-sm {{ $advancedFilterActive ? 'btn-primary' : 'btn-outline-primary' }}" type="button" data-toggle="modal" data-target="#advancedFiltersModal" id="dateFilterBtn">
                            <i class="fas fa-sliders-h"></i> {{ __('Advanced Filters') }}
                            @if ($advancedFilterActive)
                                <span class="badge badge-light ml-1">!</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <form action="{{ route('admin.register.user') }}" method="GET" class="form-inline mb-2 flex-wrap">
                            @foreach (Arr::except($userListQuery, ['btn_start_date', 'btn_end_date', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <label for="btn_start_date" class="small text-muted mb-0 mr-2">{{ __('Subscription Started From') }}</label>
                            <input type="date" id="btn_start_date" name="btn_start_date" class="form-control form-control-sm mr-3"
                                   value="{{ request('btn_start_date') }}">

                            <label for="btn_end_date" class="small text-muted mb-0 mr-2">{{ __('Subscription Started To') }}</label>
                            <input type="date" id="btn_end_date" name="btn_end_date" class="form-control form-control-sm mr-3"
                                   value="{{ request('btn_end_date') }}">

                            <button type="submit" class="btn btn-sm btn-primary mr-2">
                                <i class="fas fa-filter mr-1"></i> {{ __('Filter') }}
                            </button>
                            @if (request()->filled('btn_start_date') || request()->filled('btn_end_date'))
                                <a href="{{ route('admin.register.user', Arr::except($userListQuery, ['btn_start_date', 'btn_end_date', 'page'])) }}"
                                   class="btn btn-sm btn-outline-secondary">{{ __('Clear Dates') }}</a>
                            @endif
                        </form>
                        <div class="btn-group btn-group-sm flex-wrap mb-3" role="group">
                            @php
                                $showAllQuery = Arr::except($userListQuery, ['package_id', 'paid_member', 'has_whatsapp', 'page']);
                                $whatsappActive = request('has_whatsapp') === '1';
                                $whatsappQuery = Arr::except($userListQuery, ['has_whatsapp', 'page']);
                                if (!$whatsappActive) {
                                    $whatsappQuery['has_whatsapp'] = '1';
                                }
                            @endphp
                            <a href="{{ route('admin.register.user', $showAllQuery) }}"
                               class="btn {{ !request()->filled('package_id') && !$whatsappActive ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ __('Show All') }}
                            </a>
                            @foreach ($packageFilterButtons as $package)
                                @php
                                    $query = Arr::except($userListQuery, ['package_id', 'paid_member', 'page']);
                                    if ((string) request('package_id') !== (string) $package->id) {
                                        $query['package_id'] = $package->id;
                                    }
                                @endphp
                                <a href="{{ route('admin.register.user', $query) }}"
                                   class="btn {{ (string) request('package_id') === (string) $package->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $package->title }}
                                </a>
                            @endforeach
                            <a href="{{ route('admin.register.user', $whatsappQuery) }}"
                               class="btn {{ $whatsappActive ? 'btn-success' : 'btn-outline-success' }}"
                               title="{{ __('WhatsApp users') }}">
                                <i class="fab fa-whatsapp"></i> {{ __('WhatsApp users') }}
                            </a>
                        </div>
                        @if ($users->total() == 0)
                        <h3 class="text-center">{{ __('NO USER FOUND') }}</h3>
                        @else
                        <div class="table-responsive register-users-table-wrapper">
                            <table class="table table-striped table-sm mt-3 register-users-table">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th scope="col">{{ __('Name') }}</th>
                                        <th scope="col">{{ __('Phone') }}</th>
                                        <th scope="col">{{ __('Web site') }}</th>
                                        <th scope="col">{{ __('Subscription') }}</th>
                                        <th scope="col">{{ __('Package') }}</th>
                                        <th scope="col">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $key => $user)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{ $user->id }}">
                                        </td>
                                        <td>
                                            <span class="d-inline-flex align-items-center">
                                                {{ $user->basic_setting?->company_name ?? '—' }}
                                                @if ($user->has_whatsapp_service)
                                                    <i class="fab fa-whatsapp text-success ml-1"
                                                       title="{{ __('WhatsApp service active') }} ({{ $user->active_whatsapp_count }})"
                                                       aria-label="{{ __('WhatsApp service active') }}"></i>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="col-phone">{{ $user->phone }}</td>
                                        <td class="col-website">
                                            <a href="https://{{$user->username}}.taearif.com/ar/" target="_blank">https://{{$user->username}}.taearif.com/ar/</a>
                                            @php $isUnderMaintenance = (bool) ($maintenanceFlags[$user->id] ?? false); @endphp
                                            @if ($isUnderMaintenance)
                                                <span class="badge badge-warning">{{ __('Under Maintenance') }}</span>
                                            @endif
                                        </td>
                                        @php
                                        $currMemb = $user->currentMembership ?? $user->pendingMembership;
                                        $currPackage = $currMemb?->package;

                                        $subState = 'none';
                                        $subDays = null;

                                        if ($currMemb && $currPackage) {
                                            if ((int) $currPackage->id === \App\Services\MembershipService::FREE_PACKAGE_ID) {
                                                $subState = 'expired_free';
                                            } elseif (!is_null($currMemb->status) && (int) $currMemb->status === 0) {
                                                $subState = 'pending';
                                            } elseif ($currPackage->term === 'lifetime') {
                                                $subState = 'lifetime';
                                            } elseif (empty($currMemb->expire_date)) {
                                                $subState = 'expired';
                                            } else {
                                                $expireDate = \Carbon\Carbon::parse($currMemb->expire_date)->startOfDay();

                                                if ($expireDate->isPast()) {
                                                    $subState = 'expired';
                                                } else {
                                                    $subDays = (int) now()->startOfDay()->diffInDays($expireDate, false);
                                                    $subState = in_array($currPackage->term, ['trial', 'monthly', 'yearly'], true)
                                                        ? $currPackage->term
                                                        : 'active';
                                                }
                                            }
                                        }
                                        @endphp
                                        <td>
                                            @switch($subState)
                                                @case('expired_free')
                                                    <span class="badge badge-danger">{{ __('Subscription Expired') }}</span>
                                                    <div class="small text-muted">{{ $currPackage->getDisplayTitle('ar', $currMemb) }}</div>
                                                    @break
                                                @case('expired')
                                                    <span class="badge badge-danger">{{ __('Subscription Expired') }}</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge badge-warning">{{ __('Awaiting Payment') }}</span>
                                                    @break
                                                @case('lifetime')
                                                    <span class="badge badge-primary">{{ __('Lifetime') }}</span>
                                                    @break
                                                @case('trial')
                                                    <span class="badge badge-warning">{{ __('Trial') }} {{ __('Remaining') }} {{ trans_choice('messages.Day', $subDays) }}</span>
                                                    @break
                                                @case('monthly')
                                                    <span class="badge badge-success">{{ __('Monthly') }} {{ __('Remaining') }} {{ trans_choice('messages.Day', $subDays) }}</span>
                                                    @break
                                                @case('yearly')
                                                    <span class="badge badge-success">{{ __('Yearly') }} {{ __('Remaining') }} {{ trans_choice('messages.Day', $subDays) }}</span>
                                                    @break
                                                @case('active')
                                                    <span class="badge badge-success">{{ __('Remaining') }} {{ trans_choice('messages.Day', $subDays) }}</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-secondary">{{ __('Not Subscribed') }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if ($currPackage)
                                            <a target="_blank" href="{{route('admin.package.edit', $currPackage->id)}}">{{ $currPackage->getDisplayTitle('ar', $currMemb) }}</a>
                                            @if (!$currPackage->isTrialPackage())
                                            <span class="badge badge-secondary badge-xs mr-2">{{ __($currPackage->term) }}</span>
                                            @endif

                                            <div class="small text-muted">
                                                @if ($currMemb->start_date)
                                                    ({{ __('Subscription Start Date') }} : {{ \Carbon\Carbon::parse($currMemb->start_date)->format('M-d-Y') }})
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                ({{ __('Subscription Expire Date') }} :
                                                {{ $currPackage->term === 'lifetime'
                                                    ? __('Lifetime')
                                                    : ($currMemb->expire_date ? \Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y') : '—') }})
                                            </div>
                                            @if ($currMemb->status == 0)
                                            <form id="statusForm{{$currMemb->id}}" class="d-inline-block" action="{{route('admin.payment-log.update')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="id" value="{{$currMemb->id}}">
                                                <select class="form-control form-control-sm bg-warning" name="status" onchange="document.getElementById('statusForm{{$currMemb->id}}').submit();">
                                                    <option value=0 selected>{{ __('Pending') }}</option>
                                                    <option value=1>{{ __('Success') }}</option>
                                                    <option value=2>{{ __('Rejected') }}</option>
                                                </select>
                                            </form>
                                            @endif

                                            @else
                                            <a data-target="#addCurrentPackage-{{ $user->id }}" data-toggle="modal" class="btn btn-xs btn-primary text-white"><i class="fas fa-plus"></i> {{ __('Add Package') }}</a>
                                            @endif

                                        </td>

                                        <td class="actions-cell">
                                            @if ($currPackage)
                                            <form id="remove-package-form-{{ $user->id }}" action="{{ route('admin.user.currPackage.remove') }}" class="deleteform d-none" method="POST">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="deletebtn"></button>
                                            </form>
                                            @endif
                                            <form id="delete-user-form-{{ $user->id }}" class="deleteform d-none" action="{{ route('admin.register.user.delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="deletebtn"></button>
                                            </form>
                                            <form id="force-delete-user-form-{{ $user->id }}" class="deleteform d-none" action="{{ route('admin.register.user.force-delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="deletebtn"></button>
                                            </form>
                                            @if ($showDeleted ?? false)
                                            <form id="restore-user-form-{{ $user->id }}" class="d-none" action="{{ route('admin.register.user.restore') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            </form>
                                            @endif
                                            <form id="maintenance-form-{{ $user->id }}" class="d-none" action="{{ route('admin.register.user.maintenance') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            </form>
                                            <div class="dropdown">
                                                <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton-{{ $user->id }}" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                                                    {{ __('Actions') }}
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton-{{ $user->id }}">
                                                    @if ($showDeleted ?? false)
                                                    <a href="#" class="dropdown-item text-success" onclick="event.preventDefault(); document.getElementById('restore-user-form-{{ $user->id }}').submit();">
                                                        {{ __('Restore') }}
                                                    </a>
                                                    <a href="#" class="dropdown-item text-danger" onclick="event.preventDefault(); if(confirm(@json(__('Permanently delete this user? This cannot be undone.')))) { document.getElementById('force-delete-user-form-{{ $user->id }}').querySelector('.deletebtn').click(); }">
                                                        {{ __('Delete Permanently') }}
                                                    </a>
                                                    @else
                                                    <a href="{{ route('admin.register.user.secretLogin', $user) }}" target="_blank" class="dropdown-item">
                                                        {{ __('Secret Login') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('admin.register.user.view', $user->id) }}">{{ __('Details') }}</a>
                                                    <a class="dropdown-item" href="{{ route('admin.register.user.changePass', $user->id) }}">{{ __('Change Password') }}</a>
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('maintenance-form-{{ $user->id }}').submit();">
                                                        {{ $isUnderMaintenance ? __('Disable Maintenance Mode') : __('Enable Maintenance Mode') }}
                                                    </a>
                                                    @if ($currPackage)
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editCurrentPackage-{{ $user->id }}">{{ __('Change Current Package') }}</a>
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('remove-package-form-{{ $user->id }}').querySelector('.deletebtn').click();">
                                                        {{ __('Remove Package') }}
                                                    </a>
                                                    @endif
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('delete-user-form-{{ $user->id }}').querySelector('.deletebtn').click();">
                                                        {{ __('Delete') }}
                                                    </a>
                                                    <a href="#" class="dropdown-item text-danger" onclick="event.preventDefault(); if(confirm(@json(__('Permanently delete this user? This cannot be undone.')))) { document.getElementById('force-delete-user-form-{{ $user->id }}').querySelector('.deletebtn').click(); }">
                                                        {{ __('Delete Permanently') }}
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @foreach ($users as $user)
                            @php
                                $currMemb = $user->currentMembership ?? $user->pendingMembership;
                                $currPackage = $currMemb?->package;
                            @endphp
                            {{-- Only the two modals this page can actually open. The
                                 template / next-package modals are triggered from
                                 vcards.blade.php and details.blade.php respectively, and
                                 the next-package pair emits a hardcoded id per row. --}}
                            @includeIf('admin.register_user.edit-current-package')
                            @includeIf('admin.register_user.add-current-package')
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">


                        {{ $users->appends($userListQuery)->links() }}

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Filters Modal -->
<div class="modal fade" id="advancedFiltersModal" tabindex="-1" role="dialog" aria-labelledby="advancedFiltersModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.register.user') }}" method="GET">
                <div class="modal-header">
                    <h5 class="modal-title" id="advancedFiltersModalTitle">
                        <i class="fas fa-sliders-h"></i> {{ __('Advanced Filters') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (array_key_exists('term', $userListQuery))
                        <input type="hidden" name="term" value="{{ $userListQuery['term'] }}">
                    @endif
                    @if (array_key_exists('package_id', $userListQuery))
                        <input type="hidden" name="package_id" value="{{ $userListQuery['package_id'] }}">
                    @endif
                    @if (array_key_exists('btn_start_date', $userListQuery))
                        <input type="hidden" name="btn_start_date" value="{{ $userListQuery['btn_start_date'] }}">
                    @endif
                    @if (array_key_exists('btn_end_date', $userListQuery))
                        <input type="hidden" name="btn_end_date" value="{{ $userListQuery['btn_end_date'] }}">
                    @endif
                    @if (array_key_exists('show_deleted', $userListQuery))
                        <input type="hidden" name="show_deleted" value="{{ $userListQuery['show_deleted'] }}">
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date" class="small text-muted mb-1">{{ __('From Date') }} ({{ __('optional') }})</label>
                                <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="{{ request()->input('start_date') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date" class="small text-muted mb-1">{{ __('To Date') }} ({{ __('optional') }})</label>
                                <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="{{ request()->input('end_date') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subscription_start" class="small text-muted mb-1">{{ __('Subscription Ends From') }} ({{ __('optional') }})</label>
                                <input type="date" id="subscription_start" name="subscription_start" class="form-control form-control-sm" value="{{ request()->input('subscription_start') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subscription_end" class="small text-muted mb-1">{{ __('Subscription Ends To') }} ({{ __('optional') }})</label>
                                <input type="date" id="subscription_end" name="subscription_end" class="form-control form-control-sm" value="{{ request()->input('subscription_end') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="active_membership" class="small text-muted mb-1">{{ __('Active Subscription') }}</label>
                                <select name="active_membership" id="active_membership" class="form-control form-control-sm">
                                    <option value="">{{ __('-- All Users --') }}</option>
                                    <option value="1" {{ request()->input('active_membership') == '1' ? 'selected' : '' }}>
                                        {{ __('Only Active Subscribers') }}
                                    </option>
                                    <option value="0" {{ request()->input('active_membership') == '0' ? 'selected' : '' }}>
                                        {{ __('Only Non-Active / Expired') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paid_member" class="small text-muted mb-1">{{ __('Membership_Type') }}</label>
                                <select name="paid_member" id="paid_member" class="form-control form-control-sm">
                                    <option value="">{{ __('-- All Types --') }}</option>
                                    <option value="paid" {{ request()->input('paid_member') == 'paid'  ? 'selected' : '' }}>
                                        {{ __('Paid_Member') }}
                                    </option>
                                    <option value="trial" {{ request()->input('paid_member') == 'trial' ? 'selected' : '' }}>
                                        {{ __('Free_Trial') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="referred_by" class="small text-muted mb-1">{{ __('Referred By') }}</label>
                                <select name="referred_by" id="referred_by" class="form-control form-control-sm">
                                    <option value="">{{ __('-- All Referrers --') }}</option>
                                    @foreach($affiliateUsers as $affUser)
                                    <option value="{{ $affUser->id }}" {{ request()->input('referred_by') == $affUser->id ? 'selected' : '' }}>
                                        {{ $affUser->username }} ({{ $affUser->email }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="has_whatsapp" class="small text-muted mb-1">
                                    <i class="fab fa-whatsapp text-success"></i> {{ __('WhatsApp') }}
                                </label>
                                <select name="has_whatsapp" id="has_whatsapp" class="form-control form-control-sm">
                                    <option value="">{{ __('-- All Users --') }}</option>
                                    <option value="1" {{ request()->input('has_whatsapp') == '1' ? 'selected' : '' }}>
                                        {{ __('WhatsApp users') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.register.user') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> {{ __('Reset') }}
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add User') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.register.user.store') }}" method="POST" id="ajaxForm">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Username') }} *</label>
                        <input class="form-control" type="text" name="username">
                        <p id="errusername" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Email') }} *</label>
                        <input class="form-control" type="email" name="email">
                        <p id="erremail" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Password') }} *</label>
                        <input class="form-control" type="password" name="password">
                        <p id="errpassword" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Confirm Password') }} *</label>
                        <input class="form-control" type="password" name="password_confirmation">
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Package / Plan') }} *</label>
                        <select name="package_id" class="form-control">
                            @if (!empty($packages))
                            @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->getDisplayTitle('ar') }}@unless($package->isTrialPackage()) ({{ __($package->term) }})@endunless</option>
                            @endforeach
                            @endif
                        </select>
                        <p id="errpackage_id" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Payment Gateway') }} *</label>
                        <select name="payment_gateway" class="form-control">
                            @if (!empty($gateways))
                            @foreach ($gateways as $gateway)
                            <option value="{{ $gateway->name }}">{{ $gateway->name }}</option>
                            @endforeach
                            @endif
                        </select>
                        <p id="errpayment_gateway" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Publicly Hidden') }} *</label>
                        <select name="online_status" class="form-control">
                            <option value="1">{{ __('No') }}</option>
                            <option value="0">{{ __('Yes') }}</option>
                        </select>
                        <p id="erronline_status" class="text-danger mb-0 em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer text-center">
                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Add User') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Open the Actions menu upward when it would not fit below.
    //
    // Popper will not do this for us: with data-boundary="viewport" it only
    // flips when the menu would leave the *viewport*, and on the lower rows
    // there is still viewport room — the menu is cut by an ancestor's overflow
    // long before that. So decide the direction against the card box instead.
    //
    // Bootstrap 4 reads the .dropup class off the toggle's parent inside
    // Dropdown._getPlacement(), which runs after show.bs.dropdown fires, so
    // setting it here is picked up for the very same open.
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery === 'undefined') {
            return;
        }

        jQuery(document).on('show.bs.dropdown', '.register-users-table .dropdown', function () {
            var $dropdown = jQuery(this);
            var $menu = $dropdown.find('.dropdown-menu');
            var $card = $dropdown.closest('.card');

            if (!$menu.length || !$card.length) {
                return;
            }

            var toggleBottom = $dropdown[0].getBoundingClientRect().bottom;
            var limit = Math.min($card[0].getBoundingClientRect().bottom, window.innerHeight);

            $dropdown.toggleClass('dropup', (toggleBottom + $menu.outerHeight()) > limit);
        });
    });
</script>
@endsection
