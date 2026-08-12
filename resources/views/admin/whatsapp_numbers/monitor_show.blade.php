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

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Number details') }}</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        <tr>
                            <th scope="row">{{ __('Number') }}</th>
                            <td>{{ $number->number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Name') }}</th>
                            <td>{{ $number->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Tenant') }}</th>
                            <td>
                                @if (!empty($number->username))
                                    <div>{{ $number->username }}</div>
                                    @if (!empty($number->email))
                                        <small class="text-muted">{{ $number->email }}</small>
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
                            <td>{{ $number->phone_id ?? '—' }}</td>
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
                                                <br>
                                                <small>{{ __('Routed to tenant #:id', ['id' => $number->wa_number_user_id]) }}</small>
                                            @endif
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $syncLabel }}</span>
                                    @endswitch
                                    <br>
                                    @if (!empty($number->wa_number_id))
                                        #{{ $number->wa_number_id }}
                                        @if (!empty($number->wa_number_phone))
                                            — {{ $number->wa_number_phone }}
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
                            <td>
                                @if (!empty($number->last_inbound_at))
                                    {{ \Illuminate\Support\Carbon::parse($number->last_inbound_at)->format('Y-m-d H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('Last outbound') }}</th>
                            <td>
                                @if (!empty($number->last_outbound_at))
                                    {{ \Illuminate\Support\Carbon::parse($number->last_outbound_at)->format('Y-m-d H:i') }}
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
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Direction') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Preview') }}</th>
                                <th>{{ __('Time') }}</th>
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
                                    <td>
                                        @if (!empty($message->created_at))
                                            {{ \Illuminate\Support\Carbon::parse($message->created_at)->format('Y-m-d H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">{{ __('No messages found for this tenant.') }}</td>
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
