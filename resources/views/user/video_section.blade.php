@extends('user.layout')
@php
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp

@includeIf('user.partials.rtl-style')

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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Video Section') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    اظهر للعملاء فيديو تعريفي خاص بشركتك, يمكنك وضع رابط الفيديو المباشر على اي منصة
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
                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="videoSecForm"
                                action="{{ route('user.home.page.update.video', ['language' => request()->input('language')]) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Background Image') }} *</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ isset($data->video_section_image) ? asset('assets/front/img/user/home_settings/' . $data->video_section_image) : asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="video_section_image" id="image"
                                        class="d-none form-control image">
                                        <button type="button" class="upload-btn"
                                                style="background-color: white;
                                                        border: 2px dashed #8c9998;
                                                        color: #0E9384;
                                                        padding: 1rem;
                                                        width: 80%;
                                                        display: flex;
                                                        flex-direction: column;
                                                        align-items: center;
                                                        cursor: pointer;"
                                                onclick="document.getElementById('image').click()">
                                        <i class="bi bi-upload mb-2"></i>
                                        <span>{{ __('Background Image') }}</span>
                                        </button>
                                    @if ($errors->has('video_section_image'))
                                        <div class="error text-danger">{{ $errors->first('video_section_image') }}
                                        </div>
                                    @endif
                                </div>

                                @if ($userBs->theme != 'home_ten' && $userBs->theme != 'home_five' && $userBs->theme != 'home_four')
                                    <div class="form-group">
                                        <label for="">{{ __('Video Section Title') }}</label>
                                        <input type="text" class="form-control" name="video_section_title"
                                            value="{{ $data->video_section_title ?? old('video_section_title') }}">
                                        @if ($errors->has('video_section_title'))
                                            <p class="mt-2 mb-0 text-danger">{{ $errors->first('video_section_title') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                @if ($userBs->theme != 'home_ten' && $userBs->theme != 'home_five' && $userBs->theme != 'home_four')

                                    @if ($userBs->theme != 'home_two')
                                        @if ($userBs->theme != 'home_seven')
                                            <div class="form-group">
                                                <label for="">{{ __('Video Section Subtitle') }}</label>
                                                <input type="text" class="form-control" name="video_section_subtitle"
                                                    value="{{ $data->video_section_subtitle ?? old('video_section_subtitle') }}">
                                                @if ($errors->has('video_section_subtitle'))
                                                    <p class="mt-2 mb-0 text-danger">
                                                        {{ $errors->first('video_section_subtitle') }}</p>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($userBs->theme != 'home_nine' && $userBs->theme != 'home_one')
                                            <div class="form-group">
                                                <label for="">{{ __('Video Section Text') }}</label>
                                                <textarea class="form-control" name="video_section_text" rows="3" cols="80">{{ $data->video_section_text ?? old('video_section_text') }}</textarea>
                                                @if ($errors->has('video_section_text'))
                                                    <p class="mt-2 mb-0 text-danger">
                                                        {{ $errors->first('video_section_text') }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($userBs->theme != 'home_seven')
                                            <div class="form-group">
                                                <label for="">{{ __('Video Section Button Text') }}</label>
                                                <input type="text" class="form-control" name="video_section_button_text"
                                                    value="{{ $data->video_section_button_text ?? old('video_section_button_text') }}">
                                                @if ($errors->has('video_section_button_text'))
                                                    <p class="mt-2 mb-0 text-danger">
                                                        {{ $errors->first('video_section_button_text') }}</p>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($userBs->theme != 'home_seven')
                                            <div class="form-group">
                                                <label for="">{{ __('Video Section Button URL') }}</label>
                                                <input type="text" class="form-control" name="video_section_button_url"
                                                    value="{{ $data->video_section_button_url ?? old('video_section_button_url') }}">
                                                @if ($errors->has('video_section_button_url'))
                                                    <p class="mt-2 mb-0 text-danger">
                                                        {{ $errors->first('video_section_button_url') }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Video URL') }}</label>
                                    <input type="text" class="form-control" name="video_section_url"
                                        value="{{ $data->video_section_url ?? old('video_section_url') }}">
                                    @if ($errors->has('video_section_url'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('video_section_url') }}
                                        </p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="videoSecForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
