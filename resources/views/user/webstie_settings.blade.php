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
                <i class="fas fa-cogs ml-2 mr-2"></i> Website Settings
            </h4>

            <!-- Sidebar Menu -->
            <div class="nav flex-column">
                <a href="#basic-settings" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="basic-settings">
                    <i class="fas fa-sliders-h ml-2 mr-2"></i> Basic Settings
                </a>
                <a href="#banner" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="banner">
                    <i class="fas fa-image ml-2 mr-2"></i> Banner Section
                </a>
                <a href="#skills" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="skills">
                    <i class="fas fa-tools ml-2 mr-2"></i> Skills Section
                </a>
                <a href="#about" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="about">
                    <i class="fas fa-building ml-2 mr-2"></i> About Company
                </a>
                <a href="#portfolio" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="portfolio">
                    <i class="fas fa-briefcase ml-2 mr-2"></i> Portfolio
                </a>
                <a href="#reviews" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="reviews">
                    <i class="fas fa-star ml-2 mr-2"></i> Customer Reviews
                </a>
                <a href="#services" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="services">
                    <i class="fas fa-concierge-bell ml-2 mr-2"></i> Services
                </a>
                <a href="#achievements" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="achievements">
                    <i class="fas fa-trophy ml-2 mr-2"></i> Achievements
                </a>
                <a href="#brands" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="brands">
                    <i class="fas fa-tags ml-2 mr-2"></i> Brands
                </a>
                <a href="#footer" class="nav-link d-flex align-items-center text-dark mb-2 menu-item" data-target="footer">
                    <i class="fas fa-shoe-prints ml-2 mr-2"></i> Footer
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-fill p-4">
            <!-- <h2 class="h3 font-weight-bolder">Website Settings</h2> -->

            <!-- Basic Settings Section -->
            <div id="basic-settings" class="content-section">
                <h3 class="h4 font-weight-bold">Basic Settings</h3>
                <p class="text-muted">Manage general website settings such as site name, logo, and favicon.</p>
                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">

                            <div class="card-body ">

                                <form id="mySettingsForm" action="{{ route('user.general_settings.update_all',['language' => request()->input('language')]) }}" method="POST" enctype="multipart/form-data">
                                    @csrf

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
                                                    <input type="text" class="form-control jscolor" name="base_color" value="{{ $information['basic_settings']->base_color }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('Secondary Color') }}</label>
                                                    <input type="text" class="form-control jscolor" name="secondary_color" value="{{ $information['basic_settings']->secondary_color }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Logo Section -->
                                    <!-- Logo Section -->
                                    <div class="row">
                                        <div class="col-lg-6 offset-lg-3">
                                            <div class="form-group">
                                                <div class="col-12 mb-2">
                                                    <h3 class="section-title">{{ __('Website Logo') }}</h3>
                                                    <p class="section-description">
                                                        {{ __('Upload your website logo here. The logo represents your brand and will appear on the website header, footer, and other sections.') }}
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
                                                <img src="{{ isset($basic_setting->preloader) ? asset('assets/front/img/user/'.$basic_setting->preloader) : asset('assets/admin/img/noimage.jpg') }}" alt="preloader" class="img-thumbnail">
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
                                                <img src="{{ isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/'.$basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
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
                                                <img src="{{ isset($basic_setting->favicon) ? asset('assets/front/img/user/'.$basic_setting->favicon) : asset('assets/admin/img/noimage.jpg') }}" alt="favicon" class="img-thumbnail">
                                            </div>
                                            <button type="button" class="upload-btn d-none" onclick="document.getElementById('favicon').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Favicon') }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Home Page About Section -->
                                    <div class="card-body pt-5 pb-5">
                                        <div class="row">
                                            <div class="col-lg-6 offset-lg-3">
                                                <input type="hidden" name="id" value="{{ $information['home_setting']->id }}">
                                                <input type="hidden" name="language_id" value="{{ $information['home_setting']->language_id }}">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <div class="col-12 mb-2">
                                                                <h3 class="section-title">{{ __('Company Introduction (About Us)') }}</h3>
                                                                <p class="section-description">
                                                                    {{ __('This section contains text and an image about your company. You can control its content here.') }}
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
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label>{{ __('Title') }}</label>
                                                            <input type="text" class="form-control" name="about_title" value="{{ $information['home_setting']->about_title }}">
                                                            <p id="errabout_title" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                    @if ($userBs->theme === 'home_eleven')
                                                    <div class="col-lg-6 pl-0">
                                                        <div class="form-group">
                                                            <label>{{ __('Second Button Text') }}</label>
                                                            <input type="text" class="form-control" name="about_snd_button_text" value="{{ $information['home_setting']->about_snd_button_text }}">
                                                            <p id="errabout_snd_button_text" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ __('Content') }}</label>
                                                    <textarea class="form-control" name="about_content" rows="5">{{ $information['home_setting']->about_content }}</textarea>
                                                    <p id="errabout_content" class="mb-0 text-danger em"></p>
                                                </div>
                                                @if ((isset($userBs->theme) && $userBs->theme !== 'home_two') || $userBs->theme === 'home_eleven')
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group">
                                                            <label>{{ __('Button Text') }}</label>
                                                            <input type="text" class="form-control" name="about_button_text" value="{{ $information['home_setting']->about_button_text }}">
                                                            <p id="errabout_button_text" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 pl-0">
                                                        <div class="form-group">
                                                            <label>{{ __('Button URL') }}</label>
                                                            <input type="text" class="form-control ltr" name="about_button_url" value="{{ $information['home_setting']->about_button_url }}">
                                                            <p id="errabout_button_url" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if (isset($userBs->theme) && $userBs->theme === 'home_two')
                                                <div class="form-group">
                                                    <div class="col-12 mb-2">
                                                        <label for="about_video_image"><strong>{{ __('Video Background Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 showAboutVideoImage mb-3">
                                                        <img src="{{ $information['home_setting']->about_video_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_video_image) : asset('assets/admin/img/noimage.jpg') }}" alt="video background" class="img-thumbnail">
                                                    </div>
                                                    <input type="file" name="about_video_image" id="about_video_image" class="form-control ltr">
                                                    <p id="errabout_video_image" class="mb-0 text-danger em"></p>
                                                </div>
                                                <div class="form-group">
                                                    <label>{{ __('Video URL') }}</label>
                                                    <input type="text" class="form-control ltr" name="about_video_url" value="{{ $information['home_setting']->about_video_url }}">
                                                    <p id="errabout_video_url" class="mb-0 text-danger em"></p>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Submit Button -->
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

            <!-- Banner Section -->
            <div id="banner" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Banner Section</h3>
                <p class="text-muted">Upload and configure homepage banners.</p>
                <!--  -->
                <!-- SLIDER -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                                            <a href="{{ route('user.home_page.hero.create_slider')}}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('Add Slider') }}</a>
                                            <!--  -->
                                            <!--  -->
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                        <form id="sliderVersionForm" action="{{ route('user.home_page.hero.store_slider_info') }}" method="POST" enctype="multipart/form-data">
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

                                        <div class="settings-section">
                                            <h3 class="section-title">{{ __('Breadcrumb Photo') }}</h3>
                                            <p class="section-description">{{ __('Add an image that will appear as a background for the breadcrumb section, helping to enhance navigation visuals.') }}</p>
                                            <div class="form-group">
                                                <div class="preview-image">
                                                    <img src="{{ isset($basic_setting->breadcrumb) ? asset('assets/front/img/user/'.$basic_setting->breadcrumb) : asset('assets/admin/img/noimage.jpg') }}" alt="breadcrumb" class="img-thumbnail">
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


                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--// end SLIDER  -->
                <!--  -->
            </div>
            <!--  -->

            <!-- Skills Section -->
            <div id="skills" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Skills Section</h3>
                <p class="text-muted">Update the skills displayed on the website.</p>
                <!--  -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
                                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4">
                        @if (!is_null($userDefaultLang))
                                            <a href="#" class="btn btn-primary" data-toggle="modal"
                                                data-target="#createModalSkill"><i class="fas fa-plus"></i> {{ __('Add Skill') }}</a>
                                            <button class="btn btn-danger mr-2 d-none bulk-delete"
                                                data-href="{{ route('user.skill.bulk.delete') }}"><i class="flaticon-interface-5"></i>
                                                {{ __('Delete') }}</button>
                                        @endif

                        @if(!is_null($userDefaultLang))
                                @if (!empty($userLanguages))
                                    <select name="userLanguage" style="width: 200px; margin-inline: 0.8rem;height: 100%;" class="form-control btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
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
                                                                        <input type="checkbox" class="bulk-check"
                                                                            data-val="{{ $skill->id }}">
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
                                                                        <a class="btn btn-secondary btn-sm"
                                                                            href="{{ route('user.skill.edit', $skill->id) . '?language=' . $skill->language->code }}">
                                                                            <span class="btn-label">
                                                                                <i class="fas fa-edit"></i>
                                                                            </span>
                                                                            {{ __('Edit') }}
                                                                        </a>
                                                                        <form class="deleteform d-inline-block"
                                                                            action="{{ route('user.skill.delete') }}" method="post">
                                                                            @csrf
                                                                            <input type="hidden" name="skill_id"
                                                                                value="{{ $skill->id }}">
                                                                            <button type="submit"
                                                                                class="btn btn-danger btn-sm deletebtn">
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
                <h3 class="h4 font-weight-bold">About Company</h3>
                <p class="text-muted">Provide information about your company.</p>
                <!--  -->
                <!-- Home Page About Section -->
                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <input type="hidden" name="id" value="{{ $information['home_setting']->id }}">
                            <input type="hidden" name="language_id" value="{{ $information['home_setting']->language_id }}">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <h3 class="section-title">{{ __('Company Introduction (About Us)') }}</h3>
                                            <p class="section-description">
                                                {{ __('This section contains text and an image about your company. You can control its content here.') }}
                                            </p>
                                        </div>
                                        <div class="col-md-12 showAboutImage mb-3">
                                            <img src="{{ $information['home_setting']->about_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_image) : asset('assets/admin/img/noimage.jpg') }}" alt="about image" class="img-thumbnail">
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
                            </div>
                            <div class="row">
                                <div class="col-lg-6 pr-0">
                                    <div class="form-group">
                                        <label>{{ __('Title') }}</label>
                                        <input type="text" class="form-control" name="about_title" value="{{ $information['home_setting']->about_title }}">
                                        <p id="errabout_title" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                @if ($userBs->theme === 'home_eleven')
                                <div class="col-lg-6 pl-0">
                                    <div class="form-group">
                                        <label>{{ __('Second Button Text') }}</label>
                                        <input type="text" class="form-control" name="about_snd_button_text" value="{{ $information['home_setting']->about_snd_button_text }}">
                                        <p id="errabout_snd_button_text" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="form-group">
                                <label>{{ __('Content') }}</label>
                                <textarea class="form-control" name="about_content" rows="5">{{ $information['home_setting']->about_content }}</textarea>
                                <p id="errabout_content" class="mb-0 text-danger em"></p>
                            </div>
                            @if ((isset($userBs->theme) && $userBs->theme !== 'home_two') || $userBs->theme === 'home_eleven')
                            <div class="row">
                                <div class="col-lg-6 pr-0">
                                    <div class="form-group">
                                        <label>{{ __('Button Text') }}</label>
                                        <input type="text" class="form-control" name="about_button_text" value="{{ $information['home_setting']->about_button_text }}">
                                        <p id="errabout_button_text" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                                <div class="col-lg-6 pl-0">
                                    <div class="form-group">
                                        <label>{{ __('Button URL') }}</label>
                                        <input type="text" class="form-control ltr" name="about_button_url" value="{{ $information['home_setting']->about_button_url }}">
                                        <p id="errabout_button_url" class="mb-0 text-danger em"></p>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (isset($userBs->theme) && $userBs->theme === 'home_two')
                            <div class="form-group">
                                <div class="col-12 mb-2">
                                    <label for="about_video_image"><strong>{{ __('Video Background Image') }}</strong></label>
                                </div>
                                <div class="col-md-12 showAboutVideoImage mb-3">
                                    <img src="{{ $information['home_setting']->about_video_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->about_video_image) : asset('assets/admin/img/noimage.jpg') }}" alt="video background" class="img-thumbnail">
                                </div>
                                <input type="file" name="about_video_image" id="about_video_image" class="form-control ltr">
                                <p id="errabout_video_image" class="mb-0 text-danger em"></p>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Video URL') }}</label>
                                <input type="text" class="form-control ltr" name="about_video_url" value="{{ $information['home_setting']->about_video_url }}">
                                <p id="errabout_video_url" class="mb-0 text-danger em"></p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <!--  -->
            </div>

            <!-- Portfolio Section -->
            <div id="portfolio" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Portfolio</h3>
                <p class="text-muted">Manage projects and case studies in the portfolio section.</p>

                <!-- portfolio -->
                <div class="row">
                    <div class="col-md-12">

                        <div class="row">


                            <div class="col-lg-12 offset-lg-1 mt-2 mt-lg-0">
                                @if (!is_null($userDefaultLang))  <!-- portfolio -->
                                <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Portfolio') }}</a>
                                <a class="btn btn-success float-right btn-sm mr-2" href="{{ route('user.portfolio.category.index') }}"><i class="fas fa-hands"></i> {{ __('Add categore') }}</a>
                                <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.portfolio.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                                @endif
                            </div>
                        </div>


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
                                                    <input type="checkbox" class="bulk-check" data-val="{{ $portfolio->id }}">
                                                </td>
                                                <td><img src="{{ asset('assets/front/img/user/portfolios/' . $portfolio->image) }}" alt="" width="80"></td>
                                                <td>{{ strlen($portfolio->title) > 30 ? mb_substr($portfolio->title, 0, 30, 'UTF-8') . '...' : $portfolio->title }}
                                                </td>
                                                <td>{{ $portfolio->bcategory->name }}</td>
                                                @if ($userBs->theme != 'home_ten')
                                                <td>
                                                    <form id="featureForm{{ $portfolio->id }}" class="d-inline-block" action="{{ route('user.portfolio.featured') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="portfolio_id" value="{{ $portfolio->id }}">
                                                        <select class="form-control {{ $portfolio->featured == 1 ? 'bg-success' : 'bg-danger' }}" name="featured" onchange="document.getElementById('featureForm{{ $portfolio->id }}').submit();">
                                                            <option value="1" {{ $portfolio->featured == 1 ? 'selected' : '' }}>
                                                                {{ __('Yes') }}
                                                            </option>
                                                            <option value="0" {{ $portfolio->featured == 0 ? 'selected' : '' }}>
                                                                {{ __('No') }}
                                                            </option>
                                                        </select>
                                                    </form>


                                                </td>
                                                @endif
                                                <td>{{ $portfolio->serial_number }}</td>
                                                <td>
                                                    <a class="btn btn-secondary btn-sm" href="{{ route('user.portfolio.edit', $portfolio->id) . '?language=' . $portfolio->language->code }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form class="deleteform d-inline-block" action="{{ route('user.portfolio.delete') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $portfolio->id }}">
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
                                @endif
                            </div>
                        </div>


                    </div>
                </div>
                <!--// portfolio -->

            </div>

            <!-- Customer Reviews Section -->
            <div id="reviews" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Customer Reviews</h3>
                <p class="text-muted">Manage customer testimonials and reviews.</p>

                <!--  -->
                @if (
                $userBs->theme != 'home_eight' ||
                ($userBs->theme != 'home_ten' && !empty($permissions) && in_array('Testimonial', $permissions)))
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <br>
                            <h3 class="text-warning">{{ __('Testimonial Section') }}</h3>
                            <hr class="border-top">
                        </div>
                        @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_one' || $userBs->theme == 'home_ten')
                        <div class="form-group">
                            <div class="col-12 mb-2">
                                <label for="logo"><strong>{{ __('Testimonial Image') }}</strong></label>
                            </div>
                            <div class="col-md-12 showTestimonialImage mb-3">
                                <img src="{{ $information['home_setting']->testimonial_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->testimonial_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                            </div>

                            <input type="file" id="testimonial_image" name="testimonial_image" class="d-none form-control ltr" accept="image/*">

                            <button type="button" class="upload-btn" style="color: #0E9384;background-color:white;border: 2px dashed #8c9998; padding: 1rem;width: 80%; display: flex; flex-direction: column; align-items: center; cursor: pointer;" onclick="document.getElementById('testimonial_image').click()">
                                <i class="bi bi-upload mb-2"></i>
                                <span>{{ __('Testimonial Image') }}</span>
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
                            <!-- Add Testimonial -->
                            <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                                <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#create_testimonial_Modal"><i class="fas fa-plus"></i> {{ __('Add Testimonial') }}</a>
                                <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.testimonial.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                            </div>
                            <!--// Add Testimonial -->
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_ten')
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <br>
                            <h3 class="text-warning">{{ __('Counter Section') }}</h3>
                            <hr class="border-top">
                        </div>
                        <div class="row">
                            <div class="col-lg-6 pr-0">
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="logo"><strong>{{ __('Counter Section Image') }}</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage  mb-3">
                                        <img src="{{ $information['home_setting']->counter_section_image ? asset('assets/front/img/user/home_settings/' . $information['home_setting']->counter_section_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="hidden" name="types[]" value="counter_section_image">
                                    <input type="file" id="counter_section_image" name="counter_section_image" class="image" class=" d-none form-control ltr">

                                    <button type="button" class="upload-btn" onclick="document.getElementById('counter_section_image').click()">
                                        <i class="bi bi-upload mb-2"></i>
                                        <span>{{ __('Testimonial Image') }}</span>
                                    </button>
                                    <p id="errcounter_section_image" class="mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <!--  -->
            </div>

            <!-- Services Section -->
            <div id="services" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Services</h3>
                <p class="text-muted">Manage services offered by the company.</p>

                <!--  -->
                <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                    @if (!is_null($userDefaultLang))
                    <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal" data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Service') }}</a>
                    <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete" data-href="{{ route('user.service.bulk.delete') }}"><i class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
                    @endif
                </div>
                <!--  -->
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

                <!--  -->
            </div>

            <!-- Achievements Section -->
            <div id="achievements" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Achievements</h3>
                <p class="text-muted">Showcase company awards and achievements.</p>

                <!--  -->
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

                <!--  -->
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
                                <!-- Achievement -->
                                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.counter-information.store') }}" method="POST">
                                    @csrf
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
                                <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!--  -->

            </div>

            <!-- Brands Section -->
            <div id="brands" class="content-section d-none">
                <h3 class="h4 font-weight-bold">Brands</h3>
                <p class="text-muted">Manage brand logos displayed on the website.</p>
                <!--  -->
                <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-primary"><i class="fas fa-plus"></i>
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
                                            <a class="edit-btn btn btn-secondary btn-sm mr-2" href="#" data-toggle="modal" data-target="#editModalbrand" data-id="{{ $brand->id }}" data-brandimg="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}" data-brand_url="{{ $brand->brand_url }}" data-serial_number="{{ $brand->serial_number }}">
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
                <h3 class="h4 font-weight-bold">Footer</h3>
                <p class="text-muted">Edit footer content and social media links.</p>

                <div class="form-group mt-4">
                    <div class="form-group">
                        <label for="">{{ __('Footer\'s Logo*') }}</label> <br>
                        <div class="col-md-12 showImage mb-3">
                            <img src="{{ isset($information['footertext']) ? asset('assets/front/img/user/footer/' . $information['footertext']->logo) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                        </div>
                        <input type="file" name="logo" id="logo" class=" d-none form-control image">
                        <p id="errlogo" class="em text-danger mt-2 mb-0"></p>
                        <button type="button" class="upload-btn" onclick="document.getElementById('logo').click()">
                            <i class="bi bi-upload mb-2"></i>
                            <span>{{ __('Upload Favicon') }}</span>
                        </button>
                    </div>
                    @if ($userBs->theme == 'home_six' || $userBs->theme == 'home13')
                    <div class="form-group">
                        <label for="">{{ __('Footer\'s Background*') }}</label> <br>
                        <div class="col-md-12 showImage mb-3">
                            <img src="{{ isset($information['footertext']) ? asset('assets/front/img/user/footer/' . $information['footertext']->bg_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                        </div>
                        <input type="file" id="bg_image" name="bg_image" class=" d-none form-control image">
                        <p id="errbg_image" class="em text-danger mt-2 mb-0"></p>
                        <button type="button" class="upload-btn" onclick="document.getElementById('bg_image').click()">
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
                </div>

            </div>
        </main>
    </div>


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
                    <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('user.testimonial.store') }}" method="POST">
                        @csrf
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>


        <!-- Create Skill Modal -->
    <div class="modal fade" id="createModalSkill" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
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
                    <form id="ajaxFormSkill" enctype="multipart/form-data" class="modal-form"
                        action="{{ route('user.skill.store') }}" method="POST">
                        @csrf
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
                                    <button type="button" class="btn btn-primary iconpicker-component"><i
                                            class="fa fa-fw fa-heart"></i></button>
                                    <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                        data-selected="fa-car" data-toggle="dropdown"></button>
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
                                    <input id="percentage" type="number" class="form-control ltr" name="percentage"
                                        value="" min="1" max="100"
                                        onkeyup="if(parseInt(this.value)>100 || parseInt(this.value)<=0 ){this.value =100; return false;}">
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
                                    <input type="text" name="color" value="#F78058"
                                        class="form-control jscolor ltr">
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
                    <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!--  -->
    {{-- create modal --}}
    @include('user.home.brand_section.create')

    {{-- edit modal --}}
    @include('user.home.brand_section.edit')

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


@endsection
