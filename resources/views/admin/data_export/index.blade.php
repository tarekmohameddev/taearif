@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('تصدير البيانات') }} / {{ __('Data Export') }}</h4>
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
            <a href="#">{{ __('تصدير البيانات') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{ __('تصدير البيانات') }}</div>
                    </div>
                    <div class="col-lg-4 offset-lg-4 mt-2 mt-lg-0">
                        <form action="{{ url()->current() }}" class="d-inline-block float-right">
                            <input class="form-control" type="text" name="term"
                                placeholder="{{ __('Search by username, email, or phone') }}"
                                value="{{ request()->input('term', '') }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if ($users->isEmpty())
                            <h3 class="text-center">{{ __('NO USER FOUND') }}</h3>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th scope="col">{{ __('Username') }}</th>
                                            <th scope="col">{{ __('Phone') }}</th>
                                            <th scope="col">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $user->basic_setting?->company_name ?? '—' }}</td>
                                                <td>{{ $user->username }}</td>
                                                <td>{{ $user->phone ?? '—' }}</td>
                                                <td>
                                                    <a class="btn btn-sm btn-primary"
                                                        href="{{ route('admin.register.user.export', $user->id) }}">
                                                        <i data-lucide="download"></i>
                                                        {{ __('تصدير') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
