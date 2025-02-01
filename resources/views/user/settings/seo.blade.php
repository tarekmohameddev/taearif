@extends('user.layout')

@php
    $selLang = \App\Models\User\Language::where([['code', \Illuminate\Support\Facades\Session::get('currentLangCode')], ['user_id', \Illuminate\Support\Facades\Auth::id()]])->first();
    $userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::id()], ['is_default', 1]])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();

    $packageFeatures = App\Http\Helpers\UserPermissionHelper::packagePermission(Auth::id());
    $packageFeatures = json_decode($packageFeatures, true);

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
            .bootstrap-tagsinput {
                display: block !important;
            }
        </style>
    @endsection
@endif

@section('content')
    <!-- <div class="page-header">
        <h4 class="page-title">{{ __('SEO Informations') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Basic Settings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('SEO Informations') }}</a>
            </li>
        </ul>
    </div> -->

    <div class="row">
        <div class="col-md-12">
            <div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
                <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100">
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
                        <h2 class="fs-4 fw-semibold mb-2">{{ __('SEO Informations') }}</h2>
                        <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                        {{ __('SEO Informations') }}
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
                <form action="{{ route('user.basic_settings.update_seo_informations') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-9">
                                <div class="card-title">{{ __('Update SEO Informations') }}</div>
                            </div>

                            <div class="col-lg-3">
                                @if (!is_null($userDefaultLang))
                                    @if (!empty($userLanguages))
                                        <select name="language" class="form-control float-right"
                                            onchange="window.location='{{ url()->current() . '?language=' }}'+this.value">
                                            <option value="" selected disabled>{{ __('Select a Language') }}
                                            </option>
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

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Home Page') }}</label>
                                    <input class="form-control" name="home_meta_keywords"
                                        value="{{ $data->home_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Home Page') }}</label>
                                    <textarea class="form-control" name="home_meta_description" rows="5" placeholder="Enter Meta Description">{{ $data->home_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Blog Page') }}</label>
                                    <input class="form-control" name="blogs_meta_keywords"
                                        value="{{ $data->blogs_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Blog Page') }}</label>
                                    <textarea class="form-control" name="blogs_meta_description" rows="5" placeholder="Enter Meta Description">{{ $data->blogs_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Services Page') }}</label>
                                    <input class="form-control" name="services_meta_keywords"
                                        value="{{ $data->services_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Services Page') }}</label>
                                    <textarea class="form-control" name="services_meta_description" rows="5" placeholder="Enter Meta Description">{{ $data->services_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Portfolios Page') }}</label>
                                    <input class="form-control" name="portfolios_meta_keywords"
                                        value="{{ $data->portfolios_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Portfolios Page') }}</label>
                                    <textarea class="form-control" name="portfolios_meta_description" rows="5" placeholder="Enter Meta Description">{{ $data->portfolios_meta_description }}</textarea>
                                </div>
                            </div>


                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Jobs Page') }}</label>
                                    <input class="form-control" name="jobs_meta_keywords"
                                        value="{{ $data->jobs_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Jobs Page') }}</label>
                                    <textarea class="form-control" placeholder="Enter Meta Description" name="jobs_meta_description" rows="5">{{ $data->jobs_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Team Page') }}</label>
                                    <input class="form-control" name="team_meta_keywords"
                                        value="{{ $data->team_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Team Page') }}</label>
                                    <textarea class="form-control" name="team_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->team_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For FAQ Page') }}</label>
                                    <input class="form-control" name="faqs_meta_keywords"
                                        value="{{ $data->faqs_meta_keywords }}"
                                        placeholder="Enter Meta Keywords"data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For FAQ Page') }}</label>
                                    <textarea class="form-control" name="faqs_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->faqs_meta_description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Contact Page') }}</label>
                                    <input class="form-control" name="contact_meta_keywords"
                                        value="{{ $data->contact_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Meta Description For Contact Page') }}</label>
                                    <textarea class="form-control" name="contact_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->contact_meta_description }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Shop Page') }}</label>
                                    <input class="form-control" name="shop_meta_keywords"
                                        value="{{ $data->shop_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Shop Page') }}</label>
                                    <textarea class="form-control" name="shop_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->shop_meta_description }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Item Details Page') }}</label>
                                    <input class="form-control" name="item_details_meta_keywords"
                                        value="{{ $data->item_details_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Item Details Page') }}</label>
                                    <textarea class="form-control" name="item_details_meta_description" placeholder="Enter Meta Description"
                                        rows="5">{{ $data->item_details_meta_description }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Cart Page') }}</label>
                                    <input class="form-control" name="cart_meta_keywords"
                                        value="{{ $data->cart_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Cart Page') }}</label>
                                    <textarea class="form-control" name="cart_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->cart_meta_description }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Checkout Page') }}</label>
                                    <input class="form-control" name="checkout_meta_keywords"
                                        value="{{ $data->checkout_meta_keywords }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Checkout Page') }}</label>
                                    <textarea class="form-control" name="checkout_meta_description" placeholder="Enter Meta Description" rows="5">{{ $data->checkout_meta_description }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Login Page') }}</label>
                                    <input class="form-control" name="meta_keyword_login"
                                        value="{{ $data->meta_keyword_login }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Login Page') }}</label>
                                    <textarea class="form-control" name="meta_description_login" placeholder="Enter Meta Description" rows="5">{{ $data->meta_description_login }}</textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Meta Keywords For Signup Page') }}</label>
                                    <input class="form-control" name="meta_keyword_signup"
                                        value="{{ $data->meta_keyword_signup }}" placeholder="Enter Meta Keywords"
                                        data-role="tagsinput">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Meta Description For Signup Page') }}</label>
                                    <textarea class="form-control" name="meta_description_signup" placeholder="Enter Meta Description" rows="5">{{ $data->meta_description_signup }}</textarea>
                                </div>
                            </div>
                            @if (in_array('Hotel Booking', $packageFeatures))
                                <div class="col-lg-4 col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Rooms Page') }}</label>
                                        <input class="form-control" name="meta_keyword_rooms"
                                            value="{{ $data->meta_keyword_rooms }}" placeholder="Enter Meta Keywords"
                                            data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Rooms Page') }}</label>
                                        <textarea class="form-control" name="meta_description_rooms" placeholder="Enter Meta Description" rows="5">{{ $data->meta_description_rooms }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Rooms Details Page') }}</label>
                                        <input class="form-control" name="meta_keyword_room_details"
                                            value="{{ $data->meta_keyword_room_details }}"
                                            placeholder="Enter Meta Keywords" data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Rooms Details Page') }}</label>
                                        <textarea class="form-control" name="meta_description_room_details" placeholder="Enter Meta Description"
                                            rows="5">{{ $data->meta_description_room_details }}</textarea>
                                    </div>
                                </div>
                            @endif
                            @if (in_array('Course Management', $packageFeatures))
                                <div class="col-lg-4 col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Course Page') }}</label>
                                        <input class="form-control" name="meta_keyword_course"
                                            value="{{ $data->meta_keyword_course }}" placeholder="Enter Meta Keywords"
                                            data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Course Page') }}</label>
                                        <textarea class="form-control" name="meta_description_course" placeholder="Enter Meta Description" rows="5">{{ $data->meta_description_course }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Course Details Page') }}</label>
                                        <input class="form-control" name="meta_keyword_course_details"
                                            value="{{ $data->meta_keyword_course_details }}"
                                            placeholder="Enter Meta Keywords" data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Course Details Page') }}</label>
                                        <textarea class="form-control" name="meta_description_course_details" placeholder="Enter Meta Description"
                                            rows="5">{{ $data->meta_description_course_details }}</textarea>
                                    </div>
                                </div>
                            @endif

                            @if (in_array('Course Management', $packageFeatures))
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Properties Page') }}</label>
                                        <input class="form-control" name="meta_keyword_properties"
                                            value="{{ $data->meta_keyword_properties }}"
                                            placeholder="Enter Meta Keywords" data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Properties Page') }}</label>
                                        <textarea class="form-control" name="meta_description_properties" placeholder="Enter Meta Description"
                                            rows="5">{{ $data->meta_description_properties }}</textarea>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('Meta Keywords For Projects Page') }}</label>
                                        <input class="form-control" name="meta_keyword_projects"
                                            value="{{ $data->meta_keyword_projects }}" placeholder="Enter Meta Keywords"
                                            data-role="tagsinput">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Meta Description For Projects Page') }}</label>
                                        <textarea class="form-control" name="meta_description_projects" placeholder="Enter Meta Description" rows="5">{{ $data->meta_description_projects }}</textarea>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="form">
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit"
                                        class="btn btn-success {{ $data == null ? 'd-none' : '' }}">{{ __('Update') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
