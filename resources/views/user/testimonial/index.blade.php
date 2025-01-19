@extends('user.layout')

@php
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
    Config::set('app.timezone', $userBs->timezoneinfo->timezone);
@endphp

@php
    $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::user()->id);
    $permissions = json_decode($permissions, true);
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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Testimonials') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    قم بتعديل قسم اراء العملاء, وأظهر ما يقوله العملاء عن شركتك
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
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <form id="ajaxForm" action="{{ route('user.home.page.text.update') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $home_setting->id }}">
                                <input type="hidden" name="language_id" value="{{ $home_setting->language_id }}">


                                @if (
                                    $userBs->theme != 'home_eight' ||
                                        ($userBs->theme != 'home_ten' && !empty($permissions) && in_array('Testimonial', $permissions)))
                                    <div class="row">
                                        <div class="col-12">

                                            @if ($userBs->theme == 'home_six' || $userBs->theme == 'home_one' || $userBs->theme == 'home_ten')
                                            <!-- errtestimonial_image Section -->
                                                <div class="form-group">
                                                    <div class="col-12 mb-2">
                                                        <label
                                                            for="logo"><strong>{{ __('Testimonial Image') }}</strong></label>
                                                    </div>
                                                    <div class="col-md-12 preview-image showTestimonialImage mb-3">
                                                        <img src="{{ $home_setting->testimonial_image ? asset('assets/front/img/user/home_settings/' . $home_setting->testimonial_image) : asset('assets/admin/img/noimage.jpg') }}"
                                                            alt="..." class="img-thumbnail">
                                                    </div>
                                                    <input type="file" name="testimonial_image" id="testimonial_image"
                                                        class="d-none">
                                                        <button type="button" class="upload-btn" onclick="document.getElementById('testimonial_image').click()">
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
                                                            <label
                                                                for="">{{ __('Testimonial Section Title') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="testimonial_title">
                                                            <input type="text" class="form-control"
                                                                name="testimonial_title" placeholder=""
                                                                value="{{ $home_setting->testimonial_title }}">
                                                            <p id="errtestimonial_title" class="mb-0 text-danger em"></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 pl-0">
                                                        <div class="form-group">
                                                            <label
                                                                for="">{{ __('Testimonial Section Subtitle') }}</label>
                                                            <input type="hidden" name="types[]"
                                                                value="testimonial_subtitle">
                                                            <input type="text" class="form-control"
                                                                name="testimonial_subtitle" placeholder=""
                                                                value="{{ $home_setting->testimonial_subtitle }}">
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
                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal"
                                data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Testimonial') }}</a>
                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete"
                                data-href="{{ route('user.testimonial.bulk.delete') }}"><i
                                    class="flaticon-interface-5"></i> {{ __('Delete') }}</button>
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
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (is_null($userDefaultLang))
                                <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                            @else
                                @if (count($testimonials) == 0)
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
                                                @foreach ($testimonials as $key => $testimonial)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="bulk-check"
                                                                data-val="{{ $testimonial->id }}">
                                                        </td>
                                                        @if ($userBs->theme !== 'home_nine')
                                                            <td><img src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}"
                                                                    alt="" width="80"></td>
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
                                                            <a class="btn btn-secondary btn-sm"
                                                                href="{{ route('user.testimonial.edit', $testimonial->id) . '?language=' . $testimonial->language->code }}">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>
                                                                {{ __('Edit') }}
                                                            </a>
                                                            <form class="deleteform d-inline-block"
                                                                action="{{ route('user.testimonial.delete') }}"
                                                                method="post">
                                                                @csrf
                                                                <input type="hidden" name="id"
                                                                    value="{{ $testimonial->id }}">
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
    <!-- Create Blog Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Testimonial') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <form id="ajaxForm" enctype="multipart/form-data" class="modal-form"
                        action="{{ route('user.testimonial.store') }}" method="POST">
                        @csrf
                        @if ($userBs->theme !== 'home_nine')
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <div class="col-12 mb-2">
                                            <label for="image"><strong>{{ __('Image') }}*</strong></label>
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
  @endsection
