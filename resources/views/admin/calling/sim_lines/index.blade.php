@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Calling — Phone numbers') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="{{ route('admin.calling.tenants.index') }}">{{ __('Calling') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Phone numbers') }}</a></li>
    </ul>
    <div class="ml-auto">
        <form class="form-inline" method="GET" action="{{ route('admin.calling.sim-lines.index') }}">
            <div class="form-group mr-2">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search number / label…') }}"
                       value="{{ request('search') }}">
            </div>
            <div class="form-group mr-2">
                <select name="status" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">{{ __('All phone numbers') }}</h5>
                        <p class="text-muted small mb-0">{{ __('Add new numbers from a tenant’s calling page (via a trunk).') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.calling.tenants.index') }}" class="btn btn-outline-primary btn-sm">
                            {{ __('Tenants') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Label') }}</th>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Trunk') }}</th>
                                <th>{{ __('Port') }}</th>
                                <th>{{ __('Endpoint') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($simLines as $line)
                                <tr>
                                    <td>{{ $line->id }}</td>
                                    <td>{{ $line->label }}</td>
                                    <td><code>{{ $line->msisdn }}</code></td>
                                    <td>
                                        @if($line->tenant)
                                            <a href="{{ route('admin.calling.tenants.show', $line->tenant_id) }}">
                                                {{ $line->tenant->company_name ?: ($line->tenant->username ?? '-') }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $line->trunk->name ?? '-' }}</td>
                                    <td>{{ $line->port_index ?? '—' }}</td>
                                    <td><code>{{ $line->asterisk_endpoint }}</code></td>
                                    <td>
                                        <span class="badge badge-{{ $line->is_active ? 'success' : 'secondary' }}">
                                            {{ $line->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline"
                                              action="{{ route('admin.calling.sim-lines.toggle', $line->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-{{ $line->is_active ? 'warning' : 'success' }} btn-sm"
                                                    title="{{ $line->is_active ? __('Deactivate') : __('Activate') }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        @if($line->tenant_id)
                                            <a href="{{ route('admin.calling.tenants.show', $line->tenant_id) }}"
                                               class="btn btn-info btn-sm" title="{{ __('Manage tenant') }}">
                                                <i class="fas fa-cog"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">{{ __('No phone numbers yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($simLines->hasPages())
                <div class="card-footer">
                    {{ $simLines->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
