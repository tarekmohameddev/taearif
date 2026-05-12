@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('View WhatsApp Campaign') }}</h4>
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
            <a href="{{ route('admin.communication.whatsapp') }}">{{ __('Communication') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.whatsapp-campaigns.index') }}">{{ __('WhatsApp Campaigns') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{ $campaign->name }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="card-title">{{ $campaign->name }} ({{ $campaign->id }})</div>
                    <a href="{{ route('admin.whatsapp-campaigns.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> {{ __('Back to List') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">{{ __('Status') }}</th>
                                <td>
                                    @switch($campaign->status)
                                        @case('draft') <span class="badge badge-secondary">{{ __('Draft') }}</span> @break
                                        @case('scheduled') <span class="badge badge-info">{{ __('Scheduled') }}</span> @break
                                        @case('in_progress') <span class="badge badge-primary">{{ __('In Progress') }}</span> @break
                                        @case('paused') <span class="badge badge-warning">{{ __('Paused') }}</span> @break
                                        @case('sent') <span class="badge badge-success">{{ __('Sent') }}</span> @break
                                        @case('failed') <span class="badge badge-danger">{{ __('Failed') }}</span> @break
                                        @default <span class="badge badge-light">{{ $campaign->status }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <td>{{ $campaign->user->email ?? $campaign->user->username ?? $campaign->user_id }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('WhatsApp Number') }}</th>
                                <td>{{ $campaign->waNumber ? $campaign->waNumber->phone_number . ' (' . ($campaign->waNumber->name ?? '') . ')' : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Recipient Count') }}</th>
                                <td>{{ $campaign->recipient_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Sent') }}</th>
                                <td>{{ $campaign->sent_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Delivered') }}</th>
                                <td>{{ $campaign->delivered_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Failed') }}</th>
                                <td>{{ $campaign->failed_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Dispatch Reference') }}</th>
                                <td><code>{{ $campaign->dispatch_reference ?? '—' }}</code></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">{{ __('Created') }}</th>
                                <td>{{ $campaign->created_at ? $campaign->created_at->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Scheduled At') }}</th>
                                <td>{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Sent At') }}</th>
                                <td>{{ $campaign->sent_at ? $campaign->sent_at->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('Reserved Credits') }}</th>
                                <td>{{ $campaign->reserved_credits ?? 0 }}</td>
                            </tr>
                            @if($campaign->template)
                            <tr>
                                <th>{{ __('Template') }}</th>
                                <td>{{ $campaign->template->name }}</td>
                            </tr>
                            @endif
                            @if($campaign->description)
                            <tr>
                                <th>{{ __('Description') }}</th>
                                <td>{{ $campaign->description }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($campaign->message)
                <div class="mb-4">
                    <h6>{{ __('Message Text') }}</h6>
                    <div class="p-3 bg-light rounded">
                        {{ Str::limit($campaign->message, 500) }}
                    </div>
                </div>
                @endif

                <h6>{{ __('Message Logs Summary') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logsSummary as $status => $count)
                                <tr>
                                    <td>{{ $status }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">{{ __('No logs found') }}</td>
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
