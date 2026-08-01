@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Calling — Tenants') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Calling') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Tenants') }}</a></li>
    </ul>
    <div class="ml-auto">
        <form class="form-inline" method="GET" action="{{ route('admin.calling.tenants.index') }}">
            <div class="form-group mr-2">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search tenant…') }}"
                       value="{{ request('search') }}">
            </div>
            <div class="form-group mr-2">
                <select name="calling" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    <option value="enabled" {{ request('calling') === 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                    <option value="disabled" {{ request('calling') === 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
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
                        <h5 class="card-title mb-0">{{ __('Tenants') }}</h5>
                        <p class="text-muted small mb-0">{{ __('Enable calling per tenant, then add trunks and phone numbers.') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.calling.sim-lines.index') }}" class="btn btn-outline-primary btn-sm">
                            {{ __('All phone numbers') }}
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
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Username') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Calling') }}</th>
                                <th>{{ __('Trunks') }}</th>
                                <th>{{ __('Numbers') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tenants as $tenant)
                                <tr>
                                    <td>{{ $tenant->id }}</td>
                                    <td>{{ $tenant->company_name ?: trim(($tenant->first_name ?? '') . ' ' . ($tenant->last_name ?? '')) ?: '-' }}</td>
                                    <td>{{ $tenant->username }}</td>
                                    <td>{{ $tenant->email }}</td>
                                    <td>
                                        @if($tenant->callSetting?->enabled)
                                            <span class="badge badge-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $tenant->call_trunks_count }}</td>
                                    <td>{{ $tenant->call_sim_lines_count }}</td>
                                    <td>
                                        <a href="{{ route('admin.calling.tenants.show', $tenant->id) }}"
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-cog"></i> {{ __('Manage') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">{{ __('No tenants found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($tenants->hasPages())
                <div class="card-footer">
                    {{ $tenants->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
