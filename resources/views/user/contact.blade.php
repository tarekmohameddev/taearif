@extends('user.layout')
@php
    $userDefaultLang = \App\Models\User\Language::where([
    ['user_id',\Illuminate\Support\Facades\Auth::id()],
    ['is_default',1]
    ])->first();
    $userLanguages = \App\Models\User\Language::where('user_id',\Illuminate\Support\Facades\Auth::id())->get();
@endphp

@includeIf('user.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Contact Page') }}</h4>
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
                <a href="#">{{ __('Contact Page') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Update Contact') }}</div>
                        </div>

                        <div class="col-lg-2">
                            @if(!is_null($userDefaultLang))
                                @if (!empty($userLanguages))
                                    <select name="userLanguage" class="form-control" onchange="window.location='{{url()->current() . '?language='}}'+this.value">
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

                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form
                                id="contactSecForm"
                                action="{{ route('user.contact.update', ['language' => request()->input('language')]) }}"
                                method="POST"
                                enctype="multipart/form-data"
                            >
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <div class="col-12 mb-2">
                                                <label for="image"><strong>{{__('Contact Form Image')}}</strong></label>
                                            </div>
                                            <div class="col-md-12 showImage mb-3">
                                                <img
                                                    src="{{ isset($data->contact_form_image) ? asset('assets/front/img/user/'.$data->contact_form_image) : asset('assets/admin/img/noimage.jpg')}}"
                                                    alt="..." class="img-thumbnail">
                                            </div>
                                            <input type="file" name="contact_form_image" id="image"
                                                   class="form-control">
                                            @if ($errors->has('contact_form_image'))
                                                <p class="mt-2 mb-0 text-danger">{{ $errors->first('contact_form_image') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Form Title') }}</label>
                                    <input type="text" class="form-control" name="contact_form_title"
                                           value="{{  $data->contact_form_title ?? '' }}" placeholder="Enter title">
                                    @if ($errors->has('contact_form_title'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('contact_form_title') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label for="">{{ __('Form Subtitle') }}</label>
                                    <input type="text" class="form-control" name="contact_form_subtitle"
                                           value="{{  $data->contact_form_subtitle ?? '' }}" placeholder="Enter Subtitle">
                                    @if ($errors->has('contact_form_subtitle'))
                                        <p class="mt-2 mb-0 text-danger">{{ $errors->first('contact_form_subtitle') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{__('Address')}}</label>
                                    <textarea class="form-control" name="contact_addresses" rows="3">{{$data->contact_addresses ?? null}}</textarea>
                                    <p class="mb-0 text-warning">{{__('Use newline to seperate multiple addresses.')}}</p>
                                    @if ($errors->has('contact_addresses'))
                                        <p class="mb-0 text-danger">{{$errors->first('contact_addresses')}}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{__('Phone')}}</label>
                                    <input class="form-control" name="contact_numbers" data-role="tagsinput" value="{{$data->contact_numbers ?? null}}" />
                                    <p class="mb-0 text-warning">{{__('Use comma (,) to seperate multiple contact numbers.')}}</p>
                                    @if ($errors->has('contact_numbers'))
                                        <p class="mb-0 text-danger">{{$errors->first('contact_numbers')}}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{__('Email')}}</label>
                                    <input class="form-control ltr" name="contact_mails" data-role="tagsinput" value="{{$data->contact_mails ?? null}}" >
                                    <p class="mb-0 text-warning">{{__('Use comma (,) to seperate multiple contact mails.')}}</p>
                                    @if ($errors->has('contact_mails'))
                                        <p class="mb-0 text-danger">{{$errors->first('contact_mails')}}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{__('Latitude')}} </label>
                                    <input class="form-control" name="latitude" value="{{$data->latitude ?? null}}">
                                    @if ($errors->has('latitude'))
                                        <p class="mb-0 text-danger">{{$errors->first('latitude')}}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{__('Longitude')}}</label>
                                    <input class="form-control" name="longitude" value="{{$data->longitude ?? null}}">
                                    @if ($errors->has('longitude'))
                                        <p class="mb-0 text-danger">{{$errors->first('longitude')}}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{__('Map Zoom')}}</label>
                                    <input class="form-control" name="map_zoom" value="{{$data->map_zoom ?? 12}}">
                                    @if ($errors->has('map_zoom'))
                                        <p class="mb-0 text-danger">{{$errors->first('map_zoom')}}</p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="contactSecForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
