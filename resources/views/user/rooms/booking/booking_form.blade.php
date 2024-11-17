@extends('user.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('New Booking') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Hotel Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Room Bookings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('New Booking') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Make New Booking') }}</div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <form id="bookingForm" action="{{ route('user.room_bookings.make_booking') }}" method="POST">
                                @csrf
                                <input type="hidden" name="room_id" value="{{ request()->input('room_id') }}">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Check In / Out Date') . '*' }}</label>
                                            <input type="text" class="form-control"
                                                placeholder="{{ __('Select Dates') }}" id="date-range" name="dates"
                                                value="{{ old('dates') }}" readonly>
                                            @error('dates')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Number of Nights') . '*' }}</label>
                                            <input type="text" class="form-control"
                                                placeholder="{{ __('Number of Nights') }}" id="night" name="nights"
                                                value="{{ old('nights') }}" readonly>
                                            @error('nights')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                            <p class="text-warning mt-1 mb-0 ml-1">
                                                {{ __('Number of nights will be calculated based on checkin & checkout date.') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Number of Guests') . '*' }}</label>
                                            <input type="number" class="form-control"
                                                placeholder="{{ __('Enter Number of Guests') }}" name="guests"
                                                value="{{ old('guests') }}">
                                            @error('guests')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Subtotal') . ' (' . $currencyInfo->base_currency_text . ')' }}</label>
                                            <input type="text" class="form-control" name="subtotal" value="0.00"
                                                readonly id="subtotal">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Discount') . ' (' . $currencyInfo->base_currency_text . ')' }}</label>
                                            <input type="text" class="form-control" name="discount" value="0.00"
                                                id="discount" placeholder="Enter Discount Amount"
                                                oninput="applyDiscount()">
                                            <p class="text-warning mt-1 mb-0 ml-1">
                                                {{ __('Do not press \'Enter\' key.') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Total Rent') . ' (' . $currencyInfo->base_currency_text . ')' }}</label>
                                            <input type="text" class="form-control" name="total" value="0.00"
                                                readonly id="total">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Customer Full Name') . '*' }}</label>
                                            <input type="text" class="form-control"
                                                placeholder="{{ __('Enter Full Name') }}" name="customer_name"
                                                value="{{ old('customer_name') }}">
                                            @error('customer_name')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Customer Phone Number') . '*' }}</label>
                                            <input type="text" class="form-control"
                                                placeholder="{{ __('Enter Phone Number') }}" name="customer_phone"
                                                value="{{ old('customer_phone') }}">
                                            @error('customer_phone')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Customer Email') . '*' }}</label>
                                            <input type="email" class="form-control"
                                                placeholder="{{ __('Enter Customer Email') }}" name="customer_email"
                                                value="{{ old('customer_email') }}">
                                            @error('customer_email')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Payment Method') . '*' }}</label>
                                            <select name="payment_method" class="form-control">
                                                <option selected disabled>
                                                    {{ __('Select a Method') }}</option>

                                                @if (count($onlineGateways) > 0)
                                                    @foreach ($onlineGateways as $onlineGateway)
                                                        <option
                                                            {{ old('payment_method') == $onlineGateway->name ? 'selected' : '' }}
                                                            value="{{ $onlineGateway->name }}">
                                                            {{ $onlineGateway->name }}
                                                        </option>
                                                    @endforeach
                                                @endif

                                                @if (count($offlineGateways) > 0)
                                                    @foreach ($offlineGateways as $offlineGateway)
                                                        <option
                                                            {{ old('payment_method') == $offlineGateway->name ? 'selected' : '' }}
                                                            value="{{ $offlineGateway->name }}">
                                                            {{ $offlineGateway->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('payment_method')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Payment Status') . '*' }}</label>
                                            <select name="payment_status" class="form-control">
                                                <option selected disabled>{{ __('Select Payment Status') }}</option>
                                                <option {{ old('payment_status') == '1' ? 'selected' : '' }}
                                                    value="1">
                                                    {{ __('Paid') }}
                                                </option>
                                                <option {{ old('payment_status') == '0' ? 'selected' : '' }}
                                                    value="0">
                                                    {{ __('Unpaid') }}
                                                </option>
                                            </select>
                                            @error('payment_status')
                                                <p class="mt-1 mb-0 ml-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="bookingForm" class="btn btn-success">
                                {{ __('Submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        'use strict';

        // assign php value to js variable
        let bookedDates = {!! json_encode($dates) !!};
        let roomRentPerNight = '{{ $rent }}';
    </script>


    <script type="text/javascript" src="{{ asset('assets/admin/js/admin-room.js') }}"></script>
@endsection
