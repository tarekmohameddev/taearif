@extends('user.layout')

@php
    $selLang = \App\Models\User\Language::where([['code', \Illuminate\Support\Facades\Session::get('currentLangCode')], ['user_id', \Illuminate\Support\Facades\Auth::id()]])->first();
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
@endphp

@php
    $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::user()->id);
    $permissions = json_decode($permissions, true);
@endphp

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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Portfolios') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                   شارك معرض اعمالك مع العملاء
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
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <form id="ajaxForm" action="{{ route('user.home.page.text.update') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $home_setting->id }}">
                                <input type="hidden" name="language_id" value="{{ $home_setting->language_id }}">

                             
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
                                                            value="{{ $home_setting->portfolio_title }}">
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
                                                            value="{{ $home_setting->portfolio_subtitle }}">
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
                                                                value="{{ $home_setting->view_all_portfolio_text }}">
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
                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            @if (!is_null($userDefaultLang))
                                <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal"
                                    data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Portfolio') }}</a>
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
                                @if (count($portfolios) == 0)
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
                                                @foreach ($portfolios as $key => $portfolio)
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
                                                                    method="post">
                                                                    @csrf
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
    <!-- Create Blog Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
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

                    <form id="ajaxForm" enctype="multipart/form-data" class="modal-form"
                        action="{{ route('user.portfolio.store') }}" method="POST">
                        @csrf
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
                    <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
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
@endsection
