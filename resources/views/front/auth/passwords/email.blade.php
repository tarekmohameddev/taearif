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

              <div class="form-group mb-3">
                <label class="form-label">{{ __('Reset Method') }}*</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="reset_method" id="email_method" value="email" {{ old('reset_method', 'email') == 'email' ? 'checked' : '' }}>
                  <label class="form-check-label" for="email_method">
                    <i class="fas fa-envelope"></i> {{ __('Send reset code via Email') }}
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="reset_method" id="whatsapp_method" value="whatsapp" {{ old('reset_method') == 'whatsapp' ? 'checked' : '' }}>
                  <label class="form-check-label" for="whatsapp_method">
                    <i class="fab fa-whatsapp"></i> {{ __('Send reset code via WhatsApp') }}
                  </label>
                </div>
                @error('reset_method')
                  <p class="text-danger mb-2 mt-2">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group mb-3" id="phone_field" style="display: none;">
                <label class="form-label">{{ __('Phone Number') }}*</label>
                <input type="tel" name="phone" class="form-control" value="{{ Request::old('phone') }}" placeholder="+966501234567">
                <small class="form-text text-muted">{{ __('Enter your WhatsApp number with country code') }}</small>
                @error('phone')
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

@section('scripts')
<script>
$(document).ready(function() {
    // Show/hide phone field based on reset method
    $('input[name="reset_method"]').on('change', function() {
        if ($(this).val() === 'whatsapp') {
            $('#phone_field').show();
            $('input[name="phone"]').prop('required', true);
        } else {
            $('#phone_field').hide();
            $('input[name="phone"]').prop('required', false);
        }
    });

    // Initialize on page load
    $('input[name="reset_method"]:checked').trigger('change');
});
</script>
@endsection
