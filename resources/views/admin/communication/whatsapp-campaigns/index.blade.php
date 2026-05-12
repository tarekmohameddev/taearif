@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Campaigns') }}</h4>
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
            <a href="#">{{ __('WhatsApp Campaigns') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">{{ __('Campaign List') }}</div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.whatsapp-campaigns.index') }}" class="form-inline mb-4">
                    <div class="form-group mr-3">
                        <label for="status" class="mr-2">{{ __('Status') }}:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('admin.whatsapp-campaigns.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i> {{ __('Reset') }}
                    </a>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('WhatsApp Number') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Recipients') }}</th>
                                <th>{{ __('Sent') }}</th>
                                <th>{{ __('Failed') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $campaign)
                                <tr>
                                    <td>{{ $campaign->id }}</td>
                                    <td>
                                        <strong>{{ $campaign->name }}</strong>
                                        @if($campaign->description)
                                            <br><small class="text-muted">{{ Str::limit($campaign->description, 40) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->user)
                                            <small>{{ $campaign->user->email ?? $campaign->user->username ?? $campaign->user_id }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($campaign->waNumber)
                                            <span class="badge badge-secondary">{{ $campaign->waNumber->phone_number }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @switch($campaign->status)
                                            @case('draft')
                                                <span class="badge badge-secondary">{{ __('Draft') }}</span>
                                                @break
                                            @case('scheduled')
                                                <span class="badge badge-info">{{ __('Scheduled') }}</span>
                                                @break
                                            @case('in_progress')
                                                <span class="badge badge-primary">{{ __('In Progress') }}</span>
                                                @break
                                            @case('paused')
                                                <span class="badge badge-warning">{{ __('Paused') }}</span>
                                                @break
                                            @case('sent')
                                                <span class="badge badge-success">{{ __('Sent') }}</span>
                                                @break
                                            @case('failed')
                                                <span class="badge badge-danger">{{ __('Failed') }}</span>
                                                @break
                                            @default
                                                <span class="badge badge-light">{{ $campaign->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $campaign->recipient_count ?? 0 }}</td>
                                    <td>{{ $campaign->sent_count ?? 0 }}</td>
                                    <td>{{ $campaign->failed_count ?? 0 }}</td>
                                    <td>{{ $campaign->created_at ? $campaign->created_at->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-campaigns.show', $campaign) }}" class="btn btn-sm btn-info" title="{{ __('View') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        {{ __('No campaigns found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $campaigns->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
