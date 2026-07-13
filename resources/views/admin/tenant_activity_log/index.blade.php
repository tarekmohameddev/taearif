@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">
        {{ __('Tenant Activity Logs') }}
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
            <a href="#">{{ __('Tenant Activity Logs') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-title">
                            {{ __('Customers / Tenants') }}
                        </div>
                    </div>
                    <div class="col-lg-6 mt-2 mt-lg-0">
                        <form action="{{ route('admin.tenant-activity-logs.index') }}" method="GET" class="float-lg-right float-none">
                            <input type="text" name="term" class="form-control min-w-250" value="{{ $term }}" placeholder="{{ __('Search by username / company / email / phone') }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if ($tenants->count() === 0)
                    <h5 class="text-center">{{ __('No tenants found') }}</h5>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped mt-3">
                            <thead>
                                <tr>
                                    <th>{{ __('Username') }}</th>
                                    <th>{{ __('Company') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Phone') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tenants as $tenant)
                                    <tr>
                                        <td>{{ $tenant->username }}</td>
                                        <td>{{ $tenant->company_name }}</td>
                                        <td>{{ $tenant->email }}</td>
                                        <td>{{ $tenant->phone }}</td>
                                        <td>
                                            <a href="{{ route('admin.tenant-activity-logs.show', $tenant->id) }}" class="btn btn-sm btn-primary">
                                                {{ __('View Activity Log') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                        {{ $tenants->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
