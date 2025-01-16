@extends('user.layout')
@php
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp

@includeIf('user.partials.rtl-style')

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
                <h2 class="fs-4 fw-semibold mb-2"> {{   __('About Section')}}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    في الصفحة الرئيسية يوجد قسم عن الشركة, يحتوي على معلومات نصيه وصورة, يمكنك التحكم بالمحتوى الخاص به من هنا
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
          <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4"> 
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

                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="ajaxForm" action="{{ route('user.home.page.update.about') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $home_setting->id }}">
                                <input type="hidden" name="language_id" value="{{ $home_setting->language_id }}">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <div class="col-12 mb-2">
                                                <label for="logo"><strong>{{ __('Image') }}</strong></label>
                                            </div>
                                            <div class="col-md-12 showAboutImage mb-3">
                                                <img src="{{ $home_setting->about_image ? asset('assets/front/img/user/home_settings/' . $home_setting->about_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                    alt="..." class="img-thumbnail">
                                            </div>
                                            <input type="hidden" name="types[]" value="about_image">
                                            <input type="file" name="about_image" id="about_image"
                                                class="form-control ltr">
                                            <p id="errabout_image" class="mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 pr-0">
                                        <div class="form-group">
                                            <label for="">{{ __('Title') }}</label>
                                            <input type="hidden" name="types[]" value="about_title">
                                            <input type="text" class="form-control" name="about_title"
                                                value="{{ $home_setting->about_title }}">
                                            <p id="errabout_title" class="mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    @if ($userBs->theme !== 'home_eleven')
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Subtitle') }}</label>
                                                <input type="hidden" name="types[]" value="about_subtitle">
                                                <input type="text" class="form-control" name="about_subtitle"
                                                    value="{{ $home_setting->about_subtitle }}">
                                                <p id="errabout_subtitle" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Content') }}</label>
                                    <input type="hidden" name="types[]" value="about_content">
                                    <textarea class="form-control" name="about_content" rows="5">{{ $home_setting->about_content }}</textarea>
                                    <p id="errabout_content" class="mb-0 text-danger em"></p>
                                </div>
                                @if ((isset($userBs->theme) && !$userBs->theme === 'home_two') || $userBs->theme === 'home_eleven')
                                    <div class="row">
                                        <div class="col-lg-6 pr-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Button Text') }}</label>
                                                <input type="hidden" name="types[]" value="about_button_text">
                                                <input type="text" class="form-control" name="about_button_text"
                                                    value="{{ $home_setting->about_button_text }}">
                                                <p id="errabout_button_text" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Button URL') }}</label>
                                                <input type="hidden" name="types[]" value="about_button_url">
                                                <input type="text" class="form-control ltr" name="about_button_url"
                                                    value="{{ $home_setting->about_button_url }}">
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
                                                <input type="text" class="form-control" name="about_snd_button_text"
                                                    value="{{ $home_setting->about_snd_button_text }}">
                                                <p id="errabout_snd_button_text" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 pl-0">
                                            <div class="form-group">
                                                <label for="">{{ __('Secound Button URL') }}</label>
                                                <input type="hidden" name="types[]" value="about_snd_button_url">
                                                <input type="text" class="form-control ltr"
                                                    name="about_snd_button_url"
                                                    value="{{ $home_setting->about_snd_button_url }}">
                                                <p id="errabout_snd_button_url" class="mb-0 text-danger em"></p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if (isset($userBs->theme) && $userBs->theme === 'home_two')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label
                                                for="logo"><strong>{{ __('Video Background Image') }}</strong></label>
                                        </div>
                                        <div class="col-md-12 showAboutVideoImage mb-3">
                                            <img src="{{ $home_setting->about_video_image ? asset('assets/front/img/user/home_settings/' . $home_setting->about_video_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="hidden" name="types[]" value="about_video_image">
                                        <input type="file" name="about_video_image" id="about_video_image"
                                            class="form-control ltr">
                                        <p id="errabout_video_image" class="mb-0 text-danger em"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{ __('Video URL') }}</label>
                                        <input type="hidden" name="types[]" value="about_video_url">
                                        <input type="text" class="form-control ltr" name="about_video_url"
                                            value="{{ $home_setting->about_video_url }}">
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


@section('scripts')
    <script src="{{ asset('assets/admin/js/home-sections.js') }}"></script>
@endsection
