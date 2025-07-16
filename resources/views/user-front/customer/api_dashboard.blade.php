@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('page-name')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('br-name')
    {{ $keywords['Dashboard'] ?? __('Dashboard') }}
@endsection

@section('content')
    <section class="user-dashbord pt-100 pb-60">
        <div class="container">
            <div class="row">
                @includeIf('user-front.customer.api_side-navbar')
                <div class="col-lg-9">
                    <!-- Profile Information -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="user-profile-details">
                                <div class="account-info mb-3">
                                    <div class="title">
                                        <h4 class="mb-2">
                                            {{ $keywords['account_information'] ?? __('Account Information') }}
                                        </h4>
                                    </div>
                                    <div class="main-info">
                                        <ul class="list">
                                            <li class="py-1">
                                                <strong>{{ $keywords['Name'] ?? __('Name') }}:</strong>
                                                {{ $authUser->name }}
                                            </li>
                                            <li class="py-1">
                                                <strong>{{ $keywords['email'] ?? __('Email') }}:</strong>
                                                {{ $authUser->email }}
                                            </li>
                                            <li class="py-1">
                                                <strong>{{ $keywords['Phone_Number'] ?? __('Phone') }}:</strong>
                                                {{ $authUser->phone_number ?? 'N/A' }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        @if (in_array('Real Estate Management', $packagePermissions))
                            <div class="col-md-4">
                                <a href="{{ route('front.user.property.wishlist', getParam()) }}" class="card card-box box-2 mb-3">
                                    <div class="card-info">
                                        <h5>{{ $keywords['Property Wishlist'] ?? __('Property Wishlist') }}</h5>
                                        <p>{{ $propertyWishlistsCount }}</p>
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if (in_array('Ecommerce', $packagePermissions))
                            @if ($userShopSetting->is_shop == 1 && $userShopSetting->catalog_mode == 0)
                                <div class="col-md-4">
                                    <a href="{{ route('customer.orders', getParam()) }}" class="card card-box box-1 mb-3">
                                        <div class="card-info">
                                            <h5>{{ $keywords['myOrders'] ?? __('My Orders') }}</h5>
                                            <p>{{ $totalorders }}</p>
                                        </div>
                                    </a>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <a href="{{ route('customer.wishlist', getParam()) }}" class="card card-box box-2 mb-3">
                                    <div class="card-info">
                                        <h5>{{ $keywords['mywishlist'] ?? __('My Wishlist') }}</h5>
                                        <p>{{ $totalwishlist }}</p>
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if (in_array('Course Management', $packagePermissions))
                            <div class="col-md-4">
                                <a class="card card-box box-3 mb-3" href="{{ route('customer.purchase_history', getParam()) }}">
                                    <div class="card-info">
                                        <h5>{{ $keywords['Enrolled_Courses'] ?? __('Enrolled Courses') }}</h5>
                                        <p>{{ $couseCount }}</p>
                                    </div>
                                </a>
                            </div>
                        @endif
                        @if (in_array('Hotel Booking', $packagePermissions))
                            @if (isset($roomSetting) && $roomSetting->is_room == 1)
                                <div class="col-md-4">
                                    <a class="card card-box box-4 mb-3" href="{{ route('customer.purchase_history', getParam()) }}">
                                        <div class="card-info">
                                            <h5>{{ $keywords['Room_Bookings'] ?? __('Room Bookings') }}</h5>
                                            <p>{{ $roomBookingCount }}</p>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Recent Orders -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="user-profile-details">
                                <div class="account-info mb-3">
                                    <div class="title">
                                        <h4 class="mb-2">
                                            {{ $keywords['Recent_Orders'] ?? __('Recent Orders') }}
                                        </h4>
                                    </div>
                                    <div class="main-info">
                                        @if($orders->isEmpty())
                                            <p>{{ $keywords['No_orders_found'] ?? __('No orders found.') }}</p>
                                        @else
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ $keywords['Order_ID'] ?? __('Order ID') }}</th>
                                                        <th>{{ $keywords['Order_Number'] ?? __('Order Number') }}</th>
                                                        <th>{{ $keywords['Total_Amount'] ?? __('Total Amount') }}</th>
                                                        <th>{{ $keywords['Status'] ?? __('Status') }}</th>
                                                        <th>{{ $keywords['Actions'] ?? __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($orders as $order)
                                                        <tr>
                                                            <td>{{ $order->id }}</td>
                                                            <td>{{ $order->order_number ?? 'N/A' }}</td>
                                                            <td>{{ number_format($order->total_amount ?? 0, 2) }}</td>
                                                            <td>{{ $order->status ?? 'N/A' }}</td>
                                                            <td>
                                                                <a href="{{ route('customer.order.view', [$order->id, getParam()]) }}" class="btn btn-sm btn-primary">
                                                                    {{ $keywords['View'] ?? __('View') }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
