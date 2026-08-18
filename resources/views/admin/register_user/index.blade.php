@extends('admin.layout')

@section('content')
<style>
    .date-range-filter {
        background: #f8f9fa;
        padding: 2px;
        border-radius: 5px;
    }

    .dropdown-menu .dropdown-item {
        transition: transform 0.5s ease;
        transform-origin: center;
    }

    .dropdown-menu .dropdown-item:hover {
        transform: scale(1.05);
    }
</style>
<div class="page-header">
    <h4 class="page-title">
        {{ __('Registered Users') }}
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
            <a href="#">{{ __('Registered Users') }}</a>
        </li>
    </ul>
</div>

{{-- Flash Messages --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ __('Error') }}!</strong> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ __('Success') }}!</strong> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>{{ __('Warning') }}!</strong> {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif


<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="border-radius: 20px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); overflow: hidden; border: none;">
            <div class="card-header text-white" style="padding: 1.5rem 2rem; background: linear-gradient(45deg, #000000, #333333);">
                <h5 class="mb-0" style="font-size: 1.5rem; font-weight: 600;">إحصائيات عامة</h5>
            </div>
            <div class="card-body" style="padding: 2rem;">
                <div class="row g-5">
                    @foreach($stats as $stat)
                    <div class="col-md">
                        <div class="p-4" style="background-color: rgba(0, 0, 0, 0.05); border-radius: 15px; transition: all 0.3s ease; height: 100%;">
                            <h6 class="mb-2" style="font-size: 1.1rem; color: #6c757d;">{{ $stat['title'] }}</h6>
                            <p class="mb-0" style="font-size: 2rem; font-weight: bold; color: #000000;">{{ $stat['count'] }}</p>
                            <p class="mb-0" style="font-size: 1rem; color: #6c757d;">موقع</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #000000;
    }

    @media (max-width: 767.98px) {
        .card-body .row>div:not(:last-child) {
            margin-bottom: 1rem;
        }
    }

    .card-body .row>div>div {
        cursor: pointer;
    }
</style>


