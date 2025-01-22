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
<div class="row">
<div class="col-md-12">
<div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
        <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
            <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
            </div>
            <div class="feature-card-text">
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Theme Settings') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    يمكنك الاختيار من مجموعة رائعه من القوالب التي تناسب احتياجك
                </p>
            </div>
        </div>
    </div>
    </div>
    </div>
    <style>
        .feature-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }
        .feature-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .icon-container {
            width: 3.5rem;
            height: 3.5rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
        }
        .icon-container svg {
            width: 2rem;
            height: 2rem;
        }
        .feature-card-text {
            white-space: normal !important;
        }
        .feature-card-text h2,
        .feature-card-text p {
            white-space: normal !important;
        }
        @media (min-width: 768px) {
            .feature-card-text {
                max-width: 75%;
            }
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
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
                                        <div class="col-4 col-sm-4 d-none">
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
                                        <div class="col-4 col-sm-4 d-none">
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
                                        <div class="col-4 col-sm-4 d-none">
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
                                        <div class="col-4 col-sm-4 d-none">
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
                                            <h5 class="text-center">{{ __('ثيم المجالات المتعددة') }}</h5>
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
                                            <div class="col-4 col-sm-4 d-none">
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
                                            <div class="col-4 col-sm-4 d-none">
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
                                            <div class="col-4 col-sm-4 d-none">
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
                                            <div class="col-4 col-sm-4 d-none">
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
                                            <div class="col-4 col-sm-4 d-none">
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

                                        @if (!empty($permissions) && in_array('Real Estate Management', $permissions))
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home13"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home13' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/realestate_one.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Real Estate One') }}</h5>
                                            </div>
                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home14"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home14' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/realestate_two.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Real Estate Two') }}</h5>
                                            </div>


                                            <div class="col-4 col-sm-4">
                                                <label class="imagecheck mb-2">
                                                    <input name="theme" type="radio" value="home15"
                                                        class="imagecheck-input"
                                                        {{ !empty($data->theme) && $data->theme == 'home15' ? 'checked' : '' }}>
                                                    <figure class="imagecheck-figure">
                                                        <img src="{{ asset('assets/front/img/user/templates/realestate_three.png') }}"
                                                            alt="title" class="imagecheck-image">
                                                    </figure>
                                                </label>
                                                <h5 class="text-center">{{ __('Real Estate Three') }}</h5>
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
