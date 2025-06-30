@extends('user-front.layout')

@section('tab-title')
    {{ $currentLanguageInfo->pageHeading->signup_title ?? __('Signup') }}
@endsection
@section('meta-description', !empty($userSeo) ? $userSeo->meta_description_signup : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->meta_keyword_signup : '')

@section('page-name')
    {{ $keywords['Signup'] ?? __('Signup') }}
@endsection
@section('br-name')
    {{ $keywords['Signup'] ?? __('Signup') }}
@endsection

@section('content')
    <!--====== user-area-section part Start ======-->
    <div class="user-area-section section-gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if (Session::has('success'))
                        <div class="alert alert-success mb-4">
                            <p>{{ Session::get('success') }}</p>
                        </div>
                    @endif
                    @if (Session::has('error'))
                        <div class="alert alert-danger mb-4">
                            <p>{{ Session::get('error') }}</p>
                        </div>
                    @endif
                    <div class="user-form">
                        <div class="title mb-3">
                            <h4>{{ $keywords['Signup'] ?? __('Signup') }}</h4>
                        </div>
                        <form action="{{ route('customer.api_signup.submit', getParam()) }}" method="POST">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="form_group">
                                <label>{{ $keywords['Name'] ?? 'Name' }} *</label>
                                <input type="text" placeholder="{{ $keywords['Enter_Name'] ?? 'Enter Name' }}"
                                    class="form_control" name="name" value="{{ old('name') }}">
                                @error('name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group">
                                <label>{{ $keywords['Email_Address'] ?? 'Email Address' }}</label>
                                <input type="email" placeholder="{{ $keywords['Enter_Email_Address'] ?? 'Enter Email Address' }}"
                                    class="form_control" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group">
                                <label>{{ $keywords['Phone_Number'] ?? 'Phone Number' }}</label>
                                <input type="text" placeholder="{{ $keywords['Enter_Phone_Number'] ?? __('Enter Phone Number') }}"
                                    class="form_control" name="phone_number" value="{{ old('phone_number') }}">
                                @error('phone_number')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group">
                                <label>{{ $keywords['Password'] ?? 'Password' }} *</label>
                                <input type="password" placeholder="{{ $keywords['Enter_Password'] ?? __('Enter Password') }}"
                                    class="form_control" name="password">
                                @error('password')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group">
                                <label>{{ $keywords['Confirm_Password'] ?? 'Confirm Password' }} *</label>
                                <input type="password" placeholder="{{ $keywords['Enter_Password_Again'] ?? __('Enter Password Again') }}"
                                    class="form_control" name="password_confirmation">
                                @error('password_confirmation')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group">
                                @if ($userBs->is_recaptcha == 1)
                                    <div class="d-block mb-4">
                                        {!! NoCaptcha::renderJs() !!}
                                        {!! NoCaptcha::display() !!}
                                        @if ($errors->has('g-recaptcha-response'))
                                            @php
                                                $errmsg = $errors->first('g-recaptcha-response');
                                            @endphp
                                            <p class="text-danger mb-0 mt-2">{{ __("$errmsg") }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="form_group">
                                <button type="submit" class="btn btn-form" style="color: black; background-color:gainsboro">{{ $keywords['Signup'] ?? 'Signup!' }}</button>
                            </div>
                        </form>
                        <div class="new-user text-center">
                            <p class="text">
                                <a href="{{ route('customer.api_login', getParam()) }}">{{ $keywords['Back_to_Login'] ?? 'Back to Login' }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--====== user-area-section part End ======-->
@endsection
