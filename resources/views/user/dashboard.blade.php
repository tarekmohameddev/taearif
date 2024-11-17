@extends('user.layout')

@php
    $default = \App\Models\User\Language::where('is_default', 1)->first();
    $user = Auth::guard('web')->user();
    $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
    if (!empty($user)) {
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
    }
    Config::set('app.timezone', $userBs->timezoneinfo->timezone??'');
@endphp
@section('content')
    <div class="mt-2 mb-4">
        <h2 class="pb-2">{{ __('Welcome back') }}, {{ Auth::guard('web')->user()->first_name }}
            {{ Auth::guard('web')->user()->last_name }}!</h2>
    </div>

    @if (is_null($package))
        @php
            $pendingMemb = \App\Models\Membership::query()
                ->where([['user_id', '=', Auth::id()], ['status', 0]])
                ->whereYear('start_date', '<>', '9999')
                ->orderBy('id', 'DESC')
                ->first();
            $pendingPackage = isset($pendingMemb) ? \App\Models\Package::query()->findOrFail($pendingMemb->package_id) : null;
        @endphp

        @if ($pendingPackage)
            <div class="alert alert-warning">
                You have requested a package which needs an action (Approval / Rejection) by Admin. You will be notified via
                mail once an action is taken.
            </div>
            <div class="alert alert-warning">
                <strong>Pending Package: </strong> {{ $pendingPackage->title }}
                <span class="badge badge-secondary">{{ $pendingPackage->term }}</span>
                <span class="badge badge-warning">Decision Pending</span>
            </div>
        @else
            <div class="alert alert-warning">
                Your membership is expired. Please purchase a new package / extend the current package.
            </div>
        @endif
    @else
        <div class="row justify-content-center align-items-center mb-1">
            <div class="col-12">
                <div class="alert border-left border-primary text-dark">
                    @if ($package_count >= 2)
                        @if ($next_membership->status == 0)
                            <strong class="text-danger">You have requested a package which needs an action (Approval /
                                Rejection) by Admin. You will be notified via mail once an action is taken.</strong><br>
                        @elseif ($next_membership->status == 1)
                            <strong class="text-danger">You have another package to activate after the current package
                                expires. You cannot purchase / extend any package, until the next package is
                                activated</strong><br>
                        @endif
                    @endif

                    <strong>Current Package: </strong> {{ $current_package->title }}
                    <span class="badge badge-secondary">{{ $current_package->term }}</span>
                    @if ($current_membership->is_trial == 1)
                        (Expire Date: {{ Carbon\Carbon::parse($current_membership->expire_date)->format('M-d-Y') }})
                        <span class="badge badge-primary">Trial</span>
                    @else
                        (Expire Date:
                        {{ $current_package->term === 'lifetime' ? 'Lifetime' : Carbon\Carbon::parse($current_membership->expire_date)->format('M-d-Y') }})
                    @endif

                    @if ($package_count >= 2)
                        <div>
                            <strong>Next Package To Activate: </strong> {{ $next_package->title }} <span
                                class="badge badge-secondary">{{ $next_package->term }}</span>
                            @if ($current_package->term != 'lifetime' && $current_membership->is_trial != 1)
                                (
                                Activation Date:
                                {{ Carbon\Carbon::parse($next_membership->start_date)->format('M-d-Y') }},
                                Expire Date:
                                {{ $next_package->term === 'lifetime' ? 'Lifetime' : Carbon\Carbon::parse($next_membership->expire_date)->format('M-d-Y') }})
                            @endif
                            @if ($next_membership->status == 0)
                                <span class="badge badge-warning">Decision Pending</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        @if (!empty($permissions) && in_array('Skill', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-round" href="{{ route('user.skill.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-cogs"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Skills') }}</p>
                                    <h4 class="card-title">{{ $skills }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Portfolio', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-warning card-round" href="{{ route('user.portfolio.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-address-card"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Portfolios') }}</p>
                                    <h4 class="card-title">{{ $portfolios }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Service', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-info card-round" href="{{ route('user.services.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Services') }}</p>
                                    <h4 class="card-title">{{ $services }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Testimonial', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-primary card-round" href="{{ route('user.testimonials.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="far fa-comment"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Testimonials') }}</p>
                                    <h4 class="card-title">{{ $testimonials }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Blog', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-success card-round" href="{{ route('user.blog.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Blog') }}</p>
                                    <h4 class="card-title">{{ $blogs }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        @if (!empty($permissions) && in_array('Counter Information', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-secondary card-round" href="{{ route('user.counter-information.index') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Counter Information') }}</p>
                                    <h4 class="card-title">{{ $counter_informations }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Follow/Unfollow', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-default card-round" href="{{ route('user.follower.list') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Followers') }}</p>
                                    <h4 class="card-title">{{ $followers }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
        @if (!empty($permissions) && in_array('Follow/Unfollow', $permissions))
            <div class="col-sm-6 col-md-4">
                <a class="card card-stats card-primary card-round" href="{{ route('user.following.list') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">{{ __('Followings') }}</p>
                                    <h4 class="card-title">{{ $followings }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="row row-card-no-pd">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-head-row">
                                <h4 class="card-title">{{ __('Recent Payment Logs') }}</h4>
                            </div>
                            <p class="card-category">
                                {{ __('10 latest payment logs') }}
                            </p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    @if (count($memberships) == 0)
                                        <h3 class="text-center">{{ __('NO PAYMENT LOG FOUND') }}</h3>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">{{ __('Transaction Id') }}</th>
                                                        <th scope="col">{{ __('Amount') }}</th>
                                                        <th scope="col">{{ __('Payment Status') }}</th>
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($memberships as $key => $membership)
                                                        <tr>
                                                            <td>{{ strlen($membership->transaction_id) > 30 ? mb_substr($membership->transaction_id, 0, 30, 'UTF-8') . '...' : $membership->transaction_id }}
                                                            </td>
                                                            @php
                                                                $bex = json_decode($membership->settings);
                                                            @endphp
                                                            <td>
                                                                @if ($membership->price == 0)
                                                                    {{ __('Free') }}
                                                                @else
                                                                    {{ format_price($membership->price) }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($membership->status == 1)
                                                                    <h3 class="d-inline-block badge badge-success">
                                                                        {{ __('Success') }}</h3>
                                                                @elseif ($membership->status == 0)
                                                                    <h3 class="d-inline-block badge badge-warning">
                                                                        {{ __('Pending') }}</h3>
                                                                @elseif ($membership->status == 2)
                                                                    <h3 class="d-inline-block badge badge-danger">
                                                                        {{ __('Rejected') }}</h3>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if (!empty($membership->name !== 'anonymous'))
                                                                    <a class="btn btn-sm btn-info" href="#"
                                                                        data-toggle="modal"
                                                                        data-target="#detailsModal{{ $membership->id }}">{{ __('Detail') }}</a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <div class="modal fade" id="detailsModal{{ $membership->id }}"
                                                            tabindex="-1" role="dialog"
                                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="exampleModalLabel">
                                                                            {{ __('Owner Details') }}
                                                                        </h5>
                                                                        <button type="button" class="close"
                                                                            data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <h3 class="text-warning">
                                                                            {{ __('Member details') }}</h3>
                                                                        <label>{{ __('Name') }}</label>
                                                                        <p>{{ $membership->user->first_name . ' ' . $membership->user->last_name }}
                                                                        </p>
                                                                        <label>{{ __('Email') }}</label>
                                                                        <p>{{ $membership->user->email }}</p>
                                                                        <label>{{ __('Phone') }}</label>
                                                                        <p>{{ $membership->user->phone_number }}</p>
                                                                        <h3 class="text-warning">
                                                                            {{ __('Payment details') }}</h3>
                                                                        @if ($membership->discount > 0)
                                                                            <p>
                                                                                <strong>{{ __('Package Price') }}:
                                                                                </strong>
                                                                                {{ $membership->package_price == 0 ? 'Free' : $membership->package_price }}
                                                                            </p>

                                                                            <p>
                                                                                <strong>{{ __('Discount') }}: </strong>
                                                                                {{ $membership->discount }}
                                                                            </p>
                                                                        @endif
                                                                        <p>
                                                                            <strong>{{ __('Total') }}: </strong>
                                                                            {{ $membership->price == 0 ? 'Free' : $membership->price }}
                                                                        </p>
                                                                        <p><strong>{{ __('Currency') }}: </strong>
                                                                            {{ $membership->currency }}
                                                                        </p>
                                                                        <p><strong>{{ __('Method') }}: </strong>
                                                                            {{ $membership->payment_method }}
                                                                        </p>
                                                                        <h3 class="text-warning">
                                                                            {{ __('Package Details') }}</h3>
                                                                        <p><strong>{{ __('Title') }}:
                                                                            </strong>{{ !empty($membership->package) ? $membership->package->title : '' }}
                                                                        </p>
                                                                        <p><strong>{{ __('Term') }}: </strong>
                                                                            {{ !empty($membership->package) ? $membership->package->term : '' }}
                                                                        </p>
                                                                        <p><strong>Start
                                                                                Date: </strong>
                                                                            @if (\Illuminate\Support\Carbon::parse($membership->start_date)->format('Y') == '9999')
                                                                                <span class="badge badge-danger">Never
                                                                                    Activated</span>
                                                                            @else
                                                                                {{ \Illuminate\Support\Carbon::parse($membership->start_date)->format('M-d-Y') }}
                                                                            @endif
                                                                        </p>
                                                                        <p><strong>Expire
                                                                                Date: </strong>

                                                                            @if (\Illuminate\Support\Carbon::parse($membership->start_date)->format('Y') == '9999')
                                                                                -
                                                                            @else
                                                                                @if ($membership->modified == 1)
                                                                                    {{ \Illuminate\Support\Carbon::parse($membership->expire_date)->addDay()->format('M-d-Y') }}
                                                                                    <span
                                                                                        class="badge badge-primary btn-xs">modified
                                                                                        by Admin</span>
                                                                                @else
                                                                                    {{ $membership->package->term == 'lifetime' ? 'Lifetime' : \Illuminate\Support\Carbon::parse($membership->expire_date)->format('M-d-Y') }}
                                                                                @endif
                                                                            @endif
                                                                        </p>
                                                                        <p>
                                                                            <strong>{{ __('Purchase Type') }}: </strong>
                                                                            @if ($membership->is_trial == 1)
                                                                                {{ __('Trial') }}
                                                                            @else
                                                                                {{ $membership->price == 0 ? 'Free' : 'Regular' }}
                                                                            @endif
                                                                        </p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-dismiss="modal">
                                                                            {{ __('Close') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (!empty($permissions) && in_array('Follow/Unfollow', $permissions))
            <div class="col-lg-6">
                <div class="row row-card-no-pd">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-head-row">
                                    <h4 class="card-title">{{ __('Latest Followings') }}</h4>
                                </div>
                                <p class="card-category">
                                    {{ __('10 latest followings') }}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">{{ __('Image') }}</th>
                                                        <th scope="col">{{ __('User name') }}</th>
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($users as $key => $user)
                                                        <tr>
                                                            <td><img src="{{ asset('assets/front/img/user/' . $user->photo) }}"
                                                                    alt="" width="40"></td>
                                                            <td>{{ strlen($user->username) > 30 ? mb_substr($user->username, 0, 30, 'UTF-8') . '...' : $user->username }}
                                                            </td>
                                                            <td>
                                                                <a target="_blank" class="btn btn-secondary btn-sm"
                                                                    href="{{ route('front.user.detail.view', $user->username) }}">
                                                                    <span class="btn-label">
                                                                        <i class="fas fa-eye"></i>
                                                                    </span>
                                                                    {{ __('View') }}
                                                                </a>
                                                                <a class="btn btn-danger btn-sm"
                                                                    href="{{ route('user.unfollow', $user->id) }}">
                                                                    {{ __('Unfollow') }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
