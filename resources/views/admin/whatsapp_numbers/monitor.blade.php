@extends('admin.layout')

@section('content')
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
    $currentSort = $filters['sort'] ?? 'id';
    $currentOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $sortParams = array_filter([
        'status' => $filters['status'] ?? null,
        'health' => $filters['health'] ?? null,
        'sync' => $filters['sync'] ?? null,
        'q' => $filters['q'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $sortLink = function (string $column) use ($sortParams, $currentSort, $currentOrder) {
        $params = $sortParams;
        $params['sort'] = $column;
        $params['order'] = ($currentSort === $column && $currentOrder === 'desc') ? 'asc' : 'desc';

        return route('admin.whatsapp-numbers.monitor', $params);
    };
    $sortIndicator = function (string $column) use ($currentSort, $currentOrder) {
        if ($currentSort !== $column) {
            return '';
        }

        return $currentOrder === 'asc' ? ' ↑' : ' ↓';
    };
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

<div class="row mb-3">
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round d-block text-decoration-none" href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'working']) }}">
            <div class="card-body py-3">
                <p class="card-category text-muted mb-1">{{ __('Working') }}</p>
                <h4 class="card-title font-weight-bold mb-0 text-success">{{ number_format($counts['working'] ?? 0) }}</h4>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round d-block text-decoration-none" href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'no_recent_inbound']) }}">
            <div class="card-body py-3">
                <p class="card-category text-muted mb-1">{{ __('No recent inbound') }}</p>
                <h4 class="card-title font-weight-bold mb-0 text-warning">{{ number_format($counts['no_recent_inbound'] ?? 0) }}</h4>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round d-block text-decoration-none" href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'no_inbound_ever']) }}">
            <div class="card-body py-3">
                <p class="card-category text-muted mb-1">{{ __('No inbound ever') }}</p>
                <h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['no_inbound_ever'] ?? 0) }}</h4>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round d-block text-decoration-none" href="{{ route('admin.whatsapp-numbers.monitor', ['health' => 'not_linked']) }}">
            <div class="card-body py-3">
                <p class="card-category text-muted mb-1">{{ __('Not linked') }}</p>
                <h4 class="card-title font-weight-bold mb-0 text-muted">{{ number_format($counts['not_linked'] ?? 0) }}</h4>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-md-4 col-lg">
        <a class="card card-stats card-round d-block text-decoration-none border-danger" href="{{ route('admin.whatsapp-numbers.monitor', ['sync' => 'owner_mismatch']) }}">
            <div class="card-body py-3">
                <p class="card-category text-danger mb-1">{{ __('Owner mismatch') }}</p>
                <h4 class="card-title font-weight-bold mb-0 text-danger">{{ number_format($counts['owner_mismatch'] ?? 0) }}</h4>
            </div>
        </a>
    </div>
</div>

@if (!empty($summary['generated_at']))
    <p class="text-muted small mb-3">
        {{ __('Counts cached, generated :time', ['time' => $summary['generated_at']->format('Y-m-d H:i')]) }}
    </p>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Numbers list') }}</h5>
                <form class="form-inline float-right flex-wrap" method="GET" action="{{ route('admin.whatsapp-numbers.monitor') }}">
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2 mb-0">{{ __('Link status') }}</label>
                        <select name="status" class="form-control">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option }}" {{ ($filters['status'] ?? '') === $option ? 'selected' : '' }}>
                                    {{ $statusLabels[$option] ?? $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2 mb-0" title="{{ __('Recent means inbound within the last :hours hours', ['hours' => $staleHours]) }}">
                            {{ __('Health') }}
                            <small class="text-muted">({{ __('recent = last :hours h', ['hours' => $staleHours]) }})</small>
                        </label>
                        <select name="health" class="form-control" title="{{ __('Recent means inbound within the last :hours hours', ['hours' => $staleHours]) }}">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($healthOptions as $option)
                                <option value="{{ $option }}" {{ ($filters['health'] ?? '') === $option ? 'selected' : '' }}>
                                    {{ $healthLabels[$option] ?? $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2 mb-0">{{ __('Sync') }}</label>
                        <select name="sync" class="form-control">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($syncOptions as $option)
                                <option value="{{ $option }}" {{ ($filters['sync'] ?? '') === $option ? 'selected' : '' }}>
                                    {{ $syncLabels[$option] ?? $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2">
                        <input type="text" name="q" class="form-control" placeholder="{{ __('Number, phone ID, ID, username or email') }}" value="{{ $filters['q'] ?? '' }}">
                    </div>
                    @if (!empty($filters['sort']))
                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                    @endif
                    @if (!empty($filters['order']))
                        <input type="hidden" name="order" value="{{ $filters['order'] }}">
                    @endif
                    <button type="submit" class="btn btn-primary mb-2 mr-2">{{ __('Filter') }}</button>
                    <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="btn btn-secondary mb-2">{{ __('Reset') }}</a>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Link status') }}</th>
                                <th>{{ __('Health') }}</th>
                                <th>{{ __('Sync') }}</th>
                                <th>
                                    <a href="{{ $sortLink('last_inbound_at') }}">{{ __('Last inbound') }}{{ $sortIndicator('last_inbound_at') }}</a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('last_outbound_at') }}">{{ __('Last outbound') }}{{ $sortIndicator('last_outbound_at') }}</a>
                                </th>
                                <th>{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($numbers as $row)
                                <tr>
                                    <td>{{ ($numbers->currentPage() - 1) * $numbers->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div>{{ $row->number ?? '—' }}</div>
                                        @if (!empty($row->name))
                                            <small class="text-muted">{{ $row->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($row->username))
                                            <div>{{ $row->username }}</div>
                                            @if (!empty($row->email))
                                                <small class="text-muted">{{ $row->email }}</small>
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
                                            <small class="text-muted">{{ $row->request_status }}</small>
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
                                                    <br>
                                                    <small>{{ __('Routed to tenant #:id', ['id' => $row->wa_number_user_id]) }}</small>
                                                @endif
                                                @break
                                            @case('n/a')
                                                —
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if (!empty($row->last_inbound_at))
                                            {{ \Illuminate\Support\Carbon::parse($row->last_inbound_at)->format('Y-m-d H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($row->last_outbound_at))
                                            {{ \Illuminate\Support\Carbon::parse($row->last_outbound_at)->format('Y-m-d H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-numbers.monitor.show', $row->id) }}">{{ __('Details') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">{{ __('No WhatsApp numbers found.') }}</td>
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