<div class="row">
    <div class="col-md-12">

        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-title">
                            {{ __('Registered Users') }}
                        </div>
                    </div>
                    <div class="col-lg-6 mt-2 mt-lg-0">
                        <button class="btn btn-danger float-lg-right float-none btn-sm ml-2 mt-1 d-none bulk-delete" data-href="{{ route('admin.register.user.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                            {{ __('Delete') }}</button>
                        <button class="btn btn-primary float-lg-right float-none btn-sm ml-2 mt-1" data-toggle="modal" data-target="#addUserModal"><i class="fas fa-plus"></i> {{ __('Add User') }}</button>
                        <form action="{{ route('admin.register.user') }}" method="GET" class="float-lg-right float-none">
                            @foreach ($userListQuery as $filterName => $filterValue)
                                @if ($filterName !== 'term')
                                    <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                                @endif
                            @endforeach
                            <input type="text" name="term" class="form-control min-w-250" value="{{ request()->input('term') }}" placeholder="{{ __('Search by name / email / phone number') }}">
                        </form>
                    </div>

                    {{-- Filters --}}
                    <div class="col-lg-12 mt-2">
                        <div class="float-lg-left float-none">
                            {{-- Collapse Toggle --}}
                            <button class="btn btn-sm btn-outline-primary mb-2" type="button" data-toggle="collapse" data-target="#dateFilterCollapse" aria-expanded="false" aria-controls="dateFilterCollapse" id="dateFilterBtn">
                                <i class="fas fa-calendar mr-1"></i> {{ __('Advanced Filters') }}
                            </button>

                            {{-- Filters Collapse --}}
                            <div class="collapse hide" id="dateFilterCollapse">
                                <form action="{{ route('admin.register.user') }}" method="GET" class="float-lg-right float-none ml-2">
                                    @if (array_key_exists('term', $userListQuery))
                                        <input type="hidden" name="term" value="{{ $userListQuery['term'] }}">
                                    @endif
                                    @if (array_key_exists('package_id', $userListQuery))
                                        <input type="hidden" name="package_id" value="{{ $userListQuery['package_id'] }}">
                                    @endif
                                    <div class="input-group date-range-filter flex-wrap">

                                        {{-- Date From --}}
                                        <div class="form-group mr-4">
                                            <label for="start_date" class="small text-muted mb-1">{{ __('From Date') }} ({{ __('optional') }})</label>
                                            <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="{{ request()->input('start_date') }}">

                                            {{-- Date To --}}

                                            <label for="end_date" class="small text-muted mb-1">{{ __('To Date') }} ({{ __('optional') }})</label>
                                            <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="{{ request()->input('end_date') }}">
                                        </div>

                                        {{-- Subscription Ends From --}}
                                        <div class="form-group mr-4">
                                            <label for="subscription_start" class="small text-muted mb-1">{{ __('Subscription Ends From') }} ({{ __('optional') }})</label>
                                            <input type="date" id="subscription_start" name="subscription_start" class="form-control form-control-sm" value="{{ request()->input('subscription_start') }}">

                                            {{-- Subscription Ends To --}}
                                            <label for="subscription_end" class="small text-muted mb-1">{{ __('Subscription Ends To') }} ({{ __('optional') }})</label>
                                            <input type="date" id="subscription_end" name="subscription_end" class="form-control form-control-sm" value="{{ request()->input('subscription_end') }}">
                                        </div>

                                        {{-- Active Subscription Filter --}}
                                        <div class="form-group mr-4">
                                            <label for="active_membership" class="small text-muted mb-1">{{ __('Active Subscription') }}</label>
                                            <select name="active_membership" id="active_membership" class="form-control form-control-sm">
                                                <option value="">{{ __('-- All Users --') }}</option>
                                                <option value="1" {{ request()->input('active_membership') == '1' ? 'selected' : '' }}>
                                                    {{ __('Only Active Subscribers') }}
                                                </option>
                                                <option value="0" {{ request()->input('active_membership') == '0' ? 'selected' : '' }}>
                                                    {{ __('Only Non-Active / Expired') }}
                                                </option>
                                            </select>

                                            {{-- Paid / Trial Filter --}}
                                            <label for="paid_member" class="small text-muted mb-1">{{ __('Membership_Type') }}</label>
                                            <select name="paid_member" id="paid_member" class="form-control form-control-sm">
                                                <option value="">{{ __('-- All Types --') }}</option>
                                                <option value="paid" {{ request()->input('paid_member') == 'paid'  ? 'selected' : '' }}>
                                                    {{ __('Paid_Member') }}
                                                </option>
                                                <option value="trial" {{ request()->input('paid_member') == 'trial' ? 'selected' : '' }}>
                                                    {{ __('Free_Trial') }}
                                                </option>
                                            </select>
                                        </div>

                                        {{-- Referrer Dropdown --}}
                                        <div class="form-group mr-2">
                                            <label for="referred_by" class="small text-muted mb-1">{{ __('Referred By') }}</label>
                                            <select name="referred_by" id="referred_by" class="form-control form-control-sm">
                                                <option value="">{{ __('-- All Referrers --') }}</option>
                                                @foreach($affiliateUsers as $affUser)
                                                <option value="{{ $affUser->id }}" {{ request()->input('referred_by') == $affUser->id ? 'selected' : '' }}>
                                                    {{ $affUser->username }} ({{ $affUser->email }})
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="form-group d-flex align-items-end">
                                            <button type="submit" class="btn btn-sm btn-primary mr-2">
                                                <i class="fas fa-filter mr-1"></i> {{ __('Filter') }}
                                            </button>
                                            <a href="{{ route('admin.register.user') }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-undo mr-1"></i> {{ __('Reset') }}
                                            </a>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Local Storage Script --}}
                    <script>
                        // When DOM is ready
                        document.addEventListener('DOMContentLoaded', function() {
                            const collapse = document.getElementById('dateFilterCollapse');
                            const btn = document.getElementById('dateFilterBtn');

                            // Get saved state
                            const isCollapsed = localStorage.getItem('dateFilterCollapsed') === 'false';

                            if (isCollapsed) {
                                collapse.classList.remove('hide');
                                collapse.classList.add('show');
                            }

                            // Save state on toggle
                            btn.addEventListener('click', function() {
                                const isCurrentlyCollapsed = !collapse.classList.contains('hide');
                                localStorage.setItem('dateFilterCollapsed', isCurrentlyCollapsed);
                            });
                        });
                    </script>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="btn-group btn-group-sm flex-wrap mb-3" role="group">
                            @php
                                $showAllQuery = Arr::except($userListQuery, ['package_id', 'paid_member', 'page']);
                            @endphp
                            <a href="{{ route('admin.register.user', $showAllQuery) }}"
                               class="btn {{ !request()->filled('package_id') ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ __('Show All') }}
                            </a>
                            @foreach ($packageFilterButtons as $package)
                                @php
                                    $query = Arr::except($userListQuery, ['package_id', 'paid_member', 'page']);
                                    if ((string) request('package_id') !== (string) $package->id) {
                                        $query['package_id'] = $package->id;
                                    }
                                @endphp
                                <a href="{{ route('admin.register.user', $query) }}"
                                   class="btn {{ (string) request('package_id') === (string) $package->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $package->title }}
                                </a>
                            @endforeach
                        </div>
                        @if ($users->total() == 0)
                        <h3 class="text-center">{{ __('NO USER FOUND') }}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" class="bulk-check" data-val="all">
                                        </th>
                                        <th scope="col">{{ __('Name') }}</th>
                                        <th scope="col">{{ __('Phone') }}</th>
                                        <th scope="col">{{ __('Web site') }}</th>
                                        <th scope="col">{{ __('Package') }}</th>
                                        <td scope="col">{{ __('Action') }}</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $key => $user)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="bulk-check" data-val="{{ $user->id }}">
                                        </td>
                                        <td>{{ $user->basic_setting?->company_name ?? '—' }}</td>
                                        <td>{{ $user->phone }}</td>
                                        <td><a href="https://{{$user->username}}.taearif.com/ar/" target="_blank">https://{{$user->username}}.taearif.com/ar/</a></td>
                                        @php
                                        $currMemb = $user->currentMembership ?? $user->pendingMembership;
                                        $currPackage = $currMemb?->package;
                                        @endphp
                                        <td>
                                            @if ($currPackage)
                                            <a target="_blank" href="{{route('admin.package.edit', $currPackage->id)}}">{{$currPackage->title}}</a>
                                            <span class="badge badge-secondary badge-xs mr-2">{{ __($currPackage->term) }}</span>

                                            <p class="mb-0">
                                                @if ($currMemb->is_trial == 1)
                                                ({{ __('Expire Date') }}: {{Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                                <span class="badge badge-primary">تجريبية</span>
                                                @else
                                                ({{ __('Expire Date') }}: {{$currPackage->term === 'lifetime' ? __('Lifetime') : Carbon\Carbon::parse($currMemb->expire_date)->format('M-d-Y')}})
                                                @endif
                                                @if ($currMemb->status == 0)
                                            <form id="statusForm{{$currMemb->id}}" class="d-inline-block" action="{{route('admin.payment-log.update')}}" method="post">
                                                @csrf
                                                <input type="hidden" name="id" value="{{$currMemb->id}}">
                                                <select class="form-control form-control-sm bg-warning" name="status" onchange="document.getElementById('statusForm{{$currMemb->id}}').submit();">
                                                    <option value=0 selected>{{ __('Pending') }}</option>
                                                    <option value=1>{{ __('Success') }}</option>
                                                    <option value=2>{{ __('Rejected') }}</option>
                                                </select>
                                            </form>
                                            @endif
                                            </p>

                                            @else
                                            <a data-target="#addCurrentPackage-{{ $user->id }}" data-toggle="modal" class="btn btn-xs btn-primary text-white"><i class="fas fa-plus"></i> {{ __('Add Package') }}</a>
                                            @endif

                                        </td>

                                        @includeIf('admin.register_user.template-modal')
                                        @includeIf('admin.register_user.template-image-modal')
                                        @includeIf('admin.register_user.edit-current-package')
                                        @includeIf('admin.register_user.add-current-package')
                                        @includeIf('admin.register_user.edit-next-package')
                                        @includeIf('admin.register_user.add-next-package')
                                        <td>
                                            @if ($currPackage)
                                            <form id="remove-package-form-{{ $user->id }}" action="{{ route('admin.user.currPackage.remove') }}" class="deleteform d-none" method="POST">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="deletebtn"></button>
                                            </form>
                                            @endif
                                            <form id="delete-user-form-{{ $user->id }}" class="deleteform d-none" action="{{ route('admin.register.user.delete') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <button type="submit" class="deletebtn"></button>
                                            </form>
                                            <div class="dropdown">
                                                <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    {{ __('Actions') }}
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a href="{{ route('admin.register.user.secretLogin', $user) }}" target="_blank" class="dropdown-item">
                                                        {{ __('Secret Login') }}
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('admin.register.user.view', $user->id) }}">{{ __('Details') }}</a>
                                                    <a class="dropdown-item" href="{{ route('admin.register.user.changePass', $user->id) }}">{{ __('Change Password') }}</a>
                                                    @if ($currPackage)
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editCurrentPackage-{{ $user->id }}">{{ __('Change Current Package') }}</a>
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('remove-package-form-{{ $user->id }}').querySelector('.deletebtn').click();">
                                                        {{ __('Remove Package') }}
                                                    </a>
                                                    @endif
                                                    <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('delete-user-form-{{ $user->id }}').querySelector('.deletebtn').click();">
                                                        {{ __('Delete') }}
                                                    </a>
                                                </div>
                                            </div>
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


                        {{ $users->appends($userListQuery)->links() }}

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add User') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.register.user.store') }}" method="POST" id="ajaxForm">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Username') }} *</label>
                        <input class="form-control" type="text" name="username">
                        <p id="errusername" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Email') }} *</label>
                        <input class="form-control" type="email" name="email">
                        <p id="erremail" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Password') }} *</label>
                        <input class="form-control" type="password" name="password">
                        <p id="errpassword" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Confirm Password') }} *</label>
                        <input class="form-control" type="password" name="password_confirmation">
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Package / Plan') }} *</label>
                        <select name="package_id" class="form-control">
                            @if (!empty($packages))
                            @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->title }}
                                ({{ $package->term }})
                            </option>
                            @endforeach
                            @endif
                        </select>
                        <p id="errpackage_id" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Payment Gateway') }} *</label>
                        <select name="payment_gateway" class="form-control">
                            @if (!empty($gateways))
                            @foreach ($gateways as $gateway)
                            <option value="{{ $gateway->name }}">{{ $gateway->name }}</option>
                            @endforeach
                            @endif
                        </select>
                        <p id="errpayment_gateway" class="text-danger mb-0 em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Publicly Hidden') }} *</label>
                        <select name="online_status" class="form-control">
                            <option value="1">{{ __('No') }}</option>
                            <option value="0">{{ __('Yes') }}</option>
                        </select>
                        <p id="erronline_status" class="text-danger mb-0 em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer text-center">
                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Add User') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection
