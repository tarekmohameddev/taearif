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

</div>
<style>
    :root {
        --primary: rgb(0, 169, 145);
        --primary-dark: rgb(0, 149, 125);
    }

    .bg-primary {
        background-color: var(--primary) !important;
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .text-primary {
        color: var(--primary) !important;
    }

    .stats-card {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .shipments-banner {
        background: linear-gradient(to left, #ffe4e6, #ccfbf1);
        border-radius: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .progress {
        height: 0.5rem;
    }

    .progress-bar {
        background-color: var(--primary);
    }
</style>
<div class="shipments-banner p-4 mb-4">
    <div class="row">
        <div class="col-md-12">
            <b>
                <h2 class="pb-2">{{ __('Welcome back') }}, {{ Auth::guard('web')->user()->username }}!</h2>
            </b>
            <p class="">مرحباً بك في النسخة التجريبية من منصتنا, برجاء التأكد من اكمال جميع الخطوات الاساسية لضمان الحصول على موقع ويب احترافي</p>
        </div>
    </div>
</div>

<!-- Next Steps & Store Status -->

<div class="row">
    <div class="container mt-5 text-center">
        <h2>Visitor Statistics</h2>

        <!-- Device Chart -->
        <div class="row mt-5">
            <div class="col-md-3">
                <h3>Device Distribution</h3>
                <canvas id="deviceChart"></canvas>
            </div>
            <div class="col-md-4">
                <h3>Country Distribution</h3>
                <canvas id="countryChart"></canvas>
            </div>
        </div>

        <!-- Top Cities and Regions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <h3>Top 5 Cities</h3>
                <ul id="topCities" style="list-style: none;"></ul>
            </div>
            <div class="col-md-6">
                <h3>Top 5 Regions</h3>
                <ul id="topRegions" style="list-style: none;"></ul>
            </div>
        </div>

        <!-- Map -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Visitor </h3>
                <div class="row my-2">
                    <div class="col-md-6 py-1">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="chLine"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 py-1">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="chBar"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<div class="row mb-4">
    <div class="col-md-12">

    </div>
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
            <div class="alert border-left border-primary text-dark text-center">
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
                    <strong>Next Package To Activate: </strong> {{ $next_package->title }} <span class="badge badge-secondary">{{ $next_package->term }}</span>
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

    <div class="row d-none">
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
                                                            {{ __('Success') }}
                                                        </h3>
                                                        @elseif ($membership->status == 0)
                                                        <h3 class="d-inline-block badge badge-warning">
                                                            {{ __('Pending') }}
                                                        </h3>
                                                        @elseif ($membership->status == 2)
                                                        <h3 class="d-inline-block badge badge-danger">
                                                            {{ __('Rejected') }}
                                                        </h3>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (!empty($membership->name !== 'anonymous'))
                                                        <a class="btn btn-sm btn-info" href="#" data-toggle="modal" data-target="#detailsModal{{ $membership->id }}">{{ __('Detail') }}</a>
                                                        @else
                                                        -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <div class="modal fade" id="detailsModal{{ $membership->id }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel">
                                                                    {{ __('Owner Details') }}
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <h3 class="text-warning">
                                                                    {{ __('Member details') }}
                                                                </h3>
                                                                <label>{{ __('Name') }}</label>
                                                                <p>{{ $membership->user->first_name . ' ' . $membership->user->last_name }}
                                                                </p>
                                                                <label>{{ __('Email') }}</label>
                                                                <p>{{ $membership->user->email }}</p>
                                                                <label>{{ __('Phone') }}</label>
                                                                <p>{{ $membership->user->phone_number }}</p>
                                                                <h3 class="text-warning">
                                                                    {{ __('Payment details') }}
                                                                </h3>
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
                                                                    {{ __('Package Details') }}
                                                                </h3>
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
                                                                    <span class="badge badge-primary btn-xs">modified
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
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
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
                                                    <td><img src="{{ asset('assets/front/img/user/' . $user->photo) }}" alt="" width="40"></td>
                                                    <td>{{ strlen($user->username) > 30 ? mb_substr($user->username, 0, 30, 'UTF-8') . '...' : $user->username }}
                                                    </td>
                                                    <td>
                                                        <a target="_blank" class="btn btn-secondary btn-sm" href="{{ route('front.user.detail.view', $user->username) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                            {{ __('View') }}
                                                        </a>
                                                        <a class="btn btn-danger btn-sm" href="{{ route('user.unfollow', $user->id) }}">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('ffff');
            // Fetch data from backend
            $.ajax({
                url: '/stats',
                method: 'GET',
                success: function(response) {
                    // Device Chart
                    var ctx1 = document.getElementById('deviceChart').getContext('2d');
                    new Chart(ctx1, {
                        type: 'pie',
                        data: {
                            labels: response.deviceStats.map(stat => stat.device_type),
                            datasets: [{
                                data: response.deviceStats.map(stat => stat.count),
                                backgroundColor: ['#3a9636', '#555', '#aaa']
                            }]
                        }
                    });

                    // Country Chart
                    var ctx2 = document.getElementById('countryChart').getContext('2d');
                    new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: response.countryStats.map(stat => stat.country),
                            datasets: [{
                                label: 'Country Distribution',
                                data: response.countryStats.map(stat => stat.count),
                                backgroundColor: '#3a9636'
                            }]
                        }
                    });

                    // Top Cities
                    var citiesList = $('#topCities');
                    response.topCities.forEach(city => {
                        citiesList.append('<li>' + city.city + ' (' + city.count + ' visits)</li>');
                    });

                    // Top Regions
                    var regionsList = $('#topRegions');
                    response.topRegions.forEach(region => {
                        regionsList.append('<li>' + region.region_name + ' (' + region.count + ' visits)</li>');
                    });

                    // Map
                    var map = L.map('map').setView([20.0, 0.0], 2);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    response.countryStats.forEach(stat => {
                        if (stat.latitude && stat.longitude) {
                            L.marker([stat.latitude, stat.longitude]).addTo(map)
                                .bindPopup('<b>' + stat.country + '</b><br>Visits: ' + stat.count);
                        }
                    });
                },
                error: function(error) {
                    console.error('Error fetching stats:', error);
                }
            });
        });

        // chart colors
        var colors = ['#007bff', '#28a745', '#333333', '#c3e6cb', '#dc3545', '#6c757d'];

        /* large line chart */
        var chLine = document.getElementById("chLine");
        var chartData = {
            labels: ["S", "M", "T", "W", "T", "F", "S"],
            datasets: [{
                    data: [589, 445, 483, 503, 689, 692, 634],
                    backgroundColor: 'transparent',
                    borderColor: colors[0],
                    borderWidth: 4,
                    pointBackgroundColor: colors[0]
                }
                //   {
                //     data: [639, 465, 493, 478, 589, 632, 674],
                //     backgroundColor: colors[3],
                //     borderColor: colors[1],
                //     borderWidth: 4,
                //     pointBackgroundColor: colors[1]
                //   }
            ]
        };
        if (chLine) {
            new Chart(chLine, {
                type: 'line',
                data: chartData,
                options: {
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: false
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    responsive: true
                }
            });
        }

        /* bar chart */
        var chBar = document.getElementById("chBar");
        if (chBar) {
            new Chart(chBar, {
                type: 'bar',
                data: {
                    labels: ["S", "M", "T", "W", "T", "F", "S"],
                    datasets: [{
                        data: [589, 445, 483, 503, 689, 692, 634],
                        backgroundColor: colors[0]
                    }, ]
                },
                options: {
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            barPercentage: 0.4,
                            categoryPercentage: 0.5
                        }]
                    }
                }
            });
        }
    </script>
    @endsection
