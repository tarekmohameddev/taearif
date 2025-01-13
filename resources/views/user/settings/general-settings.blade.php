@extends('user.layout')

@php
  $user = Auth::guard('web')->user();
  $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
  if (!empty($user)) {
      $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
      $permissions = json_decode($permissions, true);
  }
@endphp

@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}">
  <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .settings-section {
        border-bottom: 1px solid #eee;
        padding-bottom: 2rem;
        margin-bottom: 2rem;
    }
    .settings-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .upload-btn {
        background-color: white;
        border: 2px dashed #8c9998;
        color: #0E9384;
        padding: 1rem;
        width: 80%;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
    }
    .upload-btn:hover {
        border-color: #0E9384;
    }
    .preview-image {
        max-width: 200px;
        margin-bottom: 1rem;
    }
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    .section-description {
        color: #6c757d;
        margin-bottom: 1.5rem;
    }
  </style>
@endsection

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Site Settings') }}</h4>
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
        <a href="#">{{ __('Basic Settings') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <form action="{{ route('user.general_settings.update_all') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- Website Title Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Website Name') }}</h3>
              <p class="section-description">{{ __('This is the name of your website. It will appear in the header, footer, and browser tabs. Make it simple and memorable.') }}</p>
              <div class="form-group">
                <input type="text" class="form-control" name="website_title" 
                  value="{{ isset($data->website_title) ? $data->website_title : '' }}">
                <p id="errwebsite_title" class="text-danger mb-0"></p>
              </div>
            </div>

            <!-- Color Settings Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Main Colors for Website') }}</h3>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>{{ __('Base Color') }}</label>
                    <input type="text" class="form-control jscolor" name="base_color"
                      value="{{ $data->base_color }}">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>{{ __('Secondary Color') }}</label>
                    <input type="text" class="form-control jscolor" name="secondary_color"
                      value="{{ $data->secondary_color }}">
                  </div>
                </div>
              </div>
            </div>

            <!-- Logo Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Website Logo') }}</h3>
              <p class="section-description">{{ __('Upload your website logo here. The logo represents your brand and will appear on the website header, footer, and other sections.') }}</p>
              <div class="form-group">
                <div class="preview-image">
                  <img src="{{ isset($basic_setting->logo) ? asset('assets/front/img/user/'.$basic_setting->logo) : asset('assets/admin/img/noimage.jpg') }}" 
                    alt="logo" class="img-thumbnail">
                </div>
                <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                <button type="button" class="upload-btn" onclick="document.getElementById('logo').click()">
                  <i class="bi bi-upload mb-2"></i>
                  <span>{{ __('Upload Logo') }}</span>
                </button>
              </div>
            </div>

            <!-- Preloader Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Website Preloading Image') }}</h3>
              <p class="section-description">{{ __('This image will be displayed while your website is loading. Use a professional or branded image to enhance the user experience.') }}</p>
              <div class="form-group">
                <div class="preview-image">
                  <img src="{{ isset($basic_setting->preloader) ? asset('assets/front/img/user/'.$basic_setting->preloader) : asset('assets/admin/img/noimage.jpg') }}" 
                    alt="preloader" class="img-thumbnail">
                </div>
                <input type="file" id="preloader" name="preloader" class="d-none" accept="image/*">
                <button type="button" class="upload-btn" onclick="document.getElementById('preloader').click()">
                  <i class="bi bi-upload mb-2"></i>
                  <span>{{ __('Upload Preloader') }}</span>
                </button>
              </div>
            </div>

            <!-- Breadcrumb Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
              <p class="section-description">{{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}</p>
              <div class="form-group">
                <div class="preview-image">
                  <img src="{{ isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/'.$basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" 
                    alt="breadcrumb" class="img-thumbnail">
                </div>
                <input type="file" id="breadcrumb" name="breadcrumb" class="d-none" accept="image/*">
                <button type="button" class="upload-btn" onclick="document.getElementById('breadcrumb').click()">
                  <i class="bi bi-upload mb-2"></i>
                  <span>{{ __('Upload Breadcrumb Image') }}</span>
                </button>
              </div>
            </div>

            <!-- Favicon Section -->
            <div class="settings-section">
              <h3 class="section-title">{{ __('Fav Icon') }}</h3>
              <p class="section-description">{{ __('Upload a small icon that represents your website. It will appear in the browser tab next to your website name.') }}</p>
              <div class="form-group">
                <div class="preview-image">
                  <img src="{{ isset($basic_setting->favicon) ? asset('assets/front/img/user/'.$basic_setting->favicon) : asset('assets/admin/img/noimage.jpg') }}" 
                    alt="favicon" class="img-thumbnail">
                </div>
                <input type="file" id="favicon" name="favicon" class="d-none" accept="image/*">
                <button type="button" class="upload-btn" onclick="document.getElementById('favicon').click()">
                  <i class="bi bi-upload mb-2"></i>
                  <span>{{ __('Upload Favicon') }}</span>
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
              <button type="submit" class="btn btn-success btn-lg">
                {{ __('Save All Settings') }}
              </button>
            </div>
<div class="row d-none">
<div class="col-lg-6 offset-lg-3 d-none">
                <div class="form-group">
                  <label>{{ __('Timezone') }} *</label>
                  <select name="timezone" class="form-control select2">
                    @foreach ($timezones as $timezone)
                      <option value="{{ $timezone->id }}" {{ $timezone->id == $data->timezone ? 'selected' : '' }}>
                        {{ $timezone->timezone }} / (UTC {{ $timezone->gmt_offset }})</option>
                    @endforeach
                  </select>
                  @if ($errors->has('timezone'))
                    <p class="mb-0 text-danger">{{ $errors->first('timezone') }}</p>
                  @endif
                </div>
              </div>
              <div class="col-lg-6 offset-lg-3 d-none">
                <div class="form-group">
                  <label>{{ __('Email Verification Status') . '*' }}</label>
                  <div class="selectgroup w-100">
                    <label class="selectgroup-item">
                      <input type="radio" name="email_verification_status" value="1" class="selectgroup-input"
                        {{ $data->email_verification_status == 1 ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Active') }}</span>
                    </label>

                    <label class="selectgroup-item">
                      <input type="radio" name="email_verification_status" value="0" class="selectgroup-input"
                        {{ $data->email_verification_status == 0 ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Deactive') }}</span>
                    </label>
                  </div>
                  <p id="err_email_verification_status" class="mb-0 text-danger em"></p>

                  <p class="text-warning mt-2 mb-0">
                    {{ __('If it is deactive, the user does not receive a verification mail when he create a new account.') }}
                  </p>
                </div>
              </div>
            </div>
            @if (
                !empty($permissions) &&
                    (in_array('Ecommerce', $permissions) ||
                        in_array('Hotel Booking', $permissions) ||
                        in_array('Donation Management', $permissions) ||
                        in_array('Course Management', $permissions)))
              <div class="row d-none">
                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">
                    <br>
                    <h3 class="text-warning">{{ __('Currency Settings') }}</h3>
                    <hr class="divider">
                  </div>
                </div>
                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">

                    <label>{{ __('Base Currency Symbol') }} **</label>
                    <input type="text" class="form-control ltr" name="base_currency_symbol"
                      value="{{ $data->base_currency_symbol }}">
                    <p id="errbase_currency_symbol" class="em text-danger mb-0"></p>
                  </div>
                </div>

                <div class="col-lg-6 offset-lg-3">
                  <div class="form-group">
                    <label>{{ __('Base Currency Symbol Position') }} **</label>
                    <select name="base_currency_symbol_position" class="form-control ltr">
                      <option value="left" {{ $data->base_currency_symbol_position == 'left' ? 'selected' : '' }}>
                        Left
                      </option>
                      <option value="right" {{ $data->base_currency_symbol_position == 'right' ? 'selected' : '' }}>
                        Right
                      </option>
                    </select>
                    <p id="errbase_currency_symbol_position" class="em text-danger mb-0"></p>
                  </div>
                </div>
                <div class="col-lg-6 offset-lg-3">
                  <div class="row">
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Text') }} **</label>
                        <input type="text" class="form-control ltr" name="base_currency_text"
                          value="{{ $data->base_currency_text }}">
                        <p id="errbase_currency_text" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Text Position') }} **</label>
                        <select name="base_currency_text_position" class="form-control ltr">
                          <option value="left" {{ $data->base_currency_text_position == 'left' ? 'selected' : '' }}>
                            Left
                          </option>
                          <option value="right" {{ $data->base_currency_text_position == 'right' ? 'selected' : '' }}>
                            Right
                          </option>
                        </select>
                        <p id="errbase_currency_text_position" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                    <div class="col-lg-4">
                      <div class="form-group">
                        <label>{{ __('Base Currency Rate') }} **</label>
                        <div class="input-group mb-2">
                          <div class="input-group-prepend">
                            <span class="input-group-text">{{ __('1 USD') }} =</span>
                          </div>
                          <input type="text" name="base_currency_rate" class="form-control ltr"
                            value="1">
                          <div class="input-group-append">
                            <span class="input-group-text">{{ $data->base_currency_text }}</span>
                          </div>
                        </div>
                        <p id="errbase_currency_rate" class="em text-danger mb-0"></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
</div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @section('scripts')
  <script>
    // Preview uploaded images
    function readURL(input, previewImg) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          previewImg.attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Handle file input changes
    $('input[type="file"]').change(function() {
      var previewImg = $(this).siblings('.preview-image').find('img');
      readURL(this, previewImg);
    });
  </script>
  @endsection
@endsection