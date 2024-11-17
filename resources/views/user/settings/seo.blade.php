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
        </style>
    @endsection
@endif

@section('content')
    <div class="page-header">
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
    </div>

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

                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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


                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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

                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
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
                                <div class="col-lg-6">
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
                                <div class="col-lg-6">
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
                                <div class="col-lg-6">
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
                                <div class="col-lg-6">
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
