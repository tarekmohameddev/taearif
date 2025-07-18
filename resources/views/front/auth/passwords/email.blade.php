@extends('front.layout')

@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/front/css/forgot-password.css') }}">
@endsection

@section('pagename')
  - {{ __('Reset Password') }}
@endsection

@section('meta-description', !empty($seo) ? $seo->forget_password_meta_description : '')
@section('meta-keywords', !empty($seo) ? $seo->forget_password_meta_keywords : '')

@section('breadcrumb-title')
  {{ __('Reset Password') }}
@endsection
@section('breadcrumb-link')
  {{ __('Reset Password') }}
@endsection

@section('content')
  <!--====== End Breadcrumbs section ======-->
  <section class="login-section ptb-120">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="user-form">
            <div class="title">
            </div>
            @if (session('status'))
              <div class="alert alert-success" role="alert">
                {{ session('status') }}
              </div>
            @endif
            <form class="login-form" action="{{ route('user.forgot.password.submit') }}" method="post"
              enctype="multipart/form-data">
              @csrf
              <div class="form-group mb-3">
                <label class="form-label">{{ __('Email Address') }}*</label>
                <input type="email" name="email" class="form-control" value="{{ Request::old('email') }}">
                @error('email')
                  <p class="text-danger mb-2 mt-2">{{ $message }}</p>
                @enderror
              </div>

              <div class="form_group">
                                @if ($bs->is_recaptcha == 1)
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

              <div class="form-group">
                <button class="btn btn-lg btn-primary ">{{ __('Send Password Reset Link') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
