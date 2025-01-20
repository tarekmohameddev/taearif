@extends('user.layout')

@php
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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Footer') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    قم بتعديل بيانات التذييل من هنا
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
                                    <select name="userLanguage" class="form-control"
                                        onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                        <option value="" selected disabled>Select a Language</option>
                                        @foreach ($userLanguages as $lang)
                                            <option value="{{ $lang->code }}"
                                                {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                {{ $lang->name }}</option>
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
                            <form id="ajaxForm"
                                action="{{ route('user.footer.update_footer_info', ['language' => request()->input('language')]) }}"
                                method="post" enctype="multipart/form-data">
                                @csrf
                                @if ($userBs->theme == 'home_ten')
                                    <div class="form-group">
                                        <label for="">{{ __('Footer Color') . ' *' }}</label>
                                        <input type="text" class="form-control jscolor" name="color"
                                            value="{{ isset($data) ? $data->footer_color : '' }}" required>
                                        <p id="errcolor" class="mb-0 text-danger em"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Footer\'s Logo*') }}</label> <br>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ isset($data) ? asset('assets/front/img/user/footer/' . $data->logo) : asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="logo"  id="logo" class=" d-none form-control image">
                                    <p id="errlogo" class="em text-danger mt-2 mb-0"></p>
                                    <button type="button" class="upload-btn" onclick="document.getElementById('logo').click()">
                                    <i class="bi bi-upload mb-2"></i>
                                    <span>{{ __('Upload Favicon') }}</span>
                                    </button>
                                </div>
                                @if ($userBs->theme == 'home_six')
                                    <div class="form-group">
                                        <label for="">{{ __('Footer\'s Background*') }}</label> <br>
                                        <div class="col-md-12 showImage mb-3">
                                            <img src="{{ isset($data) ? asset('assets/front/img/user/footer/' . $data->bg_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                alt="..." class="img-thumbnail">
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
                                    <textarea class="form-control" name="about_company" rows="3" cols="80">{{ isset($data) ? $data->about_company : '' }}</textarea>
                                    <p id="errabout_company" class="em text-danger mt-2 mb-0"></p>
                                </div>
                                @if ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_seven')
                                    <div class="form-group">
                                        <label for="">{{ __('Newsletter Text') }}</label>
                                        <textarea class="form-control" name="newsletter_text" rows="3" cols="80">{{ isset($data) ? $data->newsletter_text : '' }}</textarea>
                                        <p id="errnewsletter_text" class="em text-danger mt-2 mb-0"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Copyright Text') }}</label>
                                    <textarea id="copyrightSummernote" class="form-control summernote" name="copyright_text" data-height="80">{{ isset($data) ? replaceBaseUrl($data->copyright_text) : '' }}</textarea>
                                    <p id="errcopyright_text" class="em text-danger mb-0"></p>
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
