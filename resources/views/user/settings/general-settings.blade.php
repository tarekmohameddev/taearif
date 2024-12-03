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
@endsection
@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Information') }}</h4>
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
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Information') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <form id="ajaxForm" action="{{ route('user.general_settings.update_info') }}" method="post">
          @csrf
          <div class="card-header">
            <div class="row">
              <div class="col-lg-10">
                <div class="card-title">{{ __('Update Information') }}</div>
              </div>
            </div>
          </div>

          <div class="card-body py-5">
            <div class="row">
              <div class="col-lg-6 offset-lg-3">
                <div class="form-group">
                  <label>{{ __('Website Title*') }}</label>
                  <input type="text" class="form-control" name="website_title"
                    value="{{ isset($data->website_title) ? $data->website_title : '' }}"
                    placeholder="{{ __('Enter Website Title') }}">
                  <p id="errwebsite_title" class="em text-danger mb-0"></p>
                </div>
              </div>
              <div class="col-lg-6 offset-lg-3">
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
              <div class="col-lg-6 offset-lg-3">
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
              <div class="row">
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
                            value="{{ $data->base_currency_rate }}">
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

          <div class="card-footer">
            <div class="row">
              <div class="col-12 text-center">
                <button type="submit" id="submitBtn" class="btn btn-success">
                  {{ __('Update') }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>


  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{__('Update Logo')}}</div>
        </div>
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
              <form  enctype="multipart/form-data" action="{{route('user.logo.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-12 mb-2">
                        <label for="image"><strong> {{__('Logo')}} **</strong></label>
                      </div>
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->logo) ? asset('assets/front/img/user/'.$basic_setting->logo) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>
                      <input type="file" name="file" id="image" class="form-control">
                      <p class="text-warning">{{__('Only JPG, JPEG, PNG images are allowed')}}</p>
                      <p id="errfile" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <div class="form">
                    <div class="form-group from-show-notify row">
                      <div class="col-12 text-center">
                        <button type="submit" class="btn btn-success">{{__('Update')}}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Color Settings') }}</div>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <form id="permissionsForm" class="" action="{{ route('user.color.update') }}"
                                method="post">
                                {{ csrf_field() }}

                                <div class="form-group">
                                    <label for="">{{ __('Base Color') }}</label>
                                    <input type="text" class="form-control jscolor" name="base_color"
                                        value="{{ $data->base_color }}">
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Secondary Color') }}</label>
                                    <input type="text" class="form-control jscolor" name="secondary_color"
                                        value="{{ $data->secondary_color }}">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="form">
                        <div class="form-group from-show-notify row">
                            <div class="col-12 text-center">
                                <button type="submit" id="permissionBtn"
                                    class="btn btn-success">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{__('Update Preloader')}}</div>
        </div>
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
              <form  enctype="multipart/form-data" action="{{route('user.preloader.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-12 mb-2">
                        <label for="image"><strong> {{__('Preloader')}} **</strong></label>
                      </div>
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->preloader) ? asset('assets/front/img/user/'.$basic_setting->preloader) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>
                      <input type="file" name="file" id="image" class="form-control">
                      <p class="text-warning">{{__('Only JPG, JPEG, PNG, GIF images are allowed')}}</p>
                      <p id="errfile" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <div class="form">
                    <div class="form-group from-show-notify row">
                      <div class="col-12 text-center">
                        <button type="submit" class="btn btn-success">{{__('Update')}}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Update Breadcrumb') }}</div>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-5 pb-4">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="imageForm" action="{{ route('user.update_breadcrumb') }}"
                                  method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="">{{ __('Breadcrumb*') }}</label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img
                                            src="{{isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/' . $basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg')}}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="breadcrumb" id="image"
                                           class="form-control image">
                                    @if ($errors->has('breadcrumb'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('breadcrumb') }}</p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="imageForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{__('Update Favicon')}}</div>
        </div>
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-6 offset-lg-3">
              <form id="ajaxForm" enctype="multipart/form-data" action="{{route('user.favicon.update')}}" method="POST">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <div class="col-12 mb-2">
                        <label for="image"><strong>{{__('Favicon')}} **</strong></label>
                      </div>
                      <div class="col-md-12 showImage mb-3">
                        <img src="{{isset($basic_setting->favicon) ? asset('assets/front/img/user/'.$basic_setting->favicon) :  asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                      </div>
                      <input type="file" name="favicon" id="image" class="form-control">
                      <p id="errfavicon" class="mb-0 text-danger em"></p>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <div class="form">
                    <div class="form-group from-show-notify row">
                      <div class="col-12 text-center">
                        <button type="submit" id="submitBtn" class="btn btn-success">{{__('Update')}}</button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('scripts')
@endsection
