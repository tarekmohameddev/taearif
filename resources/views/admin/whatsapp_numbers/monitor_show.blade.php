@extends('admin.layout')

@section('content')
<style>
/* WhatsApp Numbers Monitor — detail page */
.wa-monitor-phone {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.9375rem;
    font-weight: 500;
    letter-spacing: 0.02em;
}

.wa-monitor-tenant-name {
    font-weight: 600;
}

.wa-monitor-cell-meta {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    max-width: 14rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wa-monitor-date-inline {
    white-space: nowrap;
    font-size: 0.8125rem;
    line-height: 1.35;
}

.wa-monitor-date-inline .text-muted {
    font-size: 0.75rem;
}

.wa-monitor-table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    padding: 0.625rem 0.875rem;
}

.wa-monitor-table tbody td,
.wa-monitor-table tbody th {
    padding: 0.5rem 0.875rem;
    vertical-align: middle;
    font-size: 0.875rem;
}

.wa-monitor-table tbody th {
    width: 38%;
    font-weight: 600;
    color: #495057;
    background: #fafbfc;
}

.wa-monitor-table .badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.wa-monitor-detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    padding: 0.875rem 1rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0;
}

.wa-monitor-detail-header__number {
    font-size: 1.0625rem;
    margin: 0;
}

.wa-monitor-details-card,
.wa-monitor-messages-panel {
    border: 1px solid #dee2e6;
    border-radius: 0;
    background: #fff;
    height: 100%;
}

.wa-monitor-details-card .card-header,
.wa-monitor-messages-panel .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0;
    padding: 0.75rem 1rem;
}

.wa-monitor-messages-panel {
    display: flex;
    flex-direction: column;
    min-height: 28rem;
}

.wa-monitor-messages-panel__hint {
    padding: 0.625rem 1rem;
    margin: 0;
    font-size: 0.8125rem;
    color: #6c757d;
    background: #fafbfc;
    border-bottom: 1px solid #eee;
}

.wa-monitor-messages-scroll {
    flex: 1 1 auto;
    overflow: auto;
    max-height: 32rem;
    min-height: 20rem;
}

.wa-monitor-messages-scroll .wa-monitor-table {
    margin-bottom: 0;
}

.wa-monitor-messages-scroll .wa-monitor-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    box-shadow: 0 1px 0 #dee2e6;
}

.wa-monitor-messages-scroll .wa-monitor-table tbody td {
    border-top: 1px solid #f1f3f5;
}

.wa-monitor-preview {
    max-width: 16rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    direction: auto;
    unicode-bidi: plaintext;
}

.wa-monitor-preview[title] {
    cursor: help;
}

.wa-monitor-empty {
    text-align: center;
    padding: 2.5rem 1.5rem;
    color: #6c757d;
}

.wa-monitor-empty__icon {
    font-size: 2rem;
    color: #adb5bd;
    margin-bottom: 0.75rem;
}

.wa-monitor-empty__title {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0;
}

.wa-monitor-messages-count {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #6c757d;
}

@media (max-width: 991.98px) {
    .wa-monitor-messages-panel {
        min-height: 22rem;
    }

    .wa-monitor-messages-scroll {
        max-height: 24rem;
        min-height: 16rem;
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
    $backIcon = ! empty($admin_rtl) ? 'fa-arrow-right' : 'fa-arrow-left';
    $messageCount = $messages->count();
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
    <p class="wa-monitor-detail-header__number mb-0">
        @if (!empty($number->number))
            <span class="wa-monitor-phone">{{ $number->number }}</span>
            @if (!empty($number->name))
                <span class="text-muted"> · {{ $number->name }}</span>
            @endif
        @else
            {{ __('WhatsApp number details') }}
        @endif
    </p>
    <a href="{{ route('admin.whatsapp-numbers.monitor') }}" class="btn btn-secondary btn-sm">
        <i class="fas {{ $backIcon }}" aria-hidden="true"></i>
        {{ __('Back to monitor') }}
    </a>
</div>

<div class="row align-items-stretch">
    <div class="col-lg-7 mb-4">
        <div class="card wa-monitor-messages-panel mb-0">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">{{ __('Recent messages') }}</h5>
                <span class="wa-monitor-messages-count">{{ __(':count messages', ['count' => $messageCount]) }}</span>
            </div>
            <p class="wa-monitor-messages-panel__hint mb-0">
                {{ __('Messages are shown per tenant, not per number. A tenant with several numbers shows the same message history on each.') }}
            </p>
            <div class="wa-monitor-messages-scroll" tabindex="0" role="region" aria-label="{{ __('Recent messages') }}">
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
                                <td>
                                    @if (!empty($message->preview))
                                        <span class="wa-monitor-preview" title="{{ $message->preview }}">{{ $message->preview }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($message->created_at))
                                        @php $messageAt = \Illuminate\Support\Carbon::parse($message->created_at); @endphp
                                        <span class="wa-monitor-date-inline">
                                            {{ $messageAt->format('Y-m-d H:i') }}
                                            <span class="text-muted">· {{ $messageAt->diffForHumans() }}</span>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="wa-monitor-empty">
                                        <div class="wa-monitor-empty__icon" aria-hidden="true">
                                            <i class="far fa-comment-dots"></i>
                                        </div>
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

    <div class="col-lg-5 mb-4">
        <div class="card wa-monitor-details-card mb-0">
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
                                        <span class="wa-monitor-cell-meta" title="{{ $number->email }}">{{ $number->email }}</span>
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
                        @if (!empty($number->request_status) && $number->request_status !== $number->status)
                            <tr>
                                <th scope="row">{{ __('Request status') }}</th>
                                <td>{{ $number->request_status }}</td>
                            </tr>
                        @endif
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
                                        <span class="badge badge-warning text-dark">{{ $healthLabel }}</span>
                                        @break
                                    @case('no_inbound_ever')
                                        <span class="badge badge-info">{{ $healthLabel }}</span>
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
                                            <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                            @break
                                        @case('owner_mismatch')
                                            <span class="badge badge-danger">{{ $syncLabel }}</span>
                                            @if (!empty($number->wa_number_user_id))
                                                <span class="wa-monitor-cell-meta">{{ __('Tenant #:id', ['id' => $number->wa_number_user_id]) }}</span>
                                            @endif
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                    @endswitch
                                    @if (!empty($number->wa_number_id))
                                        <span class="wa-monitor-cell-meta d-block mt-1">
                                            #{{ $number->wa_number_id }}
                                            @if (!empty($number->wa_number_phone))
                                                · <span class="wa-monitor-phone">{{ $number->wa_number_phone }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="wa-monitor-cell-meta d-block mt-1">{{ __('No wa_numbers row matches this phone ID.') }}</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Last inbound') }}</th>
                            <td>
                                @if (!empty($number->last_inbound_at))
                                    @php $inboundAt = \Illuminate\Support\Carbon::parse($number->last_inbound_at); @endphp
                                    <span class="wa-monitor-date-inline">
                                        {{ $inboundAt->format('Y-m-d H:i') }}
                                        <span class="text-muted">· {{ $inboundAt->diffForHumans() }}</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Last outbound') }}</th>
                            <td>
                                @if (!empty($number->last_outbound_at))
                                    @php $outboundAt = \Illuminate\Support\Carbon::parse($number->last_outbound_at); @endphp
                                    <span class="wa-monitor-date-inline">
                                        {{ $outboundAt->format('Y-m-d H:i') }}
                                        <span class="text-muted">· {{ $outboundAt->diffForHumans() }}</span>
                                    </span>
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
</div>
@endsection
