@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Login'] ?? __('Login') }}
@endsection
@section('meta-description', !empty($userSeo) ? $userSeo->meta_description_login : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->meta_keyword_login : '')

@section('page-name')
    {{ $keywords['Login'] ?? __('Log In') }}
@endsection
@section('br-name')
    {{ $keywords['Login'] ?? __('Log In') }}
@endsection

@section('content')
    <!--====== SING IN PART START ======-->
    <div class="user-area-section section-gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="user-form">
                        <div class="title mb-3">
                            <h4>
                                {{ $keywords['Login'] ?? __('Log In') }}
                            </h4>
                        </div>
                        <form action="{{ route('customer.api_login.submit', getParam()) }}" method="POST">
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
                                <label>{{ $keywords['Password'] ?? __('Password') }} *</label>
                                <input type="password" class="form_control" name="password" value="{{ old('password') }}"
                                    placeholder="{{ $keywords['Enter_Password'] ?? __('Enter Password') }}">
                                @error('password')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form_group form_inline">
                                <div>
                                    <label for="checkbox1"></label>
                                </div>
                                <a href="{{ route('customer.api_forgot_password', getParam()) }}">{{ $keywords['Lost_your_password'] ?? __('Lost your password') . '?' }}</a>
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
                                    class="btn">{{ $keywords['Login_Now'] ?? __('Login Now') }}</button>
                            </div>
                            <div class="new-user text-center">
                                <p class="text">{{ $keywords['New_user'] ?? 'New user' }}? <a
                                        href="{{ route('customer.api_signup', getParam()) }}">{{ $keywords['Donot_have_an_account'] ?? "Don't have an account" }}?</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--====== SING IN PART ENDS ======-->
@endsection
