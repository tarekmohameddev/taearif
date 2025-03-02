@extends('user.layout')

@php
$default = \App\Models\User\Language::where('is_default', 1)->first();
$userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();

$userDefaultLang = $default;
$user = Auth::guard('web')->user();
$package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
if (!empty($user)) {
$permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
$permissions = json_decode($permissions, true);
}
Config::set('app.timezone', $userBs->timezoneinfo->timezone??'');
@endphp

@includeIf('user.partials.rtl-style')

@section('content')

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>


</head>
<!--  -->

<body class="bg-grey">
    <div class="d-flex vh-100">
        <!-- Sidebar -->
        <nav class="w-15 bg-white p-4 d-flex flex-column border-right">
            <h4 class="h5 font-weight-bold text-primary d-flex align-items-center mb-4">
                <i class="fas fa-cogs ml-2 mr-2"></i>{{ __('Website Settings') }}
            </h4>

            <!-- Sidebar Menu -->
            <div class="nav flex-column">
                <a href="#basic-settings" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="basic-settings">
                    <i class="fas fa-sliders-h ml-2 mr-2"></i>{{ __('Basic Settings') }}
                </a>
                <a href="#banner" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="banner">
                    <i class="fas fa-image ml-2 mr-2"></i>{{ __('Banner Section') }}
                </a>
                <a href="#skills" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="skills">
                    <i class="fas fa-tools ml-2 mr-2"></i>{{ __('Skills Section') }}
                </a>
                <a href="#about" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="about">
                    <i class="fas fa-building ml-2 mr-2"></i>{{ __('About Company') }}
                </a>
                <a href="#portfolio" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="portfolio">
                    <i class="fas fa-briefcase ml-2 mr-2"></i>{{ __('Portfolio') }}
                </a>
                <a href="#reviews" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="reviews">
                    <i class="fas fa-star ml-2 mr-2"></i>{{ __('Customer Reviews') }}
                </a>
                <a href="#services" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="services">
                    <i class="fas fa-concierge-bell ml-2 mr-2"></i>{{ __('Services') }}
                </a>
                <a href="#achievements" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="achievements">
                    <i class="fas fa-trophy ml-2 mr-2"></i>{{ __('Achievements') }}
                </a>
                <a href="#brands" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="brands">
                    <i class="fas fa-tags ml-2 mr-2"></i> {{ __('Brands') }}
                </a>
                <a href="#footer" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="footer">
                    <i class="fas fa-shoe-prints ml-2 mr-2"></i> {{ __('Footer') }}
                </a>
                <a href="#menubuilder" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="menubuilder">
                    <i class="fas fa-shoe-prints ml-2 mr-2"></i> {{ __('Menu Builder') }}
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-fill p-4">
            <!-- <h2 class="h3 font-weight-bolder">Website Settings</h2> -->

            <!-- Basic Settings Section -->
            <div id="basic-settings" class="content-section ">
                <h3 class="h4 font-weight-bold">{{ __('Basic Settings') }}</h3>
                <p class="text-muted">{{ __('Manage general website settings such as site name, logo, and favicon') }}.</p>
                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">

                            <div class="card-body ">

                                <form id="mySettingsForm" action="{{ route('user.general_settings.update_all',['language' => request()->input('language')]) }}"
                                method="POST"
                                enctype="multipart/form-data"
                                onsubmit="return storeSectionBeforeSubmit(this)">
                                    @csrf
                                    <input type="hidden" id="lastSection" name="last_section" value="">

                                    <!-- Website Title Section -->

                                    <div class="settings-section">
                                        <h3 class="section-title">{{ __('Website Name') }}</h3>
                                        <p class="section-description">
                                            {{ __('This is the name of your website. It will appear in the header, footer, and browser tabs. Make it simple and memorable.') }}
                                        </p>
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="website_title" value="{{ isset($information['basic_settings']->website_title) ? $information['basic_settings']->website_title : '' }}">
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
                                                    <input type="text" class="form-control jscolor" name="base_color" value="{{ $information['basic_settings']->base_color ?? '#6DB6A2' }}">

                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('Secondary Color') }}</label>
                                                    <input type="text" class="form-control jscolor" name="secondary_color" value="{{ $information['basic_settings']->secondary_color ?? '#6DB6A2' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Logo Section -->
                                    <!-- footerLogo Section -->
                                    <div class="row">
                                        <div class="col-lg-6 offset-lg-3">
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <h3 class="section-title">{{ __('Website Logo') }}</h3>
                                                    <p class="section-description">
                                                        {{ __('Upload your website logo here. The logo represents your brand and will appear on the website header, footer, and other sections') }}
                                                    </p>
                                                    <div class="form-group">
                                                        <div class="col-md-12 mb-3 preview-image">
                                                            <img src="{{ isset($information['basic_settings']->logo) ? asset('assets/front/img/user/'.$information['basic_settings']->logo) : asset('assets/admin/img/noimage.jpg') }}" alt="website logo" class="img-thumbnail">
                                                        </div>

                                                        <!-- This input remains for the website logo -->
                                                        <input type="file" id="website-logo" name="website-logo" class="d-none" accept="image/*">
                                                        <button type="button" class="upload-btn" style="background-color: white; border: 2px dashed #8c9998; color: #0E9384; padding: 1rem; width: 80%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('website-logo').click()">

                                                            <i class="bi bi-upload mb-2"></i>
                                                            <span>{{ __('Upload Logo') }}</span>
                                                        </button>

                                                        <p id="errabout_image" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Website Logo Section -->

                                    <!-- Preloader Section -->
                                    <div class="settings-section d-none">
                                        <h3 class="section-title">{{ __('Website Preloading Image') }}</h3>
                                        <p class="section-description">
                                            {{ __('This image will be displayed while your website is loading. Use a professional or branded image to enhance the user experience.') }}
                                        </p>
                                        <div class="form-group">
                                            <div class="preview-image">
                                                <img src="{{ isset($information['home_setting']->preloader) ? asset('assets/front/img/user/'.$information['home_setting']->preloader) : asset('assets/admin/img/noimage.jpg') }}" alt="preloader" class="img-thumbnail">
                                            </div>
                                            <!-- Preloader input is commented out -->
                                            <button type="button" class="upload-btn d-none" onclick="document.getElementById('preloader').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Preloader') }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Breadcrumb Section -->
                                    <div class="settings-section d-none">
                                        <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
                                        <p class="section-description">
                                            {{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}
                                        </p>
                                        <div class="form-group">
                                            <div class="preview-image">
                                                <img src="{{ isset($information['basic_settings']->breadcrumb) ? asset('assets/front/img/user/'.$information['basic_settings']->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
                                            </div>
                                            <button type="button" class="upload-btn d-none" onclick="document.getElementById('breadcrumb').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Breadcrumb Image') }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Favicon Section -->
                                    <div class="settings-section  d-none">
                                        <h3 class="section-title">{{ __('Fav Icon') }}</h3>
                                        <p class="section-description">
                                            {{ __('Upload a small icon that represents your website. It will appear in the browser tab next to your website name.') }}
                                        </p>
                                        <div class="form-group">
                                            <div class="preview-image">
                                                <img src="{{ isset($information['home_setting']->favicon) ? asset('assets/front/img/user/'.$information['home_setting']->favicon) : asset('assets/admin/img/noimage.jpg') }}" alt="favicon" class="img-thumbnail">
                                            </div>
                                            <button type="button" class="upload-btn d-none" onclick="document.getElementById('favicon').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Favicon') }}</span>
                                            </button>
                                        </div>
                                    </div>


                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            {{ __('Save All Settings') }}
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                </div>
                <!--  -->
            </div>

            @if (
                $userBs->theme == 'home_one' ||
                $userBs->theme == 'home_two' ||
                $userBs->theme == 'home_six' ||
                $userBs->theme == 'home_seven' ||
                $userBs->theme == 'home_eight' ||
                $userBs->theme == 'home14' ||
                $userBs->theme == 'home_nine')
            <!-- Banner Section -->
            <div id="banner" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Banner Section') }} </h3>
                <p class="text-muted">{{ __('Upload and configure homepage banners') }}.</p>
                <!--  -->
                <!-- SLIDER -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        @if (count($information['sliders']) == 0)
                                        <h3 class="text-center">{{ __('NO SLIDER FOUND!') }}</h3>
                                        @else
                                        <div class="row">
                                            @foreach ($information['sliders'] as $slider)
                                            <div class="col-md-3">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <img src="{{ asset('assets/front/img/hero_slider/' . $slider->img) }}" alt="image" class="w-100">
                                                    </div>

                                                    <div class="card-footer text-center">
                                                        <a class="btn btn-secondary btn-sm mr-2" href="{{ route('user.home_page.hero.edit_slider', $slider->id) . '?language=' . request()->input('language') }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                            {{ __('Edit') }}
                                                        </a>

                                                        <form class="deleteform d-inline-block" action="{{ route('user.home_page.hero.delete_slider') }}" method="post">
                                                            @csrf
                                                            <input type="hidden" name="slider_id" value="{{ $slider->id }}">
                                                            <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">

                            <div class="card-body pt-5 pb-5">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <form id="sliderVersionForm"
                                        action="{{ route('user.home_page.hero.store_slider_info') }}"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <div class="form-group">
                                                <label for="">{{__('Language')}} **</label>
                                                <select id="language" name="user_language_id" class="form-control">
                                                    <option value="" selected disabled>{{__('Select a language')}}</option>
                                                    @foreach ($userLanguages as $lang)
                                                    <option value="{{$lang->id}}">{{$lang->name}}</option>
                                                    @endforeach
                                                </select>
                                                <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                                            </div>
                                            <!-- upload image sedtion -->
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <label for="image"><strong>{{__('Background Image')}}*</strong></label>
                                                </div>
                                                <div class="col-md-12 showImage mb-3">
                                                    <img src="{{asset('assets/admin/img/noimage.jpg')}}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="file" name="slider_img" id="image" class="d-none">
                                                <button type="button" class="upload-btn" style="background-color: white;
                                                                            border: 2px dashed #8c9998;
                                                                            color: #0E9384;
                                                                            padding: 1rem;
                                                                            width: 80%;
                                                                            display: flex;
                                                                            flex-direction: column;
                                                                            align-items: center;
                                                                            cursor: pointer;" onclick="document.getElementById('image').click()">
                                                    <i class="bi bi-upload mb-2"></i>
                                                    <span>{{ __('Background Image') }}</span>
                                                </button>
                                                @if ($errors->has('slider_img'))
                                                <p class="mt-2 mb-0 text-danger">{{ $errors->first('slider_img') }}</p>
                                                @endif
                                            </div>
                                            <div class="row">
                                                <div @if ($userBs->theme != 'home14') class="col-lg-6"@else class="col-lg-12" @endif>
                                                    <div class="form-group">
                                                        <label for="">{{ __('Title') }}</label>
                                                        <input type="text" class="form-control" name="title" placeholder="{{__('Enter Slider Title')}}">
                                                        @if ($errors->has('title'))
                                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('title') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div @if ($userBs->theme != 'home14') class="col-lg-6" @else class="col-lg-12" @endif>
                                                    <div class="form-group">
                                                        <label for="">{{ __('Subtitle') }}</label>
                                                        <input type="text" class="form-control" name="subtitle" placeholder="{{__('Enter Slider Subtitle')}}">
                                                        @if ($errors->has('subtitle'))
                                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('subtitle') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($userBs->theme != 'home14')
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Button Name') }}</label>
                                                        <input type="text" class="form-control" name="btn_name" placeholder="{{__('Enter Slider Button Name')}}">
                                                        @if ($errors->has('btn_name'))
                                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('btn_name') }}</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label>{{ __('Button URL') }}</label>
                                                        <input type="url" class="form-control ltr" name="btn_url" placeholder="{{__('Enter Slider Button URL')}}">
                                                        @if ($errors->has('btn_url'))
                                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('btn_url') }}</p>
                                                        @endif
                                                    </div>
                                                </div>

                                            </div>
                                            @endif
                                            <div class="row">

                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Serial Number*') }}</label>
                                                        <input type="number" class="form-control ltr" name="serial_number" placeholder="{{__('Enter Slider Serial Number')}}">
                                                        @if ($errors->has('serial_number'))
                                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('serial_number') }}</p>
                                                        @endif
                                                        <p class="text-warning mt-2 mb-0">
                                                            {{ __('The higher the serial number is, the later the slider will be shown.') }}
                                                        </p>
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
                                        <button type="submit" form="sliderVersionForm" class="btn btn-primary">
                                            {{ __('Save') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <!-- Breadcrumb Section -->
                                        <form action="{{ route('user.update_breadcrumb') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="settings-section">
                                                <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
                                                <p class="section-description">{{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}</p>
                                                <div class="form-group">
                                                    <div class="preview-image">
                                                        <img src="{{ isset($information['home_setting']->breadcrumb) ? asset('assets/front/img/user/'.$information['home_setting']->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
                                                    </div>
                                                    <input type="file" id="breadcrumb" name="breadcrumb" class="d-none" accept="image/*">
                                                    <button type="button" class="upload-btn" style="background-color: white;
                                                                        border: 2px dashed #8c9998;
                                                                        color: #0E9384;
                                                                        padding: 1rem;
                                                                        width: 80%;
                                                                        display: flex;
                                                                        flex-direction: column;
                                                                        align-items: center;
                                                                        cursor: pointer;" onclick="document.getElementById('breadcrumb').click()">
                                                        <i class="bi bi-upload mb-2"></i>
                                                        <span>{{ __('Upload Breadcrumb Image') }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    {{ __('Save') }}
                                                </button>
                                            </div>

                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--// end SLIDER  -->
                <!--  -->
            </div>
            @endif
            @if (
                    $userBs->theme == 'home_three' ||
                    $userBs->theme == 'home_four' ||
                    $userBs->theme == 'home_five' ||
                    $userBs->theme == 'home_eleven' ||
                    $userBs->theme == 'home_twelve' ||
                    $userBs->theme == 'home13' ||
                    $userBs->theme == 'home_ten')
           <!-- Banner Section -->
           <div id="banner" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Banner Section') }} </h3>
                <p class="text-muted">{{ __('Upload and configure homepage banners') }}.</p>
                <!--  -->
                <!-- SLIDER -->

                <div class="row">
                    <div class="col-md-12">

                        <div class="card">

                            <div class="card-body pt-5 pb-5">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                    <form id="staticVersionForm"
                                        action="{{ route('user.home_page.hero.update_static_info', ['language' => request()->input('language')]) }}"
                                        method="POST" enctype="multipart/form-data" onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <div class="form-group">
                                                <label for="">{{__('Language')}} **</label>
                                                <select id="language" name="user_language_id" class="form-control">
                                                    <option value="" selected disabled>{{__('Select a language')}}</option>
                                                    @foreach ($userLanguages as $lang)
                                                    <option value="{{$lang->id}}">{{$lang->name}}</option>
                                                    @endforeach
                                                </select>
                                                <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                                            </div>
                                            <!-- upload image sedtion -->

                                            <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="">{{ __('Image*') }}</label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ isset($information['sliders_static']->img) ? asset('assets/front/img/hero_static/'.$information['sliders_static']->img) : asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="img" id="image" class="form-control image">
                                    @if ($errors->has('img'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('img') }}</p>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="">
                                                @if ($userBs->theme == 'home_twelve')
                                                    {{ __('Name*') }}
                                                @else
                                                    {{ __('Title of banner') }}
                                                @endif
                                            </label>
                                            <input type="text" class="form-control" name="title"
                                                value="{{ $data->title ?? '' }}" placeholder="{{ __('Enter title') }}">
                                            @if ($errors->has('title'))
                                                <p class="mt-2 mb-0 text-danger">{{ $errors->first('title') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if ($userBs->theme == 'home_twelve')
                                    <div class="form-group">
                                        <label for="">{{ __('Designation') }} </label>
                                        <input type="text" class="form-control" name="designation"
                                            value="{{ $data->designation ?? '' }}" data-role="tagsinput">
                                        <small
                                            class="text-warning">{{ __('Use comma (,) to seperate the designation.') }}</small>

                                    </div>
                                @endif
                                @if ($userBs->theme !== 'home_twelve')
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="">{{ __('Subtitle*') }}</label>
                                                <input type="text" class="form-control" name="subtitle"
                                                    value="{{ $information['sliders_static']->title ?? '' }}"
                                                    placeholder="{{ __('Enter subtitle') }}">
                                                @if ($errors->has('subtitle'))
                                                    <p class="mt-2 mb-0 text-danger">{{ $errors->first('subtitle') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_eleven')
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="">{{ __('Hero text*') }}</label>
                                                <textarea class="form-control" name="hero_text" placeholder="{{ __('Enter text') }}">{{ $data->hero_text ?? '' }}</textarea>
                                                @if ($errors->has('hero_text'))
                                                    <p class="mt-2 mb-0 text-danger">{{ $errors->first('hero_text') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($userBs->theme != 'home13')
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="btn_name">{{ __('Button Name') }}</label>
                                                <input type="text" class="form-control" name="btn_name"
                                                    value="{{ $data->btn_name ?? '' }}"
                                                    placeholder="{{ __('Enter button name') }}">
                                                @if ($errors->has('btn_name'))
                                                    <p class="mt-2 mb-0 text-danger">{{ $errors->first('btn_name') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($userBs->theme != 'home13')
                                    <div class="form-group">
                                        <label for="url">{{ __('Button URL') }}</label>
                                        <input type="url" class="form-control ltr" name="btn_url"
                                            value="{{ $data->btn_url ?? '' }}"
                                            placeholder="{{ __('Enter button url') }}">
                                        @if ($errors->has('btn_url'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('btn_url') }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if ($userBs->theme == 'home_ten')
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="btn_name">{{ __('Secound Button Name') }}</label>
                                                <input type="text" class="form-control" name="secound_btn_name"
                                                    value="{{ $data->secound_btn_name ?? '' }}"
                                                    placeholder="{{ __('Enter button name') }}">
                                                @if ($errors->has('secound_btn_name'))
                                                    <p class="mt-2 mb-0 text-danger">
                                                        {{ $errors->first('secound_btn_name') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="url">{{ __('Secound Button URL') }}</label>
                                        <input type="url" class="form-control ltr" name="secound_btn_url"
                                            value="{{ $data->secound_btn_url ?? '' }}"
                                            placeholder="{{ __('Enter button url') }}">
                                        @if ($errors->has('secound_btn_url'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('secound_btn_url') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                @if ($userBs->theme == 'home_eleven')
                                    <div class="form-group">
                                        <label for="btn_name">{{ __('Video Button Name') }}</label>
                                        <input type="text" class="form-control" name="secound_btn_name"
                                            value="{{ $data->secound_btn_name ?? '' }}"
                                            placeholder="{{ __('Enter button name') }}">
                                        @if ($errors->has('secound_btn_name'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('secound_btn_name') }}</p>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="url">{{ __('Video URL') }}</label>
                                        <input type="url" class="form-control ltr" name="secound_btn_url"
                                            value="{{ $data->secound_btn_url ?? '' }}"
                                            placeholder="{{ __('Enter button url') }}">
                                        @if ($errors->has('secound_btn_url'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('secound_btn_url') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" form="staticVersionForm" class="btn btn-primary">
                                            {{ __('Save') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <!-- Breadcrumb Section -->
                                        <form action="{{ route('user.update_breadcrumb') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="settings-section">
                                                <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
                                                <p class="section-description">{{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}</p>
                                                <div class="form-group">
                                                    <div class="preview-image">
                                                        <img src="{{ isset($information['home_setting']->breadcrumb) ? asset('assets/front/img/user/'.$information['home_setting']->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
                                                    </div>
                                                    <input type="file" id="breadcrumb" name="breadcrumb" class="d-none" accept="image/*">
                                                    <button type="button" class="upload-btn" style="background-color: white;
                                                                        border: 2px dashed #8c9998;
                                                                        color: #0E9384;
                                                                        padding: 1rem;
                                                                        width: 80%;
                                                                        display: flex;
                                                                        flex-direction: column;
                                                                        align-items: center;
                                                                        cursor: pointer;" onclick="document.getElementById('breadcrumb').click()">
                                                        <i class="bi bi-upload mb-2"></i>
                                                        <span>{{ __('Upload Breadcrumb Image') }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    {{ __('Save') }}
                                                </button>
                                            </div>

                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--// end SLIDER  -->
                <!--  -->
            </div>
            @endif
            <!--  -->

            <!-- Skills Section -->
            <div id="skills" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Skills Section') }}</h3>
                <p class="text-muted">{{ __('Update the skills displayed on the website') }}.</p>
                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                                            @if (!is_null($userDefaultLang))
                                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModalSkill"><i class="fas fa-plus"></i> {{ __('Add Skill') }}</a>
                                            <button class="btn btn-danger mr-2 d-none bulk-delete" data-href="{{ route('user.skill.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                                                {{ __('Delete') }}</button>
                                            @endif

                                            @if(!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value;">
                                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                                @foreach ($userLanguages as $lang)
                                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        @if (is_null($userDefaultLang))
                                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                                        @else
                                        @if (count($information['skills']) == 0)
                                        <h3 class="text-center">{{ __('NO SKILL FOUND') }}</h3>
                                        @else
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3" id="basic-datatables">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            <input type="checkbox" class="bulk-check" data-val="all">
                                                        </th>
                                                        @if ($userBs->theme !== 'home_twelve')
                                                        <th scope="col">{{ __('Icon') }}</th>
                                                        @endif
                                                        <th scope="col">{{ __('Title') }}</th>
                                                        <th scope="col">{{ __('Language') }}</th>
                                                        <th scope="col">{{ __('Percentage') }}</th>
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($information['skills'] as $key => $skill)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="bulk-check" data-val="{{ $skill->id }}">
                                                        </td>
                                                        @if ($userBs->theme !== 'home_twelve')
                                                        <td><i class="{{ $skill->icon ?? 'fa fa-fw fa-heart' }}"></i>
                                                        </td>
                                                        @endif
                                                        <td>{{ strlen($skill->title) > 30 ? mb_substr($skill->title, 0, 30, 'UTF-8') . '...' : $skill->title }}
                                                        </td>
                                                        <td>{{ $skill->language->name }}</td>
                                                        <td>{{ $skill->percentage }}</td>
                                                        <td>
                                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.skill.edit', $skill->id) . '?language=' . $skill->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block" action="{{ route('user.skill.delete') }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="skill_id" value="{{ $skill->id }}">
                                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                    <span class="btn-label">
                                                                        <i class="fas fa-trash"></i>
                                                                    </span>
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!--  -->
            </div>

            <!-- About Company Section -->
            <div id="about" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('About Company') }} </h3>123123
                <p class="text-muted">{{ __('Provide information about your company') }}.</p>
                <!--  -->
                <!-- Home Page About Section -->
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
                                    <h2 class="fs-4 fw-semibold mb-2"> {{ __('About Section')}}</h2>
                                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                        في الصفحة الرئيسية يوجد قسم عن الشركة, يحتوي على معلومات نصيه وصورة, يمكنك التحكم بالمحتوى الخاص به من هنا
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-none">
                                <div class="row">
                                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                                            @if(!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value;">
                                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                                @foreach ($userLanguages as $lang)
                                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pt-5 pb-5">
                                <div class="row">
                                    <div class="col-lg-6 offset-lg-3">000
                                        <form id="ajaxFormAbout" action="{{ route('user.home.page.update.about') }}"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <input type="hidden" name="id" value="{{ $information['home_setting']->id }}">
                                            <input type="hidden" name="language_id" value="{{ $information['home_setting']->language_id }}">

                                            <div class="row">
                                            <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <div class="col-12 mb-2">
                                                                <h3 class="section-title">{{ __('Company Introduction (About Us)') }}</h3>
                                                                <p class="section-description">
                                                                    {{ __('This section contains text and an image about your company. You can control its content here') }}
                                                                </p>
                                                            </div>
                                                            <div class="col-md-12 mb-3 preview-image showAboutImage">
                                                                <img src="{{ isset($information['home_setting']->about_image) ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_image) : asset('assets/admin/img/noimage.jpg') }}" alt="about image" class="img-thumbnail">
                                                            </div>
                                                            <!-- Removed redundant hidden "types[]" inputs -->
                                                            <input type="file" name="about_image" id="about_image" class="d-none form-control ltr">
                                                            <button type="button" class="upload-btn" style="background-color: white; border: 2px dashed #8c9998; color: #0E9384; padding: 1rem; width: 80%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('about_image').click()">
                                                                <i class="bi bi-upload mb-2"></i>
                                                                <span>{{ __('Upload Image') }}</span>
                                                            </button>
                                                            <p id="errabout_image" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>

                                                @if ($userBs->theme == 'home13' || $userBs->theme == 'home15')
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <div class="col-12 mb-2">
                                                            <label for="logo"><strong>{{ __('Image Two') }}</strong></label>
                                                        </div>
                                                        <div class="col-md-12 showAboutImage2 mb-3">
                                                            <img src="{{ $information['home_setting']->about_image_two ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_image_two) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="  img-fluid">
                                                        </div>
                                                        <input type="hidden" name="types[]" value="about_image_two">
                                                        <input type="file" name="about_image_two" id="about_image2" class="form-control ltr">
                                                        <p id="errabout_image_two" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                @endif
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Title') }}</label>
                                                        <input type="hidden" name="types[]" value="about_title">
                                                        <input type="text" class="form-control" name="about_title" value="{{ $information['home_setting']->about_title }}">
                                                        <p id="errabout_title" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                @if ($userBs->theme !== 'home_eleven')
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Subtitle') }}</label>
                                                        <input type="hidden" name="types[]" value="about_subtitle">
                                                        <input type="text" class="form-control" name="about_subtitle" value="{{ $information['home_setting']->about_subtitle }}">
                                                        <p id="errabout_subtitle" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="form-group">
                                                <label for="">{{ __('Content') }}</label>
                                                <input type="hidden" name="types[]" value="about_content">
                                                <textarea class="form-control" name="about_content" rows="5">{{ $information['home_setting']->about_content }}</textarea>
                                                <p id="errabout_content" class="mb-0 text-danger em"></p>
                                            </div>
                                            @if ($userBs->theme === 'home13')
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Years Of Exprience') }}</label>
                                                        <input type="hidden" name="types[]" value="years_of_expricence">
                                                        <input type="number" class="form-control" name="years_of_expricence" value="{{ $information['home_setting']->years_of_expricence }}">
                                                        <p id="erryears_of_expricence" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @if (
                                            (isset($userBs->theme) && !$userBs->theme === 'home_two') ||
                                            $userBs->theme === 'home_eleven' ||
                                            $userBs->theme === 'home15' ||
                                            $userBs->theme === 'home13')
                                            <div class="row">
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Button Text') }}</label>
                                                        <input type="hidden" name="types[]" value="about_button_text">
                                                        <input type="text" class="form-control" name="about_button_text" value="{{ $information['home_setting']->about_button_text }}">
                                                        <p id="errabout_button_text" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Button URL') }}</label>
                                                        <input type="hidden" name="types[]" value="about_button_url">
                                                        <input type="text" class="form-control ltr" name="about_button_url" value="{{ $information['home_setting']->about_button_url }}">
                                                        <p id="errabout_button_url" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @if (isset($userBs->theme) && $userBs->theme === 'home_eleven')
                                            <div class="row">
                                                <div class="col-lg-6 pr-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Secound Button Text') }}</label>
                                                        <input type="hidden" name="types[]" value="about_snd_button_text">
                                                        <input type="text" class="form-control" name="about_snd_button_text" value="{{ $information['home_setting']->about_snd_button_text }}">
                                                        <p id="errabout_snd_button_text" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 pl-0">
                                                    <div class="form-group">
                                                        <label for="">{{ __('Secound Button URL') }}</label>
                                                        <input type="hidden" name="types[]" value="about_snd_button_url">
                                                        <input type="text" class="form-control ltr" name="about_snd_button_url" value="{{ $information['home_setting']->about_snd_button_url }}">
                                                        <p id="errabout_snd_button_url" class="mb-0 text-danger em"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @if (isset($userBs->theme) && $userBs->theme === 'home_two')
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <label for="logo"><strong>{{ __('Video Background Image') }}</strong></label>
                                                </div>
                                                <div class="col-md-12 showAboutVideoImage mb-3">
                                                    <img src="{{ $information['home_setting']->about_video_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_video_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="hidden" name="types[]" value="about_video_image">
                                                <input type="file" name="about_video_image" id="about_video_image" class="form-control ltr">
                                                <p id="errabout_video_image" class="mb-0 text-danger em"></p>
                                            </div>
                                            @endif
                                            @if ((isset($userBs->theme) && $userBs->theme === 'home_two') || $userBs->theme == 'home15')
                                            <div class="form-group">
                                                <label for="">{{ __('Video URL') }}</label>
                                                <input type="hidden" name="types[]" value="about_video_url">
                                                <input type="text" class="form-control ltr" name="about_video_url" value="{{ $information['home_setting']->about_video_url }}">
                                                <p id="errabout_video_url" class="mb-0 text-danger em"></p>
                                            </div>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" id="submitBtnAbout" class="btn btn-success">
                                        {{ __('Update') }} - up
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--  -->


            <!-- Portfolio Section -->
            <div id="portfolio" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Portfolio') }}</h3>
                <p class="text-muted">{{ __('Manage projects and case studies in the portfolio section') }}.</p>

                <!-- portfolio -->

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
                                        <h2 class="fs-4 fw-semibold mb-2">{{ __('Portfolios') }}</h2>
                                        <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                        شارك معرض اعمالك مع العملاء
                                        </p>
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
                                    <div class="col-lg-3 offset-lg-3">
                                        @if (!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                                <select name="userLanguage" class="form-control"
                                                    onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                                    <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                                    @foreach ($userLanguages as $lang)
                                                        <option value="{{ $lang->code }}"
                                                            {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                            {{ $lang->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <form id="ajaxForm" action="{{ route('user.home.page.text.update') }}"
                                        method="post"
                                        enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <input type="hidden" name="id" value="{{  $information['home_setting']->id }}">
                                            <input type="hidden" name="language_id" value="{{  $information['home_setting']->language_id }}">


                                            @if (
                                                !empty($permissions) &&
                                                    in_array('Portfolio', $permissions) &&
                                                    ($userBs->theme == 'home_one' ||
                                                        $userBs->theme == 'home_two' ||
                                                        $userBs->theme == 'home_four' ||
                                                        $userBs->theme == 'home_five' ||
                                                        $userBs->theme == 'home_six' ||
                                                        $userBs->theme == 'home_seven' ||
                                                        $userBs->theme == 'home_twelve' ||
                                                        $userBs->theme == 'home_three'))
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-lg-6 pr-0">
                                                                <div class="form-group">
                                                                    <label for="">{{ __('Portfolio Section Title') }}</label>
                                                                    <input type="hidden" name="types[]" value="portfolio_title">
                                                                    <input type="text" class="form-control" name="portfolio_title"
                                                                        placeholder="{{ __('Enter portfolio title') }}"
                                                                        value="{{  $information['home_setting']->portfolio_title }}">
                                                                    <p id="errportfolio_title" class="mb-0 text-danger em"></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 pl-0">
                                                                <div class="form-group">
                                                                    <label
                                                                        for="">{{ __('Portfolio Section Subtitle') }}</label>
                                                                    <input type="hidden" name="types[]" value="portfolio_subtitle">
                                                                    <input type="text" class="form-control"
                                                                        name="portfolio_subtitle"
                                                                        placeholder="{{ __('Enter Portfolio Subtitle') }}"
                                                                        value="{{  $information['home_setting']->portfolio_subtitle }}">
                                                                    <p id="errportfolio_subtitle" class="mb-0 text-danger em"></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if (isset($userBs->theme) && ($userBs->theme === 'home_two' || $userBs->theme === 'home_three'))
                                                            <div class="row">
                                                                <div class="col-lg-6 pr-0">
                                                                    <div class="form-group">
                                                                        <label
                                                                            for="">{{ __('View All Portfolio Text') }}</label>
                                                                        <input type="hidden" name="types[]"
                                                                            value="view_all_portfolio_text">
                                                                        <input type="text" class="form-control"
                                                                            name="view_all_portfolio_text"
                                                                            placeholder="{{ __('Enter view all portfolio text') }}"
                                                                            value="{{  $information['home_setting']->view_all_portfolio_text }}">
                                                                        <p id="errview_all_portfolio_text"
                                                                            class="mb-0 text-danger em">
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form">
                                    <div class="form-group from-show-notify row">
                                        <div class="col-12 text-center">
                                            <button type="submit" id="submitBtn"
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
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="card-title d-inline-block">{{ __('Portfolios') }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        @if (!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                                <select name="userLanguage" class="form-control"
                                                    onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                                    <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                                    @foreach ($userLanguages as $lang)
                                                        <option value="{{ $lang->code }}"
                                                            {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                            {{ $lang->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                                        @if (!is_null($userDefaultLang))
                                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal"
                                                data-target="#createModalportfolio"><i class="fas fa-plus"></i> {{ __('Add Portfolio') }}</a>
                                                <a class="btn btn-success float-right btn-sm mr-2"
                                                href="{{ route('user.portfolio.category.index') }}"><i
                                                    class="fas fa-hands"></i> {{ __('Add categore') }}</a>
                                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete"
                                                data-href="{{ route('user.portfolio.bulk.delete') }}"><i
                                                    class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        @if (is_null($userDefaultLang))
                                            <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                                        @else
                                            @if (count($information['portfolios']) == 0)
                                                <h3 class="text-center">{{ __('NO PORTFOLIO FOUND') }}</h3>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-striped mt-3" id="basic-datatables">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">
                                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                                </th>
                                                                <th scope="col">{{ __('Image') }}</th>
                                                                <th scope="col">{{ __('Title') }}</th>
                                                                <th scope="col">{{ __('Category') }}</th>
                                                                @if ($userBs->theme != 'home_ten')
                                                                    <th scope="col">{{ __('Featured') }}</th>
                                                                @endif
                                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                                <th scope="col">{{ __('Actions') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($information['portfolios'] as $key => $portfolio)
                                                                <tr>
                                                                    <td>
                                                                        <input type="checkbox" class="bulk-check"
                                                                            data-val="{{ $portfolio->id }}">
                                                                    </td>
                                                                    <td><img src="{{ asset('assets/front/img/user/portfolios/' . $portfolio->image) }}"
                                                                            alt="" width="80"></td>
                                                                    <td>{{ strlen($portfolio->title) > 30 ? mb_substr($portfolio->title, 0, 30, 'UTF-8') . '...' : $portfolio->title }}
                                                                    </td>
                                                                    <td>{{ $portfolio->bcategory->name }}</td>
                                                                    @if ($userBs->theme != 'home_ten')
                                                                        <td>
                                                                            <form id="featureForm{{ $portfolio->id }}"
                                                                                class="d-inline-block"
                                                                                action="{{ route('user.portfolio.featured') }}"
                                                                                method="post"
                                                                                onsubmit="return storeSectionBeforeSubmit(this)">
                                                                                @csrf
                                                                                <input type="hidden" id="lastSection" name="last_section" value="">

                                                                                <input type="hidden" name="portfolio_id"
                                                                                    value="{{ $portfolio->id }}">
                                                                                <select
                                                                                    class="form-control {{ $portfolio->featured == 1 ? 'bg-success' : 'bg-danger' }}"
                                                                                    name="featured"
                                                                                    onchange="document.getElementById('featureForm{{ $portfolio->id }}').submit();">
                                                                                    <option value="1"
                                                                                        {{ $portfolio->featured == 1 ? 'selected' : '' }}>
                                                                                        {{ __('Yes') }}
                                                                                    </option>
                                                                                    <option value="0"
                                                                                        {{ $portfolio->featured == 0 ? 'selected' : '' }}>
                                                                                        {{ __('No') }}
                                                                                    </option>
                                                                                </select>
                                                                            </form>


                                                                        </td>
                                                                    @endif
                                                                    <td>{{ $portfolio->serial_number }}</td>
                                                                    <td>
                                                                        <a class="btn btn-secondary btn-sm"
                                                                            href="{{ route('user.portfolio.edit', $portfolio->id) . '?language=' . $portfolio->language->code }}">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                        <form class="deleteform d-inline-block"
                                                                            action="{{ route('user.portfolio.delete') }}"
                                                                            method="post">
                                                                            @csrf
                                                                            <input type="hidden" name="id"
                                                                                value="{{ $portfolio->id }}">
                                                                            <button type="submit"
                                                                                class="btn btn-danger btn-sm deletebtn">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--// portfolio -->

            </div>

            <!-- Customer Reviews Section -->
            <div id="reviews" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Customer Reviews') }} </h3>
                <p class="text-muted">{{ __('Manage customer testimonials and reviews') }}.</p>

                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                                            @if(!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value;">
                                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                                @foreach ($userLanguages as $lang)
                                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <form id="ajaxFormTestimonialupdate"
                                        action="{{ route('user.home.page.text.update') }}"
                                        method="post"
                                        enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <input type="hidden" name="id" value="{{ $information['home_setting']->id }}">
                                            <input type="hidden" name="language_id" value="{{ $information['home_setting']->language_id }}">


                                            @if (
                                            $userBs->theme != 'home_eight' ||
                                            ($userBs->theme != 'home_ten' && !empty($permissions) && in_array('Testimonial', $permissions)))
                                            <div class="row">
                                                <div class="col-12">

                                                    @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_one' || $userBs->theme == 'home_ten')
                                                    <!-- errtestimonial_image Section -->
                                                    <div class="form-group">
                                                        <div class="col-12 mb-2">
                                                            <label for="logo"><strong>{{ __('Testimonial Image') }}</strong></label>
                                                        </div>
                                                        <div class="col-md-12 preview-image showTestimonialImage mb-3">
                                                            <img src="{{ $information['home_setting']->testimonial_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->testimonial_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                        </div>
                                                        <input type="file" name="testimonial_image" id="testimonial_image" class="d-none">
                                                        <button type="button" class="upload-btn" style="background-color: white;
                                                                                    border: 2px dashed #8c9998;
                                                                                    color: #0E9384;
                                                                                    padding: 1rem;
                                                                                    width: 50%;
                                                                                    display: flex;
                                                                                    flex-direction: column;
                                                                                    align-items: center;
                                                                                    cursor: pointer;" onclick="document.getElementById('testimonial_image').click()">
                                                            <i class="bi bi-upload mb-2"></i>
                                                            <span>{{ __('Upload Favicon') }}</span>
                                                        </button>
                                                        <p id="errtestimonial_image" class="mb-0 text-danger em"></p>
                                                    </div>
                                                    @endif
                                                    @if ($userBs->theme != 'home_ten')
                                                    <div class="row">
                                                        <div class="col-lg-6 pr-0">
                                                            <div class="form-group">
                                                                <label for="">{{ __('Testimonial Section Title') }}</label>
                                                                <input type="hidden" name="types[]" value="testimonial_title">
                                                                <input type="text" class="form-control" name="testimonial_title" placeholder="" value="{{ $information['home_setting']->testimonial_title }}">
                                                                <p id="errtestimonial_title" class="mb-0 text-danger em"></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 pl-0">
                                                            <div class="form-group">
                                                                <label for="">{{ __('Testimonial Section Subtitle') }}</label>
                                                                <input type="hidden" name="types[]" value="testimonial_subtitle">
                                                                <input type="text" class="form-control" name="testimonial_subtitle" placeholder="" value="{{ $information['home_setting']->testimonial_subtitle }}">
                                                                <p id="errtestimonial_subtitle" class="mb-0 text-danger em">
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form">
                                    <div class="form-group from-show-notify row">
                                        <div class="col-12 text-center">
                                            <button type="submit" id="submitBtnTestimonialUpdate" class="btn btn-success">{{ __('Update') }}</button>
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
                                <div class="row">
                                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                                        <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#create_testimonial_Modal"><i class="fas fa-plus"></i> {{ __('Add Testimonial') }}</a>
                                        <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.testimonial.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                    </div>

                                    <div class="col-lg-3">
                                        @if (!is_null($userDefaultLang))
                                        @if (!empty($userLanguages))
                                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                            @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        @if (is_null($userDefaultLang))
                                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                                        @else
                                        @if (count($information['testimonials']) == 0)
                                        <h3 class="text-center">{{ __('NO TESTIMONIAL FOUND') }}</h3>
                                        @else
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3" id="basic-datatables">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            <input type="checkbox" class="bulk-check" data-val="all">
                                                        </th>
                                                        @if ($userBs->theme !== 'home_nine')
                                                        <th scope="col">{{ __('Image') }}</th>
                                                        @endif
                                                        <th scope="col">{{ __('Name') }}</th>
                                                        <th scope="col">{{ __('Publish Date') }}</th>
                                                        <th scope="col">{{ __('Serial Number') }}</th>
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($information['testimonials'] as $key => $testimonial)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="bulk-check" data-val="{{ $testimonial->id }}">
                                                        </td>
                                                        @if ($userBs->theme !== 'home_nine')
                                                        <td><img src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}" alt="" width="80"></td>
                                                        @endif
                                                        <td>{{ strlen($testimonial->name) > 30 ? mb_substr($testimonial->name, 0, 30, 'UTF-8') . '...' : $testimonial->name }}
                                                        </td>
                                                        <td>
                                                            @php
                                                            $date = \Carbon\Carbon::parse($testimonial->created_at);
                                                            @endphp
                                                            {{ $date->translatedFormat('jS F, Y') }}
                                                        </td>
                                                        <td>{{ $testimonial->serial_number }}</td>
                                                        <td>
                                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.testimonial.edit', $testimonial->id) . '?language=' . $testimonial->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block" action="{{ route('user.testimonial.delete') }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="id" value="{{ $testimonial->id }}">
                                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                    <span class="btn-label">
                                                                        <i class="fas fa-trash"></i>
                                                                    </span>
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!--  -->
            </div>

            <!-- Services Section -->
            <div id="services" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Services') }}</h3>
                <p class="text-muted">{{ __('Manage services offered by the company') }}.</p>

                <!--  -->
                <!-- service -->
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
                                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Services') }}</h2>
                                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                        يعتبر قسم صفحة الخدمات من الاقسام المهمة في موقعك الألكتروني
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

                                    <div class="col-lg-3 offset-lg-3">
                                        @if (!is_null($userDefaultLang))
                                        @if (!empty($userLanguages))
                                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                            @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8 offset-lg-2">
                                        <form id="ajaxFormservice"
                                        action="{{ route('user.home.page.text.update') }}"
                                        method="post" enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            <input type="hidden" name="id" value="{{ $information['home_setting']->id }}">
                                            <input type="hidden" name="language_id" value="{{ $information['home_setting']->language_id }}">



                                            @if (
                                            !empty($permissions) &&
                                            in_array('Service', $permissions) &&
                                            ($userBs->theme == 'home_one' ||
                                            $userBs->theme == 'home_two' ||
                                            $userBs->theme == 'home_three' ||
                                            $userBs->theme == 'home_four' ||
                                            $userBs->theme == 'home_five' ||
                                            $userBs->theme == 'home_six' ||
                                            $userBs->theme == 'home_nine' ||
                                            $userBs->theme == 'home_twelve' ||
                                            $userBs->theme == 'home_seven'))
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-lg-6 pr-0">
                                                            <div class="form-group">
                                                                <label for="">{{ __('Service Section Title') }}</label>
                                                                <input type="hidden" name="types[]" value="service_title">
                                                                <input type="text" class="form-control" name="service_title" placeholder="{{ __('Enter service title') }}" value="{{ $information['home_setting']->service_title }}">
                                                                <p id="errservice_title" class="mb-0 text-danger em"></p>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 pl-0">
                                                            <div class="form-group">
                                                                <label for="">{{ __('Service Section Subtitle') }}</label>
                                                                <input type="hidden" name="types[]" value="service_subtitle">
                                                                <input type="text" class="form-control" name="service_subtitle" placeholder="{{ __('Enter service subtitle') }}" value="{{ $information['home_setting']->service_subtitle }}">
                                                                <p id="errservice_subtitle" class="mb-0 text-danger em"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif

                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form">
                                    <div class="form-group from-show-notify row">
                                        <div class="col-12 text-center">
                                            <button type="submit" id="submitBtnservice" class="btn btn-success">{{ __('Update') }}</button>
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
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="card-title d-inline-block">{{ __('Services') }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        @if (!is_null($userDefaultLang))
                                        @if (!empty($userLanguages))
                                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                            <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                            @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @endif
                                        @endif
                                    </div>
                                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                                        @if (!is_null($userDefaultLang))
                                        <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createServiceModal"><i class="fas fa-plus"></i> {{ __('Add Service') }}</a>
                                        <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.service.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        @if (is_null($userDefaultLang))
                                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                                        @else
                                        @if (count($information['services']) == 0)
                                        <h3 class="text-center">{{ __('NO SERVICE FOUND') }}</h3>
                                        @else
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3" id="basic-datatables">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            <input type="checkbox" class="bulk-check" data-val="all">
                                                        </th>
                                                        <th scope="col">{{ __('Image') }}</th>

                                                        @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                                                        <th scope="col">{{ __('Icon') }}</th>
                                                        @endif
                                                        <th scope="col">{{ __('Name') }}</th>
                                                        <th scope="col">{{ __('Language') }}</th>
                                                        @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                                                        @else
                                                        <th scope="col">{{ __('Featured') }}</th>
                                                        @endif
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($information['services'] as $key => $service)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="bulk-check" data-val="{{ $service->id }}">
                                                        </td>
                                                        <td>
                                                            <img src="{{ asset('assets/front/img/user/services/' . $service->image) }}" alt="" width="80">
                                                        </td>
                                                        @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                                                        <td>
                                                            <i class="{{ $service->icon }}"></i>
                                                        </td>
                                                        @endif
                                                        <td>{{ strlen($service->name) > 30 ? mb_substr($service->name, 0, 30, 'UTF-8') . '...' : $service->name }}
                                                        </td>
                                                        <td>{{ $service->language->name }}</td>
                                                        @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                                                        @else
                                                        <td>
                                                            <form id="featureForm{{ $service->id }}" class="d-inline-block"
                                                            action="{{ route('user.service.feature') }}"
                                                            method="post"
                                                            onsubmit="return storeSectionBeforeSubmit(this)">
                                                                @csrf
                                                                <input type="hidden" id="lastSection" name="last_section" value="">

                                                                <input type="hidden" name="service_id" value="{{ $service->id }}">
                                                                <select class="form-control {{ $service->featured == 1 ? 'bg-success' : 'bg-danger' }}" name="featured" onchange="document.getElementById('featureForm{{ $service->id }}').submit();">
                                                                    <option value="1" {{ $service->featured == 1 ? 'selected' : '' }}>
                                                                        {{ __('Yes') }}
                                                                    </option>
                                                                    <option value="0" {{ $service->featured == 0 ? 'selected' : '' }}>
                                                                        {{ __('No') }}
                                                                    </option>
                                                                </select>
                                                            </form>


                                                        </td>
                                                        @endif
                                                        <td>
                                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.service.edit', $service->id) . '?language=' . $service->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block" action="{{ route('user.service.delete') }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="id" value="{{ $service->id }}">
                                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                    <span class="btn-label">
                                                                        <i class="fas fa-trash"></i>
                                                                    </span>
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!--  -->
            </div>

            <!-- Achievements Section -->
            <div id="achievements" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Achievements') }}</h3>
                <p class="text-muted">{{ __('Showcase company awards and achievements') }}</p>

                <!--  -->
                <!-- Edit Achievement Modal -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                                            @if (!is_null($userDefaultLang))
                                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createAchievementModal"><i class="fas fa-plus"></i>
                                                {{ __('Add Counter') }}</a>
                                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.counter-information.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                                                {{ __('Delete') }}
                                            </button>
                                            @endif
                                            @if(!is_null($userDefaultLang))
                                            @if (!empty($userLanguages))
                                            <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value;">
                                                <option value="" selected disabled>{{__('Select a Language')}}</option>
                                                @foreach ($userLanguages as $lang)
                                                <option value="{{$lang->code}}" {{$lang->code == request()->input('language') ? 'selected' : ''}}>{{$lang->name}}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        @if (is_null($userDefaultLang))
                                        <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}
                                        </h3>
                                        @else

                                        @if (count($information['counterInformations']) == 0)
                                        <h3 class="text-center">
                                            {{ __('NO COUNTER INFORMATION FOUND') }}
                                        </h3>
                                        @else
                                        <div class="table-responsive">
                                            <table class="table table-striped mt-3" id="basic-datatables">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">
                                                            <input type="checkbox" class="bulk-check" data-val="all">
                                                        </th>
                                                        @if (
                                                        $userBs->theme != 'home_four' &&
                                                        $userBs->theme != 'home_five' &&
                                                        $userBs->theme != 'home_ten' &&
                                                        $userBs->theme != 'home_twelve')
                                                        <th scope="col">{{ __('Icon') }}</th>
                                                        @else
                                                        @endif
                                                        <th scope="col">{{ __('Title') }}</th>
                                                        <th scope="col">{{ __('Count') }}</th>
                                                        <th scope="col">{{ __('Actions') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($information['counterInformations'] as $key => $counterInformation)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="bulk-check" data-val="{{ $counterInformation->id }}">
                                                        </td>

                                                        @if (
                                                        $userBs->theme != 'home_four' &&
                                                        $userBs->theme != 'home_five' &&
                                                        $userBs->theme != 'home_ten' &&
                                                        $userBs->theme != 'home_twelve')
                                                        <td><i class="{{ $counterInformation->icon ?? 'fa fa-fw fa-heart' }}"></i>
                                                        </td>
                                                        @else
                                                        @endif
                                                        <td>{{ strlen($counterInformation->title) > 30 ? mb_substr($counterInformation->title, 0, 30, 'UTF-8') . '...' : $counterInformation->title }}
                                                        </td>
                                                        <td>{{ $counterInformation->count }}</td>
                                                        <td>
                                                            <a class="btn btn-secondary btn-sm" href="{{ route('user.counter-information.edit', $counterInformation->id) . '?language=' . $counterInformation->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block" action="{{ route('user.counter-information.delete') }}" method="post">
                                                                @csrf
                                                                <input type="hidden" name="counter_information_id" value="{{ $counterInformation->id }}">
                                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                    <span class="btn-label">
                                                                        <i class="fas fa-trash"></i>
                                                                    </span>
                                                                    {{ __('Delete') }}
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--  -->

            </div>

            <!-- Brands Section -->
            <div id="brands" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Brands') }}</h3>
                <p class="text-muted">{{ __('Manage brand logos displayed on the website') }}</p>
                <!--  -->
                <a href="#" data-toggle="modal" data-target="#createModalBrand" class="btn btn-primary"><i class="fas fa-plus"></i>
                    @if ($userBs->theme == 'home_eleven')
                    {{ __('Add Donor') }}
                    @else
                    {{ __('Add Brand') }}
                    @endif
                </a>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            @if (count($information['brands']) == 0)
                            @if ($userBs->theme == 'home_eleven')
                            <h3 class="text-center">{{ __('NO DONOR FOUND!') }}</h3>
                            @else
                            <h3 class="text-center">{{ __('NO BRAND FOUND!') }}</h3>
                            @endif
                            @else
                            <div class="row">
                                @foreach ($information['brands'] as $brand)
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}" alt="brand image" class="w-100">
                                        </div>

                                        <div class="card-footer text-center">
                                            <a class="edit-btn btn btn-secondary btn-sm mr-2" href="#" data-toggle="modal" data-target="#createModalBrand" data-id="{{ $brand->id }}" data-brandimg="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}" data-brand_url="{{ $brand->brand_url }}" data-serial_number="{{ $brand->serial_number }}">
                                                <span class="btn-label">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                {{ __('Edit') }}
                                            </a>

                                            <form class="deleteform d-inline-block" action="{{ route('user.home_page.brand_section.delete_brand') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                                <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                    <span class="btn-label">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <!--  -->
            </div>

        <!-- Footer Section -->
            <div id="footer" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('Footer') }}</h3>
                <p class="text-muted">{{ __('Edit footer content and social media links') }}</p>

                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-3 float-left">
                                        @if (!is_null($userDefaultLang))
                                        @if (!empty($userLanguages))
                                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}' + this.value;">
                                            <option value="" selected disabled>Select a Language</option>
                                            @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pt-5 pb-5">
                                <div class="row">
                                    <div class="col-lg-6 offset-lg-3">
                                        <form id="ajaxFormFooter"
                                        action="{{ route('user.footer.update_footer_info_quicklink', ['language' => request()->input('language')]) }}"
                                        method="post"
                                        enctype="multipart/form-data"
                                        onsubmit="return storeSectionBeforeSubmit(this)">
                                            @csrf
                                            <input type="hidden" id="lastSection" name="last_section" value="">

                                            @if ($userBs->theme == 'home_ten')
                                            <div class="form-group">
                                                <label for="">{{ __('Footer Color') . ' *' }}</label>
                                                <input type="text" class="form-control jscolor" name="color" value="{{ isset($information['footertext']) ? $information['footertext']->footer_color : '' }}" required>
                                                <p id="errcolor" class="mb-0 text-danger em"></p>
                                            </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="">{{ __('Footer Logo*') }}</label> <br>
                                                <div class="col-md-12 showImage mb-3">
                                                    <img src="{{ isset($information['footertext']) ? asset('assets/front/img/user/footer/' . $information['footertext']->logo) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="file" name="logo" id="logo" class=" d-none form-control image">
                                                <p id="errlogo" class="em text-danger mt-2 mb-0"></p>
                                                <button type="button" class="upload-btn" style="background-color: white; border: 2px dashed #8c9998; color: #0E9384; padding: 1rem; width: 50%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('logo').click()">
                                                    <i class="bi bi-upload mb-2"></i>
                                                    <span>{{ __('Upload Favicon') }}</span>
                                                </button>
                                            </div>
                                            @if ($userBs->theme == 'home_six' || $userBs->theme == 'home13')
                                            <div class="form-group">
                                                <label for="">{{ __('Footer Background*') }}</label> <br>
                                                <div class="col-md-12 showImage mb-3">
                                                    <img src="{{ isset($information['footertext']) ? asset('assets/front/img/user/footer/' . $information['footertext']->bg_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                                </div>
                                                <input type="file" id="bg_image" name="bg_image" class=" d-none form-control image">
                                                <p id="errbg_image" class="em text-danger mt-2 mb-0"></p>
                                                <button type="button" class="upload-btn" style="background-color: white; border: 2px dashed #8c9998; color: #0E9384; padding: 1rem; width: 50%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('bg_image').click()">
                                                    <i class="bi bi-upload mb-2"></i>
                                                    <span>{{ __('Upload Favicon') }}</span>
                                                </button>
                                            </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="">{{ __('About Company') }}</label>
                                                <textarea class="form-control" name="about_company" rows="3" cols="80">{{ isset($information['footertext']) ? $information['footertext']->about_company : '' }}</textarea>
                                                <p id="errabout_company" class="em text-danger mt-2 mb-0"></p>
                                            </div>
                                            @if ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_seven')
                                            <div class="form-group">
                                                <label for="">{{ __('Newsletter Text') }}</label>
                                                <textarea class="form-control" name="newsletter_text" rows="3" cols="80">{{ isset($information['footertext']) ? $information['footertext']->newsletter_text : '' }}</textarea>
                                                <p id="errnewsletter_text" class="em text-danger mt-2 mb-0"></p>
                                            </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="">{{ __('Copyright Text') }}</label>
                                                <textarea id="copyrightSummernote" class="form-control summernote" name="copyright_text" data-height="80">{{ isset($information['footertext']) ? replaceBaseUrl($information['footertext']->copyright_text) : '' }}</textarea>
                                                <p id="errcopyright_text" class="em text-danger mb-0"></p>
                                            </div>

                                            <!-- footer_quick_links -->

                                            <!--// footer_quick_links -->
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" id="submitBtnFooter" class="btn btn-success">
                                            {{ __('Update') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--  -->
                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                        <div class="row">

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a
                                href="#"
                                class="btn btn-sm btn-primary float-lg-right float-left"
                                data-toggle="modal"
                                data-target="#createModalQuick_links"
                            ><i class="fas fa-plus"></i> {{ __('Add') }}</a>
                            </div>
                        </div>
                        </div>

                        <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                            @if (count($information['footer_quick_links']) == 0)
                                <h3 class="text-center">{{ __('NO QUICK LINK FOUND!') }}</h3>
                            @else
                                <div class="table-responsive">
                                <table class="table table-striped mt-3">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ __('#') }}</th>
                                        <th scope="col">{{ __('Title') }}</th>
                                        <th scope="col">{{ __('URL') }}</th>
                                        <th scope="col">{{ __('Serial Number') }}</th>
                                        <th scope="col">{{ __('Actions') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($information['footer_quick_links'] as $link)
                                        <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $link->title }}</td>
                                        <td>{{ $link->url }}</td>
                                        <td>{{ $link->serial_number }}</td>
                                        <td>
                                            <a
                                            class="edit-btn btn btn-secondary btn-sm mr-1"
                                            href="#"
                                            data-toggle="modal"
                                            data-target="#editModalquick_links"
                                            data-id="{{ $link->id }}"
                                            data-title="{{ $link->title }}"
                                            data-url="{{ $link->url }}"
                                            data-serial_number="{{ $link->serial_number }}"
                                            >
                                            <span class="btn-label">
                                                <i class="fas fa-edit"></i>
                                            </span>
                                            {{ __('Edit') }}
                                            </a>

                                            <form
                                            class="deleteform d-inline-block"
                                            action="{{ route('user.footer.delete_quick_link') }}"
                                            method="post"
                                            >
                                            @csrf
                                            <input type="hidden" name="link_id" value="{{ $link->id }}">
                                            <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                <span class="btn-label">
                                                <i class="fas fa-trash"></i>
                                                </span>
                                                {{ __('Delete') }}
                                            </button>
                                            </form>
                                        </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                </div>
                            @endif
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
                <!--  -->

                <!--  -->
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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Social Links') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                  تعديل روابط الاجتماعية الخاصه بشركتك من هنا
                </p>
            </div>
        </div>
    </div>
    </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form id="socialForm" action="{{ route('user.social.store') }}" method="post">
                    <div class="card-header">
                        <div class="card-title">{{ __('Add Social Link') }}</div>
                    </div>
                    <div class="card-body pt-5 pb-5">
                        <div class="row">
                            <div class="col-lg-12">
                                @csrf
                                <div class="form-group">
                                    <label for="">{{ __('Social Icon') }} **</label>
                                    <div class="btn-group d-block">
                                        <button type="button" class="btn btn-primary iconpicker-component"><i
                                                class="fa fa-fw fa-heart"></i></button>
                                        <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                            data-selected="fa-car" data-toggle="dropdown">
                                        </button>
                                        <div class="dropdown-menu"></div>
                                    </div>
                                    <input id="inputIcon" type="hidden" name="icon" value="">
                                    @if ($errors->has('icon'))
                                        <p class="mb-0 text-danger">{{ $errors->first('icon') }}</p>
                                    @endif
                                    <div class="mt-2">
                                        <small>{{ __('NB: click on the dropdown icon to select a social link icon.') }}</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('URL') }} **</label>
                                    <input type="text" class="form-control" name="url" value=""
                                        placeholder="Enter URL of social media account">
                                    @if ($errors->has('url'))
                                        <p class="mb-0 text-danger">{{ $errors->first('url') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') }} **</label>
                                    <input type="number" class="form-control ltr" name="serial_number" value=""
                                        placeholder="Enter Serial Number">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning">
                                        <small>{{ __('The higher the serial number is, the later the social link will be shown.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer pt-3">
                        <div class="form">
                            <div class="form-group from-show-notify row">
                                <div class="col-lg-3 col-md-3 col-sm-12">

                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" id="displayNotif"
                                        class="btn btn-success">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ __('Social Links') }}</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($information['socials']) == 0)
                                <h2 class="text-center">{{ __('NO LINK ADDED') }}</h2>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">{{ __('Icon') }}</th>
                                                <th scope="col">{{ __('URL') }}</th>
                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($information['socials'] as $key => $social)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td><i class="{{ $social->icon }}"></i></td>
                                                    <td>{{ $social->url }}</td>
                                                    <td>{{ $social->serial_number }}</td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm"
                                                            href="{{ route('user.social.edit', $social->id) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form class="d-inline-block deleteform"
                                                            action="{{ route('user.social.delete') }}" method="post">
                                                            @csrf
                                                            <input type="hidden" name="socialid"
                                                                value="{{ $social->id }}">
                                                            <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                <!--  -->

            </div>
        <!--// Footer Section -->

            <!-- menu-builder Section -->
            <div id="menubuilder" class="content-section d-none">
                <h3 class="h4 font-weight-bold">{{ __('menu biulder') }}</h3>
                <p class="text-muted">{{ __('Edit menu biulder  content ') }}</p>

            <!--  -->

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
                                <h2 class="fs-4 fw-semibold mb-2">{{ __('Menu Builder') }}</h2>
                                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                    يمكنك تعديل القوائم في موقعك من هنا
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-none">
                            <div class="row">
                                <div class="col-lg-2">
                                    @if (!is_null($userDefaultLang))
                                    @if (!empty($userLanguages))
                                    <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                        <option value="" selected disabled>{{ __('Select a Language') }}</option>
                                        @foreach ($userLanguages as $lang)
                                        <option value="{{ $lang->code }}" {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                            {{ $lang->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-5 pb-5">
                            <div class="row no-gutters">
                                <div class="col-lg-4">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">{{ __('Pre-built Menus') }}</div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-home"></i>
                                                    @endif
                                                    {{ $keywords['Home'] ?? 'Home' }} <a data-text="{{ $keywords['Home'] ?? 'Home' }}" data-type="home" @if ($userBs->theme == 'home_twelve') data-icon="fas fa-home" @endif
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>

                                                @if (!empty($permissions) && in_array('Service', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-hands"></i>
                                                    @endif
                                                    {{ $keywords['Services'] ?? 'Services' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hands" @endif
                                                        data-text="{{ $keywords['Services'] ?? 'Services' }}"
                                                        data-type="services"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif
                                                @if (!empty($permissions) && in_array('Hotel Booking', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-hotel"></i>
                                                    @endif
                                                    {{ $keywords['Rooms'] ?? 'Rooms' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hotel" @endif
                                                        data-text="{{ $keywords['Rooms'] ?? 'Rooms' }}" data-type="rooms"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif
                                                @if (!empty($permissions) && in_array('Course Management', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-play"></i>
                                                    @endif
                                                    {{ $keywords['Courses'] ?? 'Courses' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-play" @endif
                                                        data-text="{{ $keywords['Courses'] ?? 'Courses' }}" data-type="courses"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif
                                                @if (!empty($permissions) && in_array('Donation Management', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-hand-holding-usd"></i>
                                                    @endif
                                                    {{ $keywords['Causes'] ?? 'Causes' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-hand-holding-usd" @endif
                                                        data-text="{{ $keywords['Causes'] ?? 'Causes' }}" data-type="causes"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif
                                                @if (!empty($permissions) && in_array('Blog', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-blog"></i>
                                                    @endif
                                                    {{ $keywords['Blog'] ?? 'Blog' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-blog" @endif
                                                        data-text="{{ $keywords['Blog'] ?? 'Blog' }}" data-type="blog"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif

                                                @if (!empty($permissions) && in_array('Portfolio', $permissions))
                                                <li class="list-group-item">{{ $keywords['Portfolios'] ?? 'Portfolios' }} <a data-text="{{ $keywords['Portfolios'] ?? 'Portfolios' }}" data-type="portfolios" class="addToMenus btn btn-primary btn-sm float-right" href="">{{ __('Add to Menus') }}</a></li>
                                                @endif

                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                    @endif
                                                    {{ $keywords['Contact'] ?? 'Contact' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-chalkboard-teacher" @endif
                                                        data-text="{{ $keywords['Contact'] ?? 'Contact' }}" data-type="contact"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>

                                                @if (!empty($permissions) && in_array('Team', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-user-friends"></i>
                                                    @endif
                                                    {{ $keywords['Team'] ?? 'Team' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-user-friends" @endif
                                                        data-text="{{ $keywords['Team'] ?? 'Team' }}" data-type="team"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif

                                                @if (!empty($permissions) && in_array('Career', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="fas fa-user-md"></i>
                                                    @endif
                                                    {{ $keywords['Career'] ?? 'Career' }} <a @if ($userBs->theme == 'home_twelve') data-icon="fas fa-user-md" @endif
                                                        data-text="{{ $keywords['Career'] ?? 'Career' }}" data-type="career"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif

                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="far fa-question-circle"></i>
                                                    @endif
                                                    {{ $keywords['FAQ'] ?? 'FAQ' }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-question-circle" @endif
                                                        data-text="{{ $keywords['FAQ'] ?? 'FAQ' }}" data-type="faq"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @if (!empty($permissions) && in_array('Ecommerce', $permissions))
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="far fa-store-alt"></i>
                                                    @endif
                                                    {{ $keywords['Shop'] ?? 'Shop' }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-store-alt" @endif
                                                        data-text="{{ $keywords['Shop'] ?? 'Shop' }}" data-type="shop"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="far fa-cart-plus"></i>
                                                    @endif
                                                    {{ $keywords['Cart'] ?? 'Cart' }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-cart-plus" @endif
                                                        data-text="{{ $keywords['Cart'] ?? 'Cart' }}" data-type="cart"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                <li class="list-group-item">
                                                    @if ($userBs->theme == 'home_twelve')
                                                    <i class="far fa-cart-plus"></i>
                                                    @endif
                                                    {{ $keywords['Checkout'] ?? 'Checkout' }} <a @if ($userBs->theme == 'home_twelve') data-icon="far fa-cart-plus" @endif
                                                        data-text="{{ $keywords['Checkout'] ?? 'Checkout' }}"
                                                        data-type="checkout"
                                                        class="addToMenus btn btn-primary btn-sm float-right"
                                                        href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endif
                                                @if (!empty($permissions) && in_array('Custom Page', $permissions))
                                                @foreach ($apages as $apage)
                                                <li class="list-group-item">
                                                    {{ $apage->name }} <span class="badge badge-primary"> {{ __('Custom Page') }}</span>
                                                    <a data-text="{{ $apage->name }}" data-type="{{ $apage->id }}" data-custom="yes" class="addToMenus btn btn-primary btn-sm float-right" href="">{{ __('Add to Menus') }}</a>
                                                </li>
                                                @endforeach
                                                @endif


                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">{{ __('Add / Edit Menu') }}</div>
                                        <div class="card-body">
                                            <form id="frmEdit" class="form-horizontal">
                                                <input class="item-menu" type="hidden" name="type" value="">
                                                @if ($userBs->theme == 'home_twelve')
                                                <div class="form-group">
                                                    <label for="">{{ __('Icon*') }}</label>
                                                    <div class="btn-group d-block">
                                                        <button type="button" class="btn btn-primary iconpicker-component">
                                                            <i class="fas fa heart"></i>
                                                        </button>
                                                        <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                                                        <div class="dropdown-menu"></div>
                                                    </div>

                                                    <input type="hidden" id="inputIcon" class="item-menu" name="icon">

                                                    <div class="text-warning mt-2">
                                                        <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                                                    </div>
                                                </div>
                                                @endif
                                                <div id="withUrl">

                                                    <div class="form-group">
                                                        <label for="text">{{ __('Text') }}</label>
                                                        <input type="text" class="form-control item-menu" name="text" placeholder="{{ __('Text') }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="href">{{ __('URL') }}</label>
                                                        <input type="text" class="form-control item-menu" name="href" placeholder="{{ __('URL') }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="target">{{ __('Target') }}</label>
                                                        <select name="target" id="target" class="form-control item-menu">
                                                            <option value="_self">{{ __('Self') }}</option>
                                                            <option value="_blank">{{ __('Blank') }}</option>
                                                            <option value="_top">{{ __('Top') }}</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div id="withoutUrl" style="display: none;">
                                                    <div class="form-group">
                                                        <label for="text">{{ __('Text') }}</label>
                                                        <input type="text" class="form-control item-menu" name="text" placeholder="{{ __('Text') }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="href">{{ __('URL') }}</label>
                                                        <input type="text" class="form-control item-menu" name="href" placeholder="{{ __('URL') }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="target">{{ __('Target') }}</label>
                                                        <select name="target" class="form-control item-menu">
                                                            <option value="_self">{{ __('Self') }}</option>
                                                            <option value="_blank">{{ __('Blank') }}</option>
                                                            <option value="_top">{{ __('Top') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="card-footer">
                                            <button type="button" id="btnUpdate" class="btn btn-primary" disabled><i class="fas fa-sync-alt"></i> {{ __('Update') }}</button>
                                            <button type="button" id="btnAdd" class="btn btn-success"><i class="fas fa-plus"></i> {{ __('Add') }}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">{{ __('Website Menus') }}</div>
                                        <div class="card-body">
                                            <ul id="myEditor" class="sortableLists list-group">
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer pt-3">
                            <div class="form">
                                <div class="form-group from-show-notify row">
                                    <div class="col-12 text-center">
                                        <button id="btnOutput" class="btn btn-success">{{ __('Update Menu') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--  -->

            </div>
            <!--// menu-builder Section -->

        </main>
    </div>



    <!-- Create Portfolio Modal -->
    <div class="modal fade" id="createModalportfolio" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Portfolio') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    {{-- Slider images upload start --}}
                    <div class="px-2">
                        <label for="" class="mb-2"><strong>{{ __('Slider Images') }} **</strong></label>
                        <form action="{{ route('user.portfolio.sliderstore') }}" id="my-dropzone"
                            enctype="multipart/form-data" class="dropzone create">
                            @csrf
                        </form>
                        <p class="text-warning">{{ __('Only png, jpg, jpeg images are allowed') }}</p>
                        <p class="em text-danger mb-0" id="errslider_images"></p>
                    </div>
                    {{-- Slider images upload end --}}

                    <form id="ajaxFormPortfolio"
                    enctype="multipart/form-data"
                    class="modal-form"
                    action="{{ route('user.portfolio.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div id="sliders"></div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Thumbnail') }} **</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..."
                                            class="img-thumbnail">
                                    </div>
                                    <input type="file" name="image" id="image" class="form-control">
                                    <p id="errimage" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Language') }} **</label>
                                    <select id="language" name="user_language_id" class="form-control">
                                        <option value="" selected disabled>{{ __('Select a language') }}</option>
                                        @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                        @endforeach
                                    </select>
                                    <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Title') }} **</label>
                                    <input type="text" class="form-control" name="title" value="">
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Category') }} **</label>
                                    <select id="pcategory" class="form-control" name="category" disabled>
                                        <option value="" selected disabled>{{ __('Select a category') }}</option>
                                    </select>
                                    <p id="errcategory" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') }} **</label>
                                    <input type="number" class="form-control ltr" name="serial_number" value="">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning mb-0">
                                        <small>{{ __('The higher the serial number is, the later the portfolio will be shown.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="status">{{ __('Status*') }}</label>
                                    <select name="status" id="status" class="form-control">
                                        <option selected disabled>{{ __('Select a Status') }}</option>
                                        <option value="0">{{ __('In Progress') }}</option>
                                        <option value="1">{{ __('Completed') }}</option>
                                    </select>
                                    <p id="errstatus" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Client Name') }}</label>
                                    <input type="text" class="form-control" name="client_name">
                                    <p id="errclient_name" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Start Date') }}</label>
                                    <input type="date" class="form-control" name="start_date">
                                    <p id="errstart_date" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Submission Date') }}</label>
                                    <input type="date" class="form-control" name="submission_date">
                                    <p id="errsubmission_date" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="">{{ __('Website Link') }}</label>
                                    <input type="text" class="form-control" name="website_link">
                                    <p id="errwebsite_link" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Content') }} **</label>
                            <textarea class="form-control summernote" name="content" rows="8" cols="80"></textarea>
                            <p id="errcontent" class="mb-0 text-danger em"></p>
                        </div>

                        @if ($userBs->theme != 'home_ten')
                            <div class="form-group">
                                <label for="featured" class="my-label mr-3">{{ __('Featured') }}</label>
                                <input id="featured" type="checkbox" name="featured" value="1">
                                <p id="errfeatured" class="mb-0 text-danger em"></p>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="">{{ __('Meta Keywords') }}</label>
                            <input type="text" class="form-control" name="meta_keywords" value=""
                                data-role="tagsinput">
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Meta Description') }}</label>
                            <textarea type="text" class="form-control" name="meta_description" rows="5"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnPortfolio" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Create portfolio Category Modal -->
    <div class="modal fade" id="createModalportfolioCategory" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Portfolio Category') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxFormPortfolioCategory"
                    class="modal-form create"
                    action="{{ route('user.portfolio.category.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div class="form-group">
                            <label for="">{{ __('Language') }} **</label>
                            <select name="user_language_id" class="form-control">
                                <option value="" selected disabled>{{ __('Select a language') }}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                            <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Name') }} **</label>
                            <input type="text" class="form-control" name="name" value="">
                            <p id="errname" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Status') }} **</label>
                            <select class="form-control ltr" name="status">
                                <option value="" selected disabled>{{ __('Select a status') }}</option>
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Deactive') }}</option>
                            </select>
                            <p id="errstatus" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Serial Number') }} **</label>
                            <input type="number" class="form-control ltr" name="serial_number" value="">
                            <p id="errserial_number" class="mb-0 text-danger em"></p>
                            <p class="text-warning">
                                <small>{{ __('The higher the serial number is, the later the portfolio category will be shown.') }}</small>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnportfolioCategory" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit portfolio Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit Portfolio Category') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxEditForm"
                    action="{{ route('user.portfolio.category.update') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <input id="inbcategory_id" type="hidden" name="bcategory_id" value="">
                        <div class="form-group">
                            <label for="">{{ __('Name') }} **</label>
                            <input id="inname" type="name" class="form-control" name="name" value="">
                            <p id="eerrname" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Status') }} **</label>
                            <select id="instatus" class="form-control ltr" name="status">
                                <option value="" selected disabled>{{ __('Select a status') }}</option>
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Deactive') }}</option>
                            </select>
                            <p id="eerrstatus" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Serial Number') }} **</label>
                            <input id="inserial_number" type="number" class="form-control ltr" name="serial_number" value="">
                            <p id="eerrserial_number" class="mb-0 text-danger em"></p>
                            <p class="text-warning">
                                <small>{{ __('The higher the serial number is, the later the blog category will be shown.') }}</small>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="updateBtn" type="button" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Create Service Modal -->
    <div class="modal fade" id="createServiceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Service') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxFormServices"
                    enctype="multipart/form-data"
                    class="modal-form"
                    action="{{ route('user.service.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Image') }}*</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="image" id="image" class="form-control">
                                    <p id="errimage" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Language') }} **</label>
                            <select name="user_language_id" class="form-control">
                                <option value="" selected disabled>{{ __('Select a language') }}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                            <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                        </div>
                        @if ($userBs->theme === 'home_six' || $userBs->theme === 'home_seven' || $userBs->theme === 'home_nine')
                        <div class="form-group">
                            <label for="">{{ __('Service Icon') }} **</label>
                            <div class="btn-group d-block">
                                <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                                <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown">
                                </button>
                                <div class="dropdown-menu"></div>
                            </div>
                            <input id="inputIcon" type="hidden" name="icon" value="">
                            @if ($errors->has('icon'))
                            <p class="mb-0 text-danger">{{ $errors->first('icon') }}</p>
                            @endif
                            <div class="text-warning mt-2">
                                <small>{{ __('NB: click on the dropdown icon to select a service icon.') }}</small>
                            </div>
                            <p id="erricon" class="mb-0 text-danger em"></p>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="">{{ __('Name') }} **</label>
                            <input type="text" class="form-control" name="name" value="">
                            <p id="errname" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Content') }}</label>
                            <textarea class="form-control summernote" name="content" rows="8" cols="80"></textarea>
                            <p id="errcontent" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Serial Number') }} **</label>
                            <input type="number" class="form-control ltr" name="serial_number" value="">
                            <p id="errserial_number" class="mb-0 text-danger em"></p>
                            <p class="text-warning mb-0">
                                <small>{{ __('The higher the serial number is, the later the service will be shown.') }}</small>
                            </p>
                        </div>
                        @if (
                        $userBs->theme != 'home_nine' ||
                        $userBs->theme != 'home_ten' ||
                        $userBs->theme != 'home_eleven' ||
                        $userBs->theme != 'home_twelve')
                        @else
                        <div class="form-group">
                            <label for="featured" class="my-label mr-3">{{ __('Featured') }}</label>
                            <input id="featured" type="checkbox" name="featured" value="1">
                            <p id="errfeatured" class="mb-0 text-danger em"></p>
                        </div>
                        @endif
                        <div class="form-group">
                            <div class="d-flex">
                                <label class="mr-3">{{ __('Detail Page') }}</label>
                                <div class="radio mr-3">
                                    <label><input type="radio" name="detail_page" value="1" checked class="mr-1">{{ __('Enable') }}</label>
                                </div>
                                <div class="radio">
                                    <label><input type="radio" name="detail_page" value="0" class="mr-1">{{ __('Disable') }}</label>
                                </div>
                            </div>
                            <p id="errdetail_page" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Meta Keywords') }}</label>
                            <input type="text" class="form-control" name="meta_keywords" value="" data-role="tagsinput">
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Meta Description') }}</label>
                            <textarea type="text" class="form-control" name="meta_description" rows="5"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnServices" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Create Achievement Modal -->
    <div class="modal fade" id="createAchievementModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Achievement') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ajaxFormAchievement"
                    enctype="multipart/form-data"
                    class="modal-form"
                    action="{{ route('user.counter-information.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div class="form-group">
                            <label for="">{{ __('Language') }} **</label>
                            <select id="language" name="user_language_id" class="form-control">
                                <option value="" selected disabled>
                                    {{ __('Select a language') }}
                                </option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                            <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                        </div>
                        @if ($userBs->theme != 'home_ten' && $userBs->theme != 'home_twelve')
                        <div class="form-group">
                            <label for="">{{ __('Icon') . '*' }}</label>
                            <div class="btn-group d-block">
                                <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                                <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                                <div class="dropdown-menu"></div>
                            </div>
                            <input type="hidden" id="inputIcon" name="icon">
                            <p id="err_icon" class="mt-1 mb-0 text-danger em"></p>
                            <div class="text-warning mt-2">
                                <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Title') }} **</label>
                                    <input type="text" class="form-control" name="title" placeholder="{{ __('Enter title') }}" value="">
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="count">{{ __('Count') }}**</label>
                                    <input id="count" type="number" class="form-control ltr" name="count" value="" placeholder="{{ __('Enter achievement count') }}" min="1">
                                    <p id="errcount" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') }}
                                        **</label>
                                    <input type="number" class="form-control ltr" name="serial_number" value="" placeholder="{{ __('Enter Serial Number') }}">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning mb-0">
                                        <small>{{ __('The higher the serial number is, the later the Skill will be shown.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnAchievement" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!--  -->
    <!-- Create Add Testimonial Modal -->
    <div class="modal fade" id="create_testimonial_Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Testimonial') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Testimonial -->
                    <form id="ajaxFormTestimonial"
                    enctype="multipart/form-data"
                    class="modal-form"
                    action="{{ route('user.testimonial.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        @if ($userBs->theme !== 'home_nine')
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Image') }}*</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="image" id="image" class="form-control">
                                    <p id="errimage" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="">{{ __('Language') }} **</label>
                            <select name="user_language_id" class="form-control">
                                <option value="" selected disabled>{{ __('Select a language') }}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                            <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                        </div>
                        <div class="form-group">
                            <label for="">{{ __('Name') }} **</label>
                            <input type="text" class="form-control" name="name" value="">
                            <p id="errname" class="mb-0 text-danger em"></p>
                        </div>
                        @if ($userBs->theme !== 'home_nine')
                        <div class="form-group">
                            <label for="">{{ __('Occupation') }}</label>
                            <input type="text" class="form-control" name="occupation" value="">
                            <p id="erroccupation" class="mb-0 text-danger em"></p>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="">{{ __('Feedback') }} **</label>
                            <textarea class="form-control " name="content" rows="5"></textarea>
                            <p id="errcontent" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Serial Number') }} **</label>
                            <input type="number" class="form-control ltr" name="serial_number" value="">
                            <p id="errserial_number" class="mb-0 text-danger em"></p>
                            <p class="text-warning mb-0">
                                <small>{{ __('The higher the serial number is, the later the blog will be shown.') }}</small>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><!-- Testimonial -->
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnTestimonial" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    <!-- Create Skill Modal -->
    <div class="modal fade" id="createModalSkill" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Skill') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- skills -->
                    <form id="ajaxFormSkill"
                    enctype="multipart/form-data"
                    class="modal-form"
                    action="{{ route('user.skill.store') }}"
                    method="POST"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div class="form-group">
                            <label for="">{{ __('Language') }} **</label>
                            <select id="language" name="user_language_id" class="form-control">
                                <option value="" selected disabled>{{ __('Select a language') }}</option>
                                @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                                @endforeach
                            </select>
                            <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                        </div>
                        @if ($userBs->theme !== 'home_twelve')
                        <div class="form-group">
                            <label for="">{{ __('Icon*') }}</label>
                            <div class="btn-group d-block">
                                <button type="button" class="btn btn-primary iconpicker-component"><i class="fa fa-fw fa-heart"></i></button>
                                <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                                <div class="dropdown-menu"></div>
                            </div>
                            <input type="hidden" id="inputIcon" name="icon">
                            <p id="erricon" class="mt-1 mb-0 text-danger em"></p>
                            <div class="text-warning mt-2">
                                <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                            </div>
                        </div>
                        @endif
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Title') }} **</label>
                                    <input type="text" class="form-control" name="title" value="">
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="percentage">{{ __('Percentage') }}**</label>
                                    <input id="percentage" type="number" class="form-control ltr" name="percentage" value="" min="1" max="100" onkeyup="if(parseInt(this.value)>100 || parseInt(this.value)<=0 ){this.value =100; return false;}">
                                    <p id="errpercentage" class="mb-0 text-danger em"></p>
                                    <p class="text-warning mb-0">
                                        <small>{{ __('The percentage should between 1 to 100.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Color') }} **</label>
                                    <input type="text" name="color" value="#F78058" class="form-control jscolor ltr">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') }} **</label>
                                    <input type="number" class="form-control ltr" name="serial_number" value="">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning mb-0">
                                        <small>{{ __('The higher the serial number is, the later the Skill will be shown.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtnSkill" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- create brand model  -->
    <div class="modal fade" id="createModalBrand" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">
                        @if ($userBs->theme == 'home_eleven')
                        {{ __('Add Donor') }}
                        @else
                        {{ __('Add Brand') }}
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="ajaxFormBrand"
                    class="modal-form"
                    action="{{ route('user.home_page.brand_section.store_brand') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    onsubmit="return storeSectionBeforeSubmit(this)">
                        @csrf
                        <input type="hidden" id="lastSection" name="last_section" value="">

                        <div class="form-group">
                            <div class="col-12 mb-2">
                                <label for="image"><strong>{{ __('Image') }}</strong></label>
                            </div>
                            <div class="col-md-12 showImage mb-3">
                                <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                            </div>
                            <input type="file" name="brand_img" id="image" class="form-control image">
                            <p id="errbrand_img" class="mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group d-none">
                            @if ($userBs->theme == 'home_eleven')
                            <label for="">{{ __('Donor\'s URL*') }}</label>
                            @else
                            <label for="">{{ __('Brand\'s URL*') }}</label>
                            @endif
                            <input type="url" class="form-control ltr" value="#" name="brand_url" placeholder="{{ __('Enter Brand URL') }}">
                            <p id="errbrand_url" class="mt-2 mb-0 text-danger em"></p>
                        </div>

                        <div class="form-group">
                            <label for="">{{ __('Serial Number*') }}</label>
                            <input type="number" class="form-control ltr" name="serial_number" placeholder="{{ __('Enter Serial Number') }}">
                            <p id="errserial_number" class="mt-2 mb-0 text-danger em"></p>
                            <p class="text-warning mt-2">
                                <small>{{ __('The higher the serial number is, the later the brand will be shown.') }}</small>
                            </p>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                    <button id="submitBtnBrand" type="button" class="btn btn-primary">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- create Quick_links Modal -->
    <div
    class="modal fade"
    id="createModalQuick_links"
    tabindex="-1"
    role="dialog"
    aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Quick Links') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form
                id="ajaxFormQuick_links"
                class="modal-form"
                action="{{ route('user.footer.store_quick_link') }}"
                method="post"
                >
                @csrf
                <div class="form-group">
                    <label for="">{{__('Language')}} **</label>
                    <select id="language" name="user_language_id" class="form-control">
                        <option value="" selected disabled>{{__('Select a language')}}</option>
                        @foreach ($userLanguages as $lang)
                        <option value="{{$lang->id}}">{{$lang->name}}</option>
                        @endforeach
                    </select>
                    <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                </div>
                <div class="form-group">
                    <label for="">{{ __('Title*') }}</label>
                    <input
                    type="text"
                    class="form-control"
                    name="title"
                    placeholder="{{__('Enter Quick Link Title')}}"
                    >
                    <p id="errtitle" class="mt-1 mb-0 text-danger em"></p>
                </div>

                <div class="form-group">
                    <label for="">{{ __('URL*') }}</label>
                    <input
                    type="url"
                    class="form-control ltr"
                    name="url"
                    placeholder="{{__('Enter Quick Link URL')}}"
                    >
                    <p id="errurl" class="mt-1 mb-0 text-danger em"></p>
                </div>

                <div class="form-group">
                    <label for="">{{ __('Serial Number*') }}</label>
                    <input
                    type="number"
                    class="form-control ltr"
                    name="serial_number"
                    placeholder="{{__('Enter Serial Number')}}"
                    >
                    <p id="errserial_number" class="mt-1 mb-0 text-danger em"></p>
                    <p class="text-warning mt-2">
                    <small>{{ __('The higher the serial number is, the later the quick link will be shown.') }}</small>
                    </p>
                </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                {{ __('Close') }}
                </button>
                <button id="submitBtnQuick_links" type="button" class="btn btn-primary">
                {{ __('Save') }}
                </button>
            </div>
            </div>
        </div>
    </div>

    <!--  -->
    <div
    class="modal fade"
    id="editModalquick_links"
    tabindex="-1"
    role="dialog"
    aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit Quick Links') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form
                    id="ajaxEditForm_quick_links"
                    class="modal-form"
                    action="{{ route('user.footer.update_quick_link') }}"
                    method="post"
                    >
                    @csrf
                    <input type="hidden" id="in_id" name="link_id">

                    <div class="form-group">
                        <label for="">{{ __('Title*') }}</label>
                        <input
                        type="text"
                        id="in_title"
                        class="form-control"
                        name="title"
                        >
                        <p id="eerrtitle" class="mt-1 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('URL*') }}</label>
                        <input
                        type="url"
                        id="in_url"
                        class="form-control ltr"
                        name="url"
                        >
                        <p id="eerrurl" class="mt-1 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number*') }}</label>
                        <input
                        type="number"
                        id="in_serial_number"
                        class="form-control ltr"
                        name="serial_number"
                        >
                        <p id="eerrserial_number" class="mt-1 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2">
                        <small>{{ __('The higher the serial number is, the later the quick link will be shown.') }}</small>
                        </p>
                    </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {{ __('Close') }}
                    </button>
                    <button id="updateBtn_quick_links" type="button" class="btn btn-primary">
                    {{ __('Update') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- edit Quick_links Modal -->

    <!--  -->

    {{-- edit modal --}}
    @include('user.home.brand_section.edit')


<!--  -->
<script>
    (function () {
        let lastSection = sessionStorage.getItem("lastSection");

        if (lastSection) {
            document.querySelectorAll(".content-section").forEach(section => section.classList.add("d-none"));
            document.querySelectorAll(".menu-item").forEach(item => item.classList.remove("text-primary", "bg-light", "active-item"));

            let sectionElement = document.getElementById(lastSection);
            let navItem = document.querySelector(`.menu-item[data-target="${lastSection}"]`);

            if (sectionElement && navItem) {
                sectionElement.classList.remove("d-none");
                navItem.classList.add("text-primary", "bg-light", "active-item");

                // Ensure hover effects apply dynamically
                navItem.addEventListener("mouseover", function () {
                    navItem.classList.add("hover-effect");
                });
                navItem.addEventListener("mouseout", function () {
                    navItem.classList.remove("hover-effect");
                });
            }
        }
    })();

    document.addEventListener("DOMContentLoaded", function () {
        let lastSection = sessionStorage.getItem("lastSection");

        if (lastSection) {
            let sectionElement = document.getElementById(lastSection);
            if (sectionElement) {
                sectionElement.scrollIntoView({ behavior: "smooth" });
                sessionStorage.removeItem("lastSection");
            }
        }

        document.querySelectorAll(".menu-item").forEach(item => {
            item.addEventListener("click", function (e) {
                let sectionId = e.currentTarget.getAttribute("data-target");
                if (sectionId) {
                    sessionStorage.setItem("lastSection", sectionId);
                }

                document.querySelectorAll(".menu-item").forEach(nav => nav.classList.remove("text-primary", "bg-light", "active-item"));
                e.currentTarget.classList.add("text-primary", "bg-light", "active-item");

                // Reapply hover effect for active menu items
                e.currentTarget.addEventListener("mouseover", function () {
                    e.currentTarget.classList.add("hover-effect");
                });
                e.currentTarget.addEventListener("mouseout", function () {
                    e.currentTarget.classList.remove("hover-effect");
                });
            });
        });
    });

    function storeSectionBeforeSubmit(form) {
        let activeSection = document.querySelector('.content-section:not(.d-none)');
        if (activeSection) {
            let sectionId = activeSection.id;
            sessionStorage.setItem("lastSection", sectionId);
            form.action += "#" + sectionId;
        }
        return true;
    }
</script>




<!--  -->
</body>

<!--  -->

@endsection

@section('scripts')
<script src="{{ asset('assets/admin/js/edit.js') }}"></script>


<script>
    $(document).ready(function() {
        $(".menu-item").click(function(e) {
            e.preventDefault();

            // Remove active state from all menu items
            $(".menu-item").removeClass("text-primary bg-light").addClass("text-dark");

            // Add active state to clicked menu item
            $(this).addClass("text-primary bg-light").removeClass("text-dark");

            // Hide all content sections
            $(".content-section").addClass("d-none");

            // Show the selected section
            $("#" + $(this).data("target")).removeClass("d-none");
        });
    });
</script>

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



<script>
    $(document).ready(function() {

        // CSRF token for all AJAX requests.
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // click event to delete buttons.
        $('.deleteSocialBtn').on('click', function(e) {
            e.preventDefault();
            var socialId = $(this).data('socialid');
            var row = $('#socialRow-' + socialId);

            // SweetAlert confirmation.
            swal({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancel",
                        visible: true,
                        className: "btn btn-danger",
                        closeModal: true
                    },
                    confirm: {
                        text: "Yes, delete it!",
                        className: "btn btn-success"
                    }
                }
            }).then((willDelete) => {
                if (willDelete) {
                    // AJAX request.
                    $.ajax({
                        url: "{{ route('user.social.delete') }}",
                        type: "POST",
                        data: {
                            socialid: socialId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Remove the table row.
                                row.fadeOut(500, function() {
                                    $(this).remove();
                                });
                                swal("Deleted!", response.message, "success");
                            } else {
                                swal("Error!", response.message, "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            swal("Error!", "An error occurred while deleting the social link.", "error");
                        }
                    });
                } else {
                    swal.close();
                }
            });
        });
    });
</script>


<script>
    let isFormDirty = false;

    const form = document.getElementById('mySettingsForm');

    form.addEventListener('change', function() {
        isFormDirty = true;
    });

    document.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (isFormDirty) {
                e.preventDefault();
                const destination = this.href;
                swal({
                    title: "لديك تغييرات غير محفوظة",
                    text: "هل أنت متأكد أنك تريد مغادرة هذه الصفحة",
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "Cancel",
                            visible: true,
                            className: "btn btn-danger",
                            closeModal: true
                        },
                        confirm: {
                            text: "Yes, leave it!",
                            className: "btn btn-success"
                        }
                    },
                    dangerMode: true
                }).then((willLeave) => {
                    if (willLeave) {
                        isFormDirty = false;
                        window.location.href = destination;
                    }
                });
            }
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = 'لديك تغييرات غير محفوظة?';
        }
    });

    form.addEventListener('submit', function() {
        isFormDirty = false;
    });
</script>

<script>
    "use strict";
    // myDropzone is the configuration for the element that has an id attribute
    // with the value my-dropzone (or myDropzone)
    Dropzone.options.myDropzone = {
        acceptedFiles: '.png, .jpg, .jpeg',
        url: "{{ route('user.portfolio.sliderstore') }}",
        maxFilesize: 2, // specify the number of MB you want to limit here
        success: function(file, response) {
            $("#sliders").append(
                `<input type="hidden" name="slider_images[]" id="slider${response.file_id}" value="${response.file_id}">`
            );
            // Create the remove button
            var removeButton = Dropzone.createElement(
                "<button class='rmv-btn'><i class='fa fa-times'></i></button>");

            // Capture the Dropzone instance as closure.
            var _this = this;

            // Listen to the click event
            removeButton.addEventListener("click", function(e) {
                // Make sure the button click doesn't submit the form:
                e.preventDefault();
                e.stopPropagation();
                _this.removeFile(file);
                rmvimg(response.file_id);
            });

            // Add the button to the file preview element.
            file.previewElement.appendChild(removeButton);

            if (typeof response.error != 'undefined') {
                if (typeof response.file != 'undefined') {
                    document.getElementById('errpreimg').innerHTML = response.file[0];
                }
            }
        }
    };

    function rmvimg(fileid) {
        // If you want to the delete the file on the server as well,
        // you can do the AJAX request here.

        $.ajax({
            url: "{{ route('user.portfolio.sliderrmv') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                fileid: fileid
            },
            success: function(data) {
                $("#slider" + fileid).remove();
            }
        });

    }
</script>

<script>
    function redirectToSection() {
        let activeSection = document.querySelector('.content-section:not(.d-none)');
        if (activeSection) {
            let sectionId = activeSection.id;
            let form = document.getElementById("mySettingsForm");
            form.action += "#" + sectionId; // Append the section ID to the URL
        }
        return true;
    }

    window.onload = function () {
        if (window.location.hash) {
            let targetSection = document.querySelector(window.location.hash);
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: "smooth" });
            }
        }
    };
</script>

<!-- menu -->
<script type="text/javascript" src="{{ asset('assets/admin/js/plugin/jquery-menu-editor/jquery-menu-editor.js') }}">
    </script>
    <script>
        "use strict";
        var prevMenus = @php echo json_encode($prevMenu) @endphp;
        var langid = {{ $lang_id }};
        var menuUpdate = "{{ route('user.menu_builder.update') }}";
    </script>
    <script type="text/javascript" src="{{ asset('assets/admin/js/menu-builder.js') }}"></script>
    <script>
        (function($) {

            $('.btnEdit').on('click', function() {
                setTimeout(() => {
                    $(".iconpicker-component i").removeClass();
                    $('.iconpicker-component i').addClass($('#inputIcon').val())
                }, 10);

            });
        })(jQuery);
</script>
@endsection
