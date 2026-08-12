@extends('admin.layout')

@section('content')
<style>
/* WhatsApp Numbers Monitor — scoped styles */
.wa-monitor-intro {
    margin-bottom: 1.25rem;
}

.wa-monitor-intro .wa-monitor-subtitle {
    color: #6c757d;
    font-size: 0.9375rem;
    margin-bottom: 0.25rem;
}

.wa-monitor-intro .wa-monitor-meta {
    color: #adb5bd;
    font-size: 0.8125rem;
}

.wa-monitor-stat {
    display: block;
    text-decoration: none !important;
    color: inherit;
    border-left: 4px solid transparent;
    border-radius: 0.25rem;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    margin-bottom: 1rem;
}

.wa-monitor-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    text-decoration: none;
    color: inherit;
}

.wa-monitor-stat:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

.wa-monitor-stat.is-active {
    box-shadow: 0 0 0 2px #007bff;
}

.wa-monitor-stat--working { border-left-color: #28a745; }
.wa-monitor-stat--no_recent_inbound { border-left-color: #ffc107; }
.wa-monitor-stat--no_inbound_ever { border-left-color: #fd7e14; }
.wa-monitor-stat--not_linked { border-left-color: #6c757d; }
.wa-monitor-stat--owner_mismatch { border-left-color: #dc3545; }

.wa-monitor-stat .card-body {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem 1.125rem;
}

.wa-monitor-stat__icon {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.125rem;
}

.wa-monitor-stat__icon--success { background: rgba(40, 167, 69, 0.12); color: #28a745; }
.wa-monitor-stat__icon--warning { background: rgba(255, 193, 7, 0.15); color: #d39e00; }
.wa-monitor-stat__icon--orange { background: rgba(253, 126, 20, 0.12); color: #fd7e14; }
.wa-monitor-stat__icon--muted { background: rgba(108, 117, 125, 0.12); color: #6c757d; }
.wa-monitor-stat__icon--danger { background: rgba(220, 53, 69, 0.12); color: #dc3545; }

.wa-monitor-stat__label {
    font-size: 0.8125rem;
    color: #6c757d;
    margin-bottom: 0.125rem;
    line-height: 1.3;
}

.wa-monitor-stat__value {
    font-size: 1.375rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.wa-monitor-filters .form-group label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.375rem;
}

.wa-monitor-filters .form-control {
    min-height: 40px;
}

.wa-monitor-filters .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.wa-monitor-search-wrap {
    position: relative;
}

.wa-monitor-search-wrap .form-control {
    padding-left: 2.25rem;
}

.wa-monitor-search-wrap__icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    pointer-events: none;
    z-index: 1;
}

.wa-monitor-filters__actions {
    display: flex;
    justify-content: flex-end;
    align-items: flex-end;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.wa-monitor-filters__actions .btn {
    min-height: 40px;
    min-width: 5rem;
}

.wa-monitor-active-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.wa-monitor-active-filters__label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    margin-right: 0.25rem;
}

.wa-monitor-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    font-size: 0.8125rem;
    background: #e9ecef;
    border-radius: 999px;
    color: #495057;
    text-decoration: none !important;
    min-height: 32px;
    transition: background 0.15s ease;
}

.wa-monitor-pill:hover {
    background: #dee2e6;
    color: #212529;
    text-decoration: none;
}

.wa-monitor-pill:focus {
    outline: 2px solid #007bff;
    outline-offset: 1px;
}

.wa-monitor-pill__remove {
    opacity: 0.6;
    font-size: 0.75rem;
}

.wa-monitor-pill:hover .wa-monitor-pill__remove {
    opacity: 1;
}

.wa-monitor-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    padding: 0.75rem 1rem;
}

.wa-monitor-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.wa-monitor-sort {
    color: #495057;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.wa-monitor-sort:hover {
    color: #007bff;
    text-decoration: none;
}

.wa-monitor-sort:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
    border-radius: 2px;
}

.wa-monitor-sort.is-active {
    color: #007bff;
    font-weight: 600;
}

.wa-monitor-phone {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.9375rem;
    font-weight: 500;
    letter-spacing: 0.02em;
}

.wa-monitor-date {
    white-space: nowrap;
}

.wa-monitor-date__relative {
    display: block;
    font-size: 0.75rem;
    color: #adb5bd;
    margin-top: 0.125rem;
}

.wa-monitor-tenant-name {
    font-weight: 600;
}

.wa-monitor-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #6c757d;
}

.wa-monitor-empty__icon {
    font-size: 2.5rem;
    color: #25d366;
    opacity: 0.5;
    margin-bottom: 1rem;
}

.wa-monitor-empty__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.375rem;
}

.wa-monitor-list-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (max-width: 767.98px) {
    .wa-monitor-filters__actions {
        justify-content: stretch;
    }

    .wa-monitor-filters__actions .btn {
        flex: 1 1 auto;
    }
}
</style>

@php
    $statusLabels = [
        'active' => __('Active'),
        'inactive' => __('Inactive'),
        'blocked' => __('Blocked'),
        'not_linked' => __('Not linked'),
    ];
    $healthLabels = [
        'working' => __('Working'),
        'no_recent_inbound' => __('No recent inbound'),
        'no_inbound_ever' => __('No inbound ever'),
        'not_linked' => __('Not linked'),
    ];
    $syncLabels = [
        'synced' => __('Synced'),
        'missing' => __('Missing'),
        'owner_mismatch' => __('Owner mismatch'),
        'n/a' => __('N/A'),
    ];
    $counts = $summary['counts'] ?? [];
    $totalNumbers = array_sum(array_map('intval', $counts));
    $currentSort = $filters['sort'] ?? 'id';
    $currentOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $currentHealth = $filters['health'] ?? '';
    $currentSync = $filters['sync'] ?? '';
    $currentStatus = $filters['status'] ?? '';
    $currentQuery = $filters['q'] ?? '';
    $sortParams = array_filter([
        'status' => $currentStatus ?: null,
        'health' => $currentHealth ?: null,
        'sync' => $currentSync ?: null,
        'q' => $currentQuery ?: null,
    ], fn ($value) => $value !== null && $value !== '');
    $sortLink = function (string $column) use ($sortParams, $currentSort, $currentOrder) {
        $params = $sortParams;
        $params['sort'] = $column;
        $params['order'] = ($currentSort === $column && $currentOrder === 'desc') ? 'asc' : 'desc';

        return route('admin.whatsapp-numbers.monitor', $params);
    };
    $sortIcon = function (string $column) use ($currentSort, $currentOrder) {
        if ($currentSort !== $column) {
            return 'fa-sort text-muted';
        }

        return $currentOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    };
    $isSortActive = function (string $column) use ($currentSort) {
        return $currentSort === $column ? 'is-active' : '';
    };
    $filterLinkWithout = function (string $excludeKey) use ($filters) {
        $params = array_filter([
            'status' => ($excludeKey !== 'status') ? ($filters['status'] ?? null) : null,
            'health' => ($excludeKey !== 'health') ? ($filters['health'] ?? null) : null,
            'sync' => ($excludeKey !== 'sync') ? ($filters['sync'] ?? null) : null,
            'q' => ($excludeKey !== 'q') ? ($filters['q'] ?? null) : null,
            'sort' => $filters['sort'] ?? null,
            'order' => $filters['order'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return route('admin.whatsapp-numbers.monitor', $params);
    };
    $hasActiveFilters = $currentStatus !== '' || $currentHealth !== '' || $currentSync !== '' || $currentQuery !== '';
@endphp

<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Numbers Monitor') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Credit Management') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('WhatsApp Numbers Monitor') }}</a></li>
    </ul>
</div>

<div class="wa-monitor-intro">
    <p class="wa-monitor-subtitle mb-1">
        {{ __('Monitor all WhatsApp numbers health and sync status across tenants.') }}
        @if ($totalNumbers > 0)
            <span class="text-dark font-weight-bold">{{ number_format($totalNumbers) }} {{ __('numbers') }}</span>
        @endif
    </p>
    <p class="wa-monitor-meta mb-0">
        {{ __('Recent inbound threshold: :hours hours', ['hours' => $staleHours]) }}
        @if (!empty($summary['generated_at']))
            · {{ __('Counts cached, generated :time', ['time' => $summary['generated_at']->format('Y-m-d H:i')]) }}
        @endif
    </p>
</div>

<div class="row">
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round wa-monitor-stat wa-monitor-stat--working {{ $currentHealth === 'working' ? 'is-active' : '' }}"
           href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'working']) }}">
            <div class="card-body">
                <span class="wa-monitor-stat__icon wa-monitor-stat__icon--success" aria-hidden="true">
                    <i class="fas fa-check-circle"></i>
                </span>
                <div>
                    <p class="wa-monitor-stat__label mb-0">{{ __('Working') }}</p>
                    <p class="wa-monitor-stat__value text-success mb-0">{{ number_format($counts['working'] ?? 0) }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round wa-monitor-stat wa-monitor-stat--no_recent_inbound {{ $currentHealth === 'no_recent_inbound' ? 'is-active' : '' }}"
           href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'no_recent_inbound']) }}">
            <div class="card-body">
                <span class="wa-monitor-stat__icon wa-monitor-stat__icon--warning" aria-hidden="true">
                    <i class="fas fa-clock"></i>
                </span>
                <div>
                    <p class="wa-monitor-stat__label mb-0">{{ __('No recent inbound') }}</p>
                    <p class="wa-monitor-stat__value text-warning mb-0">{{ number_format($counts['no_recent_inbound'] ?? 0) }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round wa-monitor-stat wa-monitor-stat--no_inbound_ever {{ $currentHealth === 'no_inbound_ever' ? 'is-active' : '' }}"
           href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'no_inbound_ever']) }}">
            <div class="card-body">
                <span class="wa-monitor-stat__icon wa-monitor-stat__icon--orange" aria-hidden="true">
                    <i class="fas fa-inbox"></i>
                </span>
                <div>
                    <p class="wa-monitor-stat__label mb-0">{{ __('No inbound ever') }}</p>
                    <p class="wa-monitor-stat__value mb-0">{{ number_format($counts['no_inbound_ever'] ?? 0) }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round wa-monitor-stat wa-monitor-stat--not_linked {{ $currentHealth === 'not_linked' ? 'is-active' : '' }}"
           href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'not_linked']) }}">
            <div class="card-body">
                <span class="wa-monitor-stat__icon wa-monitor-stat__icon--muted" aria-hidden="true">
                    <i class="fas fa-unlink"></i>
                </span>
                <div>
                    <p class="wa-monitor-stat__label mb-0">{{ __('Not linked') }}</p>
                    <p class="wa-monitor-stat__value text-muted mb-0">{{ number_format($counts['not_linked'] ?? 0) }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round wa-monitor-stat wa-monitor-stat--owner_mismatch {{ $currentSync === 'owner_mismatch' ? 'is-active' : '' }}"
           href="{{ route('admin.whatsapp-numbers.monitor', ['sync' => 'owner_mismatch']) }}">
            <div class="card-body">
                <span class="wa-monitor-stat__icon wa-monitor-stat__icon--danger" aria-hidden="true">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <div>
                    <p class="wa-monitor-stat__label mb-0 text-danger">{{ __('Owner mismatch') }}</p>
                    <p class="wa-monitor-stat__value text-danger mb-0">{{ number_format($counts['owner_mismatch'] ?? 0) }}</p>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card wa-monitor-filters mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.whatsapp-numbers.monitor') }}">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-4 form-group">
                            <label for="wa-monitor-q">{{ __('Search') }}</label>
                            <div class="wa-monitor-search-wrap">
                                <span class="wa-monitor-search-wrap__icon" aria-hidden="true">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search"
                                       id="wa-monitor-q"
                                       name="q"
                                       class="form-control"
                                       placeholder="{{ __('Number, phone ID, ID, username or email') }}"
                                       value="{{ $currentQuery }}">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2 form-group">
                            <label for="wa-monitor-status">{{ __('Link status') }}</label>
                            <select id="wa-monitor-status" name="status" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option }}" {{ $currentStatus === $option ? 'selected' : '' }}>
                                        {{ $statusLabels[$option] ?? $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3 form-group">
                            <label for="wa-monitor-health" title="{{ __('Recent means inbound within the last :hours hours', ['hours' => $staleHours]) }}">
                                {{ __('Health') }}
                                <small class="text-muted font-weight-normal">({{ __('recent = last :hours h', ['hours' => $staleHours]) }})</small>
                            </label>
                            <select id="wa-monitor-health"
                                    name="health"
                                    class="form-control"
                                    title="{{ __('Recent means inbound within the last :hours hours', ['hours' => $staleHours]) }}">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($healthOptions as $option)
                                    <option value="{{ $option }}" {{ $currentHealth === $option ? 'selected' : '' }}>
                                        {{ $healthLabels[$option] ?? $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3 form-group">
                            <label for="wa-monitor-sync">{{ __('Sync') }}</label>
                            <select id="wa-monitor-sync" name="sync" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($syncOptions as $option)
                                    <option value="{{ $option }}" {{ $currentSync === $option ? 'selected' : '' }}>
                                        {{ $syncLabels[$option] ?? $option }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="wa-monitor-filters__actions">
                                @if (!empty($filters['sort']))
                                    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                                @endif
                                @if (!empty($filters['order']))
                                    <input type="hidden" name="order" value="{{ $filters['order'] }}">
                                @endif
                                <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasActiveFilters)
            <div class="wa-monitor-active-filters" role="region" aria-label="{{ __('Active filters') }}">
                <span class="wa-monitor-active-filters__label">{{ __('Active filters') }}:</span>
                @if ($currentQuery !== '')
                    <a href="{{ $filterLinkWithout('q') }}" class="wa-monitor-pill" title="{{ __('Remove filter') }}">
                        {{ __('Search') }}: {{ Str::limit($currentQuery, 30) }}
                        <i class="fas fa-times wa-monitor-pill__remove" aria-hidden="true"></i>
                    </a>
                @endif
                @if ($currentStatus !== '')
                    <a href="{{ $filterLinkWithout('status') }}" class="wa-monitor-pill" title="{{ __('Remove filter') }}">
                        {{ __('Link status') }}: {{ $statusLabels[$currentStatus] ?? $currentStatus }}
                        <i class="fas fa-times wa-monitor-pill__remove" aria-hidden="true"></i>
                    </a>
                @endif
                @if ($currentHealth !== '')
                    <a href="{{ $filterLinkWithout('health') }}" class="wa-monitor-pill" title="{{ __('Remove filter') }}">
                        {{ __('Health') }}: {{ $healthLabels[$currentHealth] ?? $currentHealth }}
                        <i class="fas fa-times wa-monitor-pill__remove" aria-hidden="true"></i>
                    </a>
                @endif
                @if ($currentSync !== '')
                    <a href="{{ $filterLinkWithout('sync') }}" class="wa-monitor-pill" title="{{ __('Remove filter') }}">
                        {{ __('Sync') }}: {{ $syncLabels[$currentSync] ?? $currentSync }}
                        <i class="fas fa-times wa-monitor-pill__remove" aria-hidden="true"></i>
                    </a>
                @endif
                <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="wa-monitor-pill" title="{{ __('Clear all filters') }}">
                    {{ __('Clear all') }}
                    <i class="fas fa-times wa-monitor-pill__remove" aria-hidden="true"></i>
                </a>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="wa-monitor-list-header">
                    <h5 class="card-title mb-0">{{ __('Numbers list') }}</h5>
                    <span class="badge badge-primary">{{ number_format($numbers->total()) }} {{ __('results') }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 wa-monitor-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('Number') }}</th>
                                <th scope="col">{{ __('Tenant') }}</th>
                                <th scope="col">{{ __('Link status') }}</th>
                                <th scope="col">{{ __('Health') }}</th>
                                <th scope="col">{{ __('Sync') }}</th>
                                <th scope="col">
                                    <a href="{{ $sortLink('last_inbound_at') }}"
                                       class="wa-monitor-sort {{ $isSortActive('last_inbound_at') }}">
                                        {{ __('Last inbound') }}
                                        <i class="fas {{ $sortIcon('last_inbound_at') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a href="{{ $sortLink('last_outbound_at') }}"
                                       class="wa-monitor-sort {{ $isSortActive('last_outbound_at') }}">
                                        {{ __('Last outbound') }}
                                        <i class="fas {{ $sortIcon('last_outbound_at') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($numbers as $row)
                                <tr>
                                    <td>{{ ($numbers->currentPage() - 1) * $numbers->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="wa-monitor-phone">{{ $row->number ?? '—' }}</div>
                                        @if (!empty($row->name))
                                            <small class="text-muted d-block">{{ $row->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($row->username))
                                            <div class="wa-monitor-tenant-name">{{ $row->username }}</div>
                                            @if (!empty($row->email))
                                                <small class="text-muted d-block">{{ $row->email }}</small>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @php $statusLabel = $statusLabels[$row->status] ?? $row->status; @endphp
                                        @if ($row->status === 'active')
                                            <span class="badge badge-success">{{ $statusLabel }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $statusLabel }}</span>
                                        @endif
                                        @if (!empty($row->request_status))
                                            <small class="text-muted d-block">{{ $row->request_status }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php $healthLabel = $healthLabels[$row->health] ?? $row->health; @endphp
                                        @switch($row->health)
                                            @case('working')
                                                <span class="badge badge-success">{{ $healthLabel }}</span>
                                                @break
                                            @case('no_recent_inbound')
                                                <span class="badge badge-warning">{{ $healthLabel }}</span>
                                                @break
                                            @case('no_inbound_ever')
                                                <span class="badge badge-warning">{{ $healthLabel }}</span>
                                                @break
                                            @case('not_linked')
                                                <span class="badge badge-secondary">{{ $healthLabel }}</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $healthLabel }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @php $syncLabel = $syncLabels[$row->sync] ?? $row->sync; @endphp
                                        @switch($row->sync)
                                            @case('synced')
                                                <span class="badge badge-success">{{ $syncLabel }}</span>
                                                @break
                                            @case('missing')
                                                <span class="badge badge-warning">{{ $syncLabel }}</span>
                                                @break
                                            @case('owner_mismatch')
                                                <span class="badge badge-danger">{{ $syncLabel }}</span>
                                                @if (!empty($row->wa_number_user_id))
                                                    <small class="d-block">{{ __('Routed to tenant #:id', ['id' => $row->wa_number_user_id]) }}</small>
                                                @endif
                                                @break
                                            @case('n/a')
                                                —
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                        @endswitch
                                    </td>
                                    <td class="wa-monitor-date">
                                        @if (!empty($row->last_inbound_at))
                                            @php $inboundAt = \Illuminate\Support\Carbon::parse($row->last_inbound_at); @endphp
                                            <span>{{ $inboundAt->format('Y-m-d H:i') }}</span>
                                            <span class="wa-monitor-date__relative">{{ $inboundAt->diffForHumans() }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="wa-monitor-date">
                                        @if (!empty($row->last_outbound_at))
                                            @php $outboundAt = \Illuminate\Support\Carbon::parse($row->last_outbound_at); @endphp
                                            <span>{{ $outboundAt->format('Y-m-d H:i') }}</span>
                                            <span class="wa-monitor-date__relative">{{ $outboundAt->diffForHumans() }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-numbers.monitor.show', $row->id) }}"
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            <span class="sr-only">{{ __('Details') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="wa-monitor-empty">
                                            <div class="wa-monitor-empty__icon" aria-hidden="true">
                                                <i class="fab fa-whatsapp"></i>
                                            </div>
                                            <p class="wa-monitor-empty__title">{{ __('No WhatsApp numbers found.') }}</p>
                                            @if ($hasActiveFilters)
                                                <p class="mb-3">{{ __('Try adjusting your filters or search terms.') }}</p>
                                                <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="btn btn-secondary btn-sm">
                                                    {{ __('Clear all filters') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($numbers->hasPages())
                <div class="card-footer">
                    {{ $numbers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
