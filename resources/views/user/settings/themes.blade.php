@extends('user.layout')
@php
    
    $user = Auth::guard('web')->user();
    $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
    if (!empty($user)) {
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
        $userBs = \App\Models\User\BasicSetting::where('user_id', $user->id)->first();
    }
@endphp
@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Home Page Version') }}</h4>
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
                <a href="#">{{ __('Home Page Version') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title">{{ __('Theme Settings') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="ajaxForm" action="{{ route('user.theme.update') }}" method="post">
                                @csrf

                                <div class="form-group">
                                    <label class="form-label">{{ __('Theme') }} *</label>
                                    <div class="row">
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_one"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_one' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_one.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme One') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_two"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_two' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_two.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Two') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_three"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_three' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_three.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Three') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_four"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_four' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_four.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Four') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_five"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_five' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_five.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Five') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_six"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_six' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_six.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Six') }}</h5>
                                        </div>
                                        <div class="col-4 col-sm-4">
                                            <label class="imagecheck mb-2">
                                                <input name="theme" type="radio" value="home_seven"
                                                    class="imagecheck-input"
                                                    {{ !empty($data->theme) && $data->theme == 'home_seven' ? 'checked' : '' }}>
                                                <figure class="imagecheck-figure">
                                                    <img src="{{ asset('assets/front/img/user/templates/home_seven.png') }}"
                                                        alt="title" class="imagecheck-image">
                                                </figure>
                                            </label>
                                            <h5 class="text-center">{{ __('Theme Seven') }}</h5>
                                        </div>
                                        @if (!empty($permissions) && in_array('Ecommerce', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home_eight"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home_eight' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/home_eight.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Theme Eight') }}</h5>
                                            </div>
                                        @endif
                                        @if (!empty($permissions) && in_array('Hotel Booking', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home_nine"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home_nine' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/home_nine.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Theme Nine') }}</h5>
                                            </div>
                                        @endif
                                        @if (!empty($permissions) && in_array('Course Management', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home_ten"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home_ten' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/home_ten.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Theme Ten') }}</h5>
                                            </div>
                                        @endif

                                        @if (!empty($permissions) && in_array('Donation Management', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home_eleven"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home_eleven' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/home_eleven.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Theme Eleven') }}</h5>
                                            </div>
                                        @endif
                                        @if (!empty($permissions) && in_array('Portfolio', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home_twelve"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home_twelve' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/home_twelve.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Theme Twelve') }}</h5>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
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
            </div>
        </div>
    </div>
@endsection
