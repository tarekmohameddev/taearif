@extends('user.layout')

@php
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Why Choose Us Section') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                اظهر مميزات شركتك, ولماذا يجب على العملاء اختيارك
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
                        <div class="col-lg-2 float-left">
                            @if (!is_null($userDefaultLang))
                                @if (!empty($userLanguages))
                                    <select name="userLanguage" class="form-control"
                                        onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                        <option value="" selected disabled>{{ __('Select a Language') }}</option>
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
                            <form id="whyChooseUsSecForm"
                                action="{{ route('user.home_page.update_why_choose_us_section', ['language' => request()->input('language')]) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    @if ($userBs->theme == 'home13')
                                        <div class="col-12 mb-2">
                                            <label for="image"><strong>{{ __('Image One') }}</strong></label>
                                        </div>
                                    @else
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Background Image') }}</strong></label>
                                    </div>
                                    @endif
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ isset($data->why_choose_us_section_image) ? asset('assets/front/img/user/home_settings/' . $data->why_choose_us_section_image) : asset('assets/admin/img/noimage.jpg') }}"
                                            alt="..." class="img-thumbnail">
                                    </div>
                                    <input type="file" name="why_choose_us_section_image" id="image"
                                        class=" d-none form-control image">
                                    <button type="button" class="upload-btn" onclick="document.getElementById('image').click()">
                                    <i class="bi bi-upload mb-2"></i>
                                    <span>{{ __('Background Image') }}</span>
                                    </button>
                                    <p id="error_why_choose_us_section_image" class="mb-0 text-danger em"></p>
                                </div>
                                @if ($userBs->theme == 'home13')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label for="image"><strong>{{ __('Image Two ') }}</strong></label>
                                        </div>
                                        <div class="col-md-12 showImage2 mb-3">
                                            <img src="{{ isset($data->why_choose_us_section_image_two) ? asset('assets/front/img/user/home_settings/' . $data->why_choose_us_section_image_two) : asset('assets/admin/img/noimage.jpg') }}"
                                                alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="file" name="why_choose_us_section_image_two" id="image2"
                                            class="form-control image">
                                        <p id="error_why_choose_us_section_image_two" class="mb-0 text-danger em"></p>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Why Choose Us Section Title') }}</label>
                                    <input type="text" class="form-control" name="why_choose_us_section_title"
                                        value="{{ $data->why_choose_us_section_title ?? '' }}"
                                        placeholder="{{ __('Enter title') }}">
                                    @if ($errors->has('why_choose_us_section_title'))
                                        <p class="mt-2 mb-0 text-danger">
                                            {{ $errors->first('why_choose_us_section_title') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('Why Choose Us Section Subtitle') }}</label>
                                    <input type="text" class="form-control" name="why_choose_us_section_subtitle"
                                        value="{{ $data->why_choose_us_section_subtitle ?? '' }}"
                                        placeholder="{{ __('Enter subtitle') }}">
                                    @if ($errors->has('why_choose_us_section_subtitle'))
                                        <p class="mt-2 mb-0 text-danger">
                                            {{ $errors->first('why_choose_us_section_subtitle') }}</p>
                                    @endif
                                </div>
                                @if ($userBs->theme != 'home_nine')
                                    <div class="form-group">
                                        <label for="">{{ __('Why Choose Us Section Text') }}</label>
                                        <textarea class="form-control" name="why_choose_us_section_text" rows="3" cols="80"
                                            placeholder="{{ __('Enter text') }}">{{ $data->why_choose_us_section_text ?? null }}</textarea>
                                        @if ($errors->has('why_choose_us_section_text'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('why_choose_us_section_text') }}</p>
                                        @endif
                                    </div>
                                    @if ($userBs->theme != 'home13')
                                    <div class="form-group">
                                        <label for="">{{ __('Why Choose Us Section Button Text') }}</label>
                                            <input type="text" class="form-control"
                                                name="why_choose_us_section_button_text"
                                            value="{{ $data->why_choose_us_section_button_text ?? '' }}"
                                            placeholder="{{ __('Enter button text') }}">
                                        @if ($errors->has('why_choose_us_section_button_text'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('why_choose_us_section_button_text') }}</p>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="">{{ __('Why Choose Us Section Button URL') }}</label>
                                        <input type="text" class="form-control" name="why_choose_us_section_button_url"
                                            value="{{ $data->why_choose_us_section_button_url ?? '' }}"
                                            placeholder="{{ __('Enter button url') }}">
                                        @if ($errors->has('why_choose_us_section_button_url'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('why_choose_us_section_button_url') }}</p>
                                        @endif
                                    </div>
                                @endif
                                @endif
                                @if ($userBs->theme === 'home_three' || $userBs->theme === 'home13')
                                    @if ($userBs->theme != 'home13')
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label
                                                for="logo"><strong>{{ __('Why choose us video section image') }}</strong></label>
                                        </div>
                                        <div class="col-md-12 showAboutVideoImage mb-3">
                                            <img src="{{ !empty($data->why_choose_us_section_video_image) ? asset('assets/front/img/user/home_settings/' . $data->why_choose_us_section_video_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                alt="..." class="img-thumbnail">
                                        </div>
                                        <input type="file" name="why_choose_us_section_video_image"
                                            id="about_video_image" class="form-control ltr">
                                        @if ($errors->has('why_choose_us_section_video_image'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('why_choose_us_section_video_image') }}</p>
                                        @endif
                                    </div>
                                    @endif
                                    <div class="form-group">
                                        <label for="">{{ __('Video URL') }}</label>
                                        <input type="text" class="form-control ltr"
                                            name="why_choose_us_section_video_url"
                                            placeholder="{{ __('Enter video url') }}"
                                            value="{{ $data->why_choose_us_section_video_url ?? '' }}">
                                        @if ($errors->has('why_choose_us_section_video_url'))
                                            <p class="mt-2 mb-0 text-danger">
                                                {{ $errors->first('why_choose_us_section_video_url') }}</p>
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
                            <button type="submit" form="whyChooseUsSecForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <form id="ajaxForm" action="{{ route('user.home_page.why_choose_us_item_add') }}" method="post">
                    <div class="card-header">
                        <div class="card-title">{{ __('Add Why Chose Us Item ') }}</div>
                    </div>
                    <div class="card-body pt-5 pb-5">
                        <div class="row">
                            <div class="col-lg-12">
                                @csrf
                                <div class="form-group">
                                    <label for="">{{ __('Icon') }} **</label>
                                    <div class="btn-group d-block">
                                        <button type="button" class="btn btn-primary iconpicker-component"><i
                                                class="fa fa-fw fa-heart"></i></button>
                                        <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                            data-selected="fa-car" data-toggle="dropdown">
                                        </button>
                                        <div class="dropdown-menu"></div>
                                    </div>
                                    <input id="inputIcon" type="hidden" name="icon" value="">
                                    <p id="erricon" class="mb-0 text-danger em"></p>
                                    <div class="mt-2">
                                        <small>{{ __('NB: click on the dropdown icon to select a social link icon.') }}</small>
                                    </div>
                                </div>
                                @if (!is_null($userDefaultLang))
                                    @if (!empty($userLanguages))
                                        <div class="form-group">
                                            <label for="">{{ __('Language') }} **</label>
                                            <select name="language_id" class="form-control">
                                                <option value="" selected disabled>{{ __('Select a Language') }}
                                                </option>
                                                @foreach ($userLanguages as $lang)
                                                    <option value="{{ $lang->id }}"
                                                        {{ $lang->code == request()->input('language') ? 'selected' : '' }}>
                                                        {{ $lang->name }}</option>
                                                @endforeach
                                            </select>
                                            <p id="errlanguage_id" class="mb-0 text-danger em"></p>
                                        </div>
                                    @endif
                                @endif
                                <div class="form-group">
                                    <label for="">{{ __('Title') }} **</label>
                                    <input type="text" class="form-control" name="title"
                                        value="{{ old('title') }}" placeholder="Enter title">
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Content') }} **</label>
                                    <textarea name="content" id="" class="form-control" rows="4" placeholder="Enter content">{{ old('content') }}</textarea>
                                    <p id="errcontent" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') }} **</label>
                                    <input type="number" class="form-control ltr" name="serial_number"
                                        value="{{ old('serial_number') }}" placeholder="Enter Serial Number">
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
                                    <button type="submit" id="submitBtn" class="btn btn-success">
                                        {{ __('Submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ __('Why Choose Us Items') }}</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (empty($chooseUsItems))
                                <h2 class="text-center">{{ __('NO LINK ADDED') }}</h2>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">{{ __('Icon') }}</th>
                                                <th scope="col">{{ __('Title') }}</th>
                                                <th scope="col">{{ __('Icon') }}</th>
                                                <th scope="col">{{ __('Serial No') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($chooseUsItems as $key => $chooseUsItem)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td><i class="{{ $chooseUsItem->icon }}"></i></td>
                                                    <td>{{ $chooseUsItem->title }}</td>
                                                    <td>
                                                        {{ strlen($chooseUsItem->content) > 30
                                                            ? mb_substr(strip_tags($chooseUsItem->content), 0, 30, 'UTF-8') . '...'
                                                            : strip_tags($chooseUsItem->content) }}
                                                    </td>
                                                    <td>{{ $chooseUsItem->serial_number }}</td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm  editbtn" href="#"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $chooseUsItem->id }}" {{-- data-language="{{ $chooseUsItem->question }}" --}}
                                                            data-icon="{{ $chooseUsItem->icon }}"
                                                            data-title="{{ $chooseUsItem->title }}"
                                                            data-content="{{ $chooseUsItem->content }}"
                                                            data-serial_number="{{ $chooseUsItem->serial_number }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form class="d-inline-block deleteform"
                                                            action="{{ route('user.home_page.why_choose_us_item_delete') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="id"
                                                                value="{{ $chooseUsItem->id }}">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($userBs->theme == 'home_nine')
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Update Why Choose Us Item') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form id="ajaxEditForm" class="modal-form"
                            action="{{ route('user.home_page.why_choose_us_item_update') }}" method="post">
                            @csrf
                            <input type="hidden" name="id" id="inid">
                            <div class="form-group">
                                <label for="">{{ __('Icon') }} **</label>
                                <div class="btn-group d-block">
                                    <button type="button" class="btn btn-primary iconpicker-component picker"><i
                                            id="inicon" class=" "></i></button>
                                    <button type="button" class="icp2 icp-dd2 btn btn-primary dropdown-toggle"
                                        data-selected="fa-car" data-toggle="dropdown">
                                    </button>
                                    <div class="dropdown-menu"></div>
                                </div>
                                <input id="inputIcon2" type="hidden" name="icon" value="" class="in_icon">
                                <p id="eerricon" class="mb-0 text-danger em"></p>
                                <div class="mt-2">
                                    <small>{{ __('NB: click on the dropdown icon to select a social link icon.') }}</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="">{{ __('Title') }}*</label>
                                <input type="text" id="intitle" class="form-control" name="title"
                                    placeholder="{{ __('Enter title') }}">
                                <p id="eerrtitle" class="mt-1 mb-0 text-danger em"></p>
                            </div>

                            <div class="form-group">
                                <label for="">{{ __('Content') }}*</label>
                                <textarea class="form-control" id="incontent" name="content" rows="5" cols="80"
                                    placeholder="{{ __('Enter content') }}"></textarea>
                                <p id="eerrcontent" class="mt-1 mb-0 text-danger em"></p>
                            </div>

                            <div class="form-group">
                                <label for="">{{ __(' Serial Number') }}*</label>
                                <input type="number" id="inserial_number" class="form-control ltr" name="serial_number"
                                    placeholder="{{ __('Enter  Serial Number') }}">
                                <p id="eerrserial_number" class="mt-1 mb-0 text-danger em"></p>
                                <p class="text-warning mt-2">
                                    <small>{{ __('The higher the serial number is, the later the FAQ will be shown.') }}</small>
                                </p>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            {{ __('Close') }}
                        </button>
                        <button id="updateBtn" type="button" class="btn btn-primary">
                            {{ __('Update') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/home-sections.js') }}"></script>
@endsection
