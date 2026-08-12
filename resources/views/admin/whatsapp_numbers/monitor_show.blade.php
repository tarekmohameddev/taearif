@extends('admin.layout')

@section('content')
<style>
/* WhatsApp Numbers Monitor — detail page (shared class names with list) */
.wa-monitor-phone {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.9375rem;
    font-weight: 500;
    letter-spacing: 0.02em;
}

.wa-monitor-tenant-name {
    font-weight: 600;
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

.wa-monitor-table tbody td,
.wa-monitor-table tbody th {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.wa-monitor-table tbody th {
    width: 38%;
    font-weight: 600;
    color: #495057;
    background: transparent;
}

.wa-monitor-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.wa-monitor-empty {
    text-align: center;
    padding: 2.5rem 1.5rem;
    color: #6c757d;
}

.wa-monitor-empty__title {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0;
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
@endphp

<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Number Details') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Credit Management') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="{{ route('admin.whatsapp-numbers.monitor') }}">{{ __('WhatsApp Numbers Monitor') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ $number->number ?? __('Details') }}</a></li>
    </ul>
</div>

<div class="wa-monitor-detail-header">
    <p class="text-muted mb-0">
        @if (!empty($number->number))
            <span class="wa-monitor-phone">{{ $number->number }}</span>
            @if (!empty($number->name))
                <span class="text-muted"> — {{ $number->name }}</span>
            @endif
        @else
            {{ __('WhatsApp number details') }}
        @endif
    </p>
    <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        {{ __('Back to monitor') }}
    </a>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Number details') }}</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0 wa-monitor-table">
                    <tbody>
                        <tr>
                            <th scope="row">{{ __('Number') }}</th>
                            <td><span class="wa-monitor-phone">{{ $number->number ?? '—' }}</span></td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Name') }}</th>
                            <td>{{ $number->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Tenant') }}</th>
                            <td>
                                @if (!empty($number->username))
                                    <div class="wa-monitor-tenant-name">{{ $number->username }}</div>
                                    @if (!empty($number->email))
                                        <small class="text-muted d-block">{{ $number->email }}</small>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Tenant owner ID') }}</th>
                            <td>{{ $number->tenant_owner_id ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Link status') }}</th>
                            <td>
                                @php $statusLabel = $statusLabels[$number->status] ?? $number->status; @endphp
                                @if ($number->status === 'active')
                                    <span class="badge badge-success">{{ $statusLabel }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ $statusLabel }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Request status') }}</th>
                            <td>{{ $number->request_status ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Phone ID') }}</th>
                            <td><span class="wa-monitor-phone">{{ $number->phone_id ?? '—' }}</span></td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Health') }}</th>
                            <td>
                                @php $healthLabel = $healthLabels[$number->health] ?? $number->health; @endphp
                                @switch($number->health)
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
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Sync') }}</th>
                            <td>
                                @php $syncLabel = $syncLabels[$number->sync] ?? $number->sync; @endphp
                                @if ($number->sync === 'n/a')
                                    —
                                @else
                                    @switch($number->sync)
                                        @case('synced')
                                            <span class="badge badge-success">{{ $syncLabel }}</span>
                                            @break
                                        @case('missing')
                                            <span class="badge badge-warning">{{ $syncLabel }}</span>
                                            @break
                                        @case('owner_mismatch')
                                            <span class="badge badge-danger">{{ $syncLabel }}</span>
                                            @if (!empty($number->wa_number_user_id))
                                                <small class="d-block">{{ __('Routed to tenant #:id', ['id' => $number->wa_number_user_id]) }}</small>
                                            @endif
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                    @endswitch
                                    <br>
                                    @if (!empty($number->wa_number_id))
                                        #{{ $number->wa_number_id }}
                                        @if (!empty($number->wa_number_phone))
                                            — <span class="wa-monitor-phone">{{ $number->wa_number_phone }}</span>
                                        @endif
                                        @if (!empty($number->wa_number_status))
                                            <br><small class="text-muted">{{ $number->wa_number_status }}</small>
                                        @endif
                                    @else
                                        {{ __('No wa_numbers row matches this phone ID.') }}
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Last inbound') }}</th>
                            <td class="wa-monitor-date">
                                @if (!empty($number->last_inbound_at))
                                    @php $inboundAt = \Illuminate\Support\Carbon::parse($number->last_inbound_at); @endphp
                                    <span>{{ $inboundAt->format('Y-m-d H:i') }}</span>
                                    <span class="wa-monitor-date__relative">{{ $inboundAt->diffForHumans() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Last outbound') }}</th>
                            <td class="wa-monitor-date">
                                @if (!empty($number->last_outbound_at))
                                    @php $outboundAt = \Illuminate\Support\Carbon::parse($number->last_outbound_at); @endphp
                                    <span>{{ $outboundAt->format('Y-m-d H:i') }}</span>
                                    <span class="wa-monitor-date__relative">{{ $outboundAt->diffForHumans() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <p class="text-muted small mb-2">{{ __('Messages are shown per tenant, not per number. A tenant with several numbers shows the same message history on each.') }}</p>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Recent messages') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 wa-monitor-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Direction') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Preview') }}</th>
                                <th scope="col">{{ __('Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td>
                                        @if ($message->direction === 'inbound')
                                            <span class="badge badge-info">{{ __('Inbound') }}</span>
                                        @elseif ($message->direction === 'outbound')
                                            <span class="badge badge-primary">{{ __('Outbound') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $message->direction ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->status ?? '—' }}</td>
                                    <td>{{ $message->preview ?? '—' }}</td>
                                    <td class="wa-monitor-date">
                                        @if (!empty($message->created_at))
                                            @php $messageAt = \Illuminate\Support\Carbon::parse($message->created_at); @endphp
                                            <span>{{ $messageAt->format('Y-m-d H:i') }}</span>
                                            <span class="wa-monitor-date__relative">{{ $messageAt->diffForHumans() }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="wa-monitor-empty">
                                            <p class="wa-monitor-empty__title">{{ __('No messages found for this tenant.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
