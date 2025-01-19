@extends('user.layout')

@php
$userDefaultLang = \App\Models\User\Language::where([
['user_id',\Illuminate\Support\Facades\Auth::id()],
['is_default',1]
])->first();
$userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
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
@includeIf('user.partials.rtl-style')

@section('content')
<!-- <div class="page-header">
        <h4 class="page-title">{{ __('Create Slider') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{route('user-dashboard')}}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Home Page') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Hero Section') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Create Slider') }}</a>
            </li>
        </ul>
    </div> -->

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
                    <h2 class="fs-4 fw-semibold mb-2">{{ __('Add Slider') }}</h2>
                    <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    {{ __('Add Slider') }}
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

            <!-- <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add Slider') }}</div>

                    <a
                        class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('user.home_page.hero.slider_version') . '?language=' . $userDefaultLang->code }}"
                    >
            <span class="btn-label">
              <i class="fas fa-backward"></i>
            </span>
                        {{ __('Back') }}
                    </a>
                </div> -->

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
                                <input type="file" name="slider_img" id="image" class="d-none" >
                                <button type="button" class="upload-btn" onclick="document.getElementById('image').click()">
                                <i class="bi bi-upload mb-2"></i>
                                <span>{{ __('Background Image') }}</span>
                                </button>
                                @if ($errors->has('slider_img'))
                                <p class="mt-2 mb-0 text-danger">{{ $errors->first('slider_img') }}</p>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{ __('Title') }}</label>
                                        <input type="text" class="form-control" name="title" placeholder="{{__('Enter Slider Title')}}">
                                        @if ($errors->has('title'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('title') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="">{{ __('Subtitle') }}</label>
                                        <input type="text" class="form-control" name="subtitle" placeholder="{{__('Enter Slider Subtitle')}}">
                                        @if ($errors->has('subtitle'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('subtitle') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

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

                            <div class="row">

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="">{{ __('Serial Number*') }}</label>
                                        <input type="number" class="form-control ltr" name="serial_number" placeholder="{{__('Enter Slider Serial Number')}}">
                                        @if ($errors->has('serial_number'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('serial_number') }}</p>
                                        @endif
                                        <p class="text-warning mt-2 mb-0">{{ __('The higher the serial number is, the later the slider will be shown.') }}</p>
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
    </div>
</div>
@endsection
