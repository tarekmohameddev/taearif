@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Forgot_Password'] ?? __('Forgot Password') }}
@endsection
@section('meta-description', !empty($userSeo) ? $userSeo->meta_description_forgot_password : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->meta_keyword_forgot_password : '')

@section('page-name')
    {{ $keywords['Forgot_Password'] ?? __('Forgot Password') }}
@endsection
@section('br-name')
    {{ $keywords['Forgot_Password'] ?? __('Forgot Password') }}
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
                            <h4>
                                {{ $keywords['Forgot_Password'] ?? __('Forgot Password') }}
                            </h4>
                        </div>
                        <form action="{{ route('customer.api_forgot_password.submit', getParam()) }}" method="POST">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="form_group">
                                <label>{{ $keywords['Email_or_Phone'] ?? __('Email or Phone Number') }} *</label>
                                <input type="text" placeholder="{{ $keywords['Enter_Email_or_Phone'] ?? __('Enter Email or Phone Number') }}"
                                    class="form_control" name="identifier" value="{{ old('identifier') }}">
                                @error('identifier')
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
                                <button type="submit" style="color: black; background-color:gainsboro"
                                    class="btn btn-form">{{ $keywords['Submit'] ?? __('Submit') }}</button>
                            </div>
                            <div class="new-user text-center">
                                <p class="text">
                                    <a href="{{ route('customer.api_login', getParam()) }}">{{ $keywords['Back_to_Login'] ?? __('Back to Login') }}</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--====== user-area-section part End ======-->
@endsection
