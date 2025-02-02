@extends('user.layout')

@php
$user = Auth::guard('web')->user();
$package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
if (!empty($user)) {
$permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
$permissions = json_decode($permissions, true);
}

$selLang = \App\Models\User\Language::where([['code', \Illuminate\Support\Facades\Session::get('currentLangCode')], ['user_id', \Illuminate\Support\Facades\Auth::id()]])->first();
$userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
$userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();

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

@if (!empty($selLang) && $selLang->rtl == 1)
@section('styles')
<style>
    form:not(.modal-form) input,
    form:not(.modal-form) textarea,
    form:not(.modal-form) select,
    select[name='userLanguage'] {
        direction: rtl;
    }

    form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
        direction: rtl;
        text-align: right;
    }
</style>
@endsection
@endif

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
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Site Settings') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        قم بتعديل بيانات الموقع الاساسية من هنا
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
                    <div class="col-lg-3 float-left">
                        @if (!is_null($userDefaultLang))
                        @if (!empty($userLanguages))
                        <select name="userLanguage" class="form-control" onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
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

            <div class="card-body">

                <form id="mySettingsForm" action="{{ route('user.general_settings.update_all',['language' => request()->input('language')]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Website Title Section -->

                    <div class="settings-section">
                        <h3 class="section-title">{{ __('Website Name') }}</h3>
                        <p class="section-description">
                            {{ __('This is the name of your website. It will appear in the header, footer, and browser tabs. Make it simple and memorable.') }}
                        </p>
                        <div class="form-group">
                            <input type="text" class="form-control" name="website_title" value="{{ isset($data->website_title) ? $data->website_title : '' }}">
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
                                    <input type="text" class="form-control jscolor" name="base_color" value="{{ $data->base_color }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Secondary Color') }}</label>
                                    <input type="text" class="form-control jscolor" name="secondary_color" value="{{ $data->secondary_color }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Website Logo Section -->
                    <div class="settings-section col-lg-6">
                        <h3 class="section-title">{{ __('Website Logo') }}</h3>
                        <p class="section-description">
                            {{ __('Upload your website logo here. The logo represents your brand and will appear on the website header, footer, and other sections.') }}
                        </p>
                        <div class="form-group">
                            <div class="preview-image">
                                <img src="{{ isset($basic_setting->logo) ? asset('assets/front/img/user/'.$basic_setting->logo) : asset('assets/admin/img/noimage.jpg') }}" alt="website logo" class="img-thumbnail">
                            </div>
                            <!-- This input remains for the website logo -->
                            <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                            <button type="button" class="upload-btn" onclick="document.getElementById('logo').click()">
                                <i class="bi bi-upload mb-2"></i>
                                <span>{{ __('Upload Logo') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Preloader, Breadcrumb, Favicon Sections (Hidden) -->
                    <!-- Preloader Sections (Hidden) -->
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
                    <!-- Breadcrumb Sections (Hidden) -->
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
                    <!-- Favicon Sections (Hidden) -->
                    <div class="settings-section d-none">
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
                                <input type="hidden" name="id" value="{{ $home_setting->id }}">
                                <input type="hidden" name="language_id" value="{{ $home_setting->language_id }}">
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
                                                <img src="{{ $home_setting->about_image ? asset('assets/front/img/user/home_settings/' . $home_setting->about_image) : asset('assets/admin/img/noimage.jpg') }}" alt="about image" class="img-thumbnail">
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
                                            <input type="text" class="form-control" name="about_title" value="{{ $home_setting->about_title }}">
                                            <p id="errabout_title" class="mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    @if ($userBs->theme === 'home_eleven')
                                    <div class="col-lg-6 pl-0">
                                        <div class="form-group">
                                            <label>{{ __('Second Button Text') }}</label>
                                            <input type="text" class="form-control" name="about_snd_button_text" value="{{ $home_setting->about_snd_button_text }}">
                                            <p id="errabout_snd_button_text" class="mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Content') }}</label>
                                    <textarea class="form-control" name="about_content" rows="5">{{ $home_setting->about_content }}</textarea>
                                    <p id="errabout_content" class="mb-0 text-danger em"></p>
                                </div>
                                @if ((isset($userBs->theme) && $userBs->theme !== 'home_two') || $userBs->theme === 'home_eleven')
                                <div class="row">
                                    <div class="col-lg-6 pr-0">
                                        <div class="form-group">
                                            <label>{{ __('Button Text') }}</label>
                                            <input type="text" class="form-control" name="about_button_text" value="{{ $home_setting->about_button_text }}">
                                            <p id="errabout_button_text" class="mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 pl-0">
                                        <div class="form-group">
                                            <label>{{ __('Button URL') }}</label>
                                            <input type="text" class="form-control ltr" name="about_button_url" value="{{ $home_setting->about_button_url }}">
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
                                        <img src="{{ $home_setting->about_video_image ? asset('assets/front/img/user/home_settings/' . $home_setting->about_video_image) : asset('assets/admin/img/noimage.jpg') }}" alt="video background" class="img-thumbnail">
                                    </div>
                                    <input type="file" name="about_video_image" id="about_video_image" class="form-control ltr">
                                    <p id="errabout_video_image" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Video URL') }}</label>
                                    <input type="text" class="form-control ltr" name="about_video_url" value="{{ $home_setting->about_video_url }}">
                                    <p id="errabout_video_url" class="mb-0 text-danger em"></p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Home Page About Section -->

                    <!-- Footer Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="feature-card-text">
                                <h2 class="fs-4 fw-semibold mb-2">{{ __('Footer') }}</h2>
                                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                    {{ __('Edit the footer details here') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-body pt-5 pb-5">
                                <div class="row">
                                    <div class="col-lg-6 offset-lg-3">
                                        @if ($userBs->theme == 'home_ten')
                                        <div class="form-group d-none">
                                            <label>{{ __('Footer Color') }} *</label>
                                            <!-- Change name to footer_color -->
                                            <input type="text" class="form-control jscolor" name="footer_color" value="{{ isset($footertext) ? $footertext->footer_color : '' }}" required>
                                            <p id="errcolor" class="mb-0 text-danger em"></p>
                                        </div>
                                        @endif
                                        <div class="form-group d-none">
                                            <label>{{ __('Footer\'s Logo*') }}</label> <br>
                                            <div class="col-md-12 showImage mb-3">
                                                <!-- Use $footertext->logo (FooterText model field) -->
                                                <img src="{{ isset($footertext) ? asset('assets/front/img/user/footer/' . $footertext->logo) : asset('assets/admin/img/noimage.jpg') }}" alt="footer logo" class="img-thumbnail">
                                            </div>
                                            <!-- Change name and id to footer_logo -->
                                            <input type="file" name="footer_logo" id="footer_logo" class="d-none form-control image">
                                            <p id="errlogo" class="em text-danger mt-2 mb-0"></p>
                                            <button type="button" class="upload-btn" onclick="document.getElementById('footer_logo').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Favicon') }}</span>
                                            </button>
                                        </div>
                                        @if ($userBs->theme == 'home_six')
                                        <div class="form-group d-none">
                                            <label>{{ __('Footer\'s Background*') }}</label> <br>
                                            <div class="col-md-12 showImage mb-3">
                                                <!-- Change to use $footertext->bg_image -->
                                                <img src="{{ isset($footertext) ? asset('assets/front/img/user/footer/' . $footertext->bg_image) : asset('assets/admin/img/noimage.jpg') }}" alt="footer background" class="img-thumbnail">
                                            </div>
                                            <!-- Change name and id to footer_bg_image -->
                                            <input type="file" id="bg_image" name="footer_bg_image" class="d-none form-control image">
                                            <p id="errbg_image" class="em text-danger mt-2 mb-0"></p>
                                            <button type="button" class="upload-btn" onclick="document.getElementById('bg_image').click()">
                                                <i class="bi bi-upload mb-2"></i>
                                                <span>{{ __('Upload Favicon') }}</span>
                                            </button>
                                        </div>
                                        @endif
                                        <div class="form-group">
                                            <label>{{ __('About Company') }}</label>
                                            <textarea class="form-control" name="about_company" rows="3" cols="80">{{ isset($footertext) ? $footertext->about_company : '' }}</textarea>
                                            <p id="errabout_company" class="em text-danger mt-2 mb-0"></p>
                                        </div>
                                        @if ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_seven')
                                        <div class="form-group d-none">
                                            <label>{{ __('Newsletter Text') }}</label>
                                            <textarea class="form-control" name="newsletter_text" rows="3" cols="80">{{ isset($footertext) ? $footertext->newsletter_text : '' }}</textarea>
                                            <p id="errnewsletter_text" class="em text-danger mt-2 mb-0"></p>
                                        </div>
                                        @endif
                                        <div class="form-group d-none">
                                            <label>{{ __('Copyright Text') }}</label>
                                            <textarea id="copyrightSummernote" class="form-control summernote" name="copyright_text" data-height="80">{{ isset($footertext) ? replaceBaseUrl($footertext->copyright_text) : '' }}</textarea>
                                            <p id="errcopyright_text" class="em text-danger mb-0"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="feature-card-text">
                                <h2 class="fs-4 fw-semibold mb-2">{{ __('Social Links') }}</h2>
                                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                                    {{ __('Edit your company\'s social links here') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links - Add Social Link Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card-body pt-5 pb-5">
                                <div class="card-title">{{ __('Add Social Link') }}</div>
                                <div class="container " style="margin-right: 0px !important; margin-left: 0px !important;">
                                    <div class="form-group">
                                        <label>{{ __('Social Icon') }} **</label>
                                        <div class="btn-group d-block" style="position: relative;">
                                            <button type="button" class="btn btn-primary iconpicker-component">
                                                <i class="fa fa-fw fa-heart"></i>
                                            </button>
                                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle" data-selected="fa-car" data-toggle="dropdown"></button>
                                            <div class="dropdown-menu"></div>
                                        </div>
                                        <!-- Change input name to social_links[0][icon] -->
                                        <input id="inputIcon" type="hidden" name="social_links[0][icon]" value="">
                                        @if ($errors->has('social_links.0.icon'))
                                        <p class="mb-0 text-danger">{{ $errors->first('social_links.0.icon') }}</p>
                                        @endif
                                        <div class="mt-2">
                                            <small>{{ __('NB: click on the dropdown icon to select a social link icon.') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('URL') }} **</label>
                                    <!-- Change input name to social_links[0][url] -->
                                    <input type="text" class="form-control" name="social_links[0][url]" value="" placeholder="Enter URL of social media account">
                                    @if ($errors->has('social_links.0.url'))
                                    <p class="mb-0 text-danger">{{ $errors->first('social_links.0.url') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Serial Number') }} **</label>
                                    <!-- Change input name to social_links[0][serial_number] -->
                                    <input type="number" class="form-control ltr" name="social_links[0][serial_number]" value="" placeholder="Enter Serial Number">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning">
                                        <small>{{ __('The higher the serial number is, the later the social link will be shown.') }}</small>
                                    </p>
                                </div>
                            </div>
                        </div>

                    <!-- Social Links List -->
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
                                @foreach ($socials as $key => $social)
                                <tr id="socialRow-{{ $social->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><i class="{{ $social->icon }}"></i></td>
                                    <td>{{ $social->url }}</td>
                                    <td>{{ $social->serial_number }}</td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="{{ route('user.social.edit', $social->id) }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Delete button with data-socialid attribute -->
                                        <button type="button" class="btn btn-danger btn-sm deleteSocialBtn" data-socialid="{{ $social->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>


                    <!-- End Social Links Section -->

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


@endsection

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



<script>
    $(document).ready(function(){

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
                        data: { socialid: socialId },
                        success: function(response) {
                            if(response.success) {
                                // Remove the table row.
                                row.fadeOut(500, function(){
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

  form.addEventListener('change', function () {
    isFormDirty = true;
  });

  document.querySelectorAll('a').forEach(function(link){
    link.addEventListener('click', function(e){
      if(isFormDirty) {
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

   window.addEventListener('beforeunload', function (e) {
    if (isFormDirty) {
       e.preventDefault();
      e.returnValue = 'لديك تغييرات غير محفوظة?';
    }
  });

   form.addEventListener('submit', function () {
    isFormDirty = false;
  });
</script>



@endsection
