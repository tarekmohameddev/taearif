@extends('user.layout')

@section('content')

    <div class="page-header">
        <h4 class="page-title">{{ __('Add Porperty') }}</h4>
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
                <a href="#">{{ __('Real Estate Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Manage Property') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Add Porperty') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add Porperty') }}</div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="alert alert-danger pb-1 " style="display: none;" id="propertyErrors">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>
                            {{-- <div class="col-lg-12">
                                <label for="" class="mb-2"><strong>{{ __('Gallery Images') }}
                                        *</strong></label>
                                <form action="{{ route('user.property.imagesstore') }}" id="myDropzoneI" enctype="multipart/form-data"
                                    class="dropzone create">
                                    @csrf
                                    <div class="fallback">
                                        <input name="file" type="file" multiple />
                                    </div>
                                </form>
                                <p class="em text-danger mb-0" id="errslider_images"></p>
                            </div> --}}
                            <form id="propertyForm" action="{{ route('user.property_management.store_property') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="type" value="{{ request()->type }}">
                                {{-- <div id="sliders"></div> --}}
                                <div class="row">


                                    <div class="col-lg-12">
                                        <label for="" class="mb-2"><strong>{{ __('Gallery Images') . '*' }}
                                            </strong></label>
                                        <div class=" dropzone create" id="myDropzoneI">
                                            <div class="fallback">
                                                <input name="file" type="file" multiple />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Thumbnail Image') . '*' }}</label>
                                            <br>
                                            <div class="showImage">
                                                <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..."
                                                    class="img-thumbnail">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control " id="image"
                                                    name="featured_image">
                                            </div>
                                            <p id="errfeatured_image" class=" mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Floor Planning Image') . '*' }}</label>
                                            <br>
                                            <div class="showImage2">
                                                <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..."
                                                    class="img-thumbnail">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control " id="image2"
                                                    name="floor_planning_image">
                                            </div>
                                            <p id="errimage" class=" mb-0 text-danger em"></p>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Video Image') }}</label>
                                            <br>
                                            <div class="showImage3">
                                                <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..."
                                                    class="img-thumbnail">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control" id="image3"
                                                    name="video_image">
                                            </div>
                                            <p id="errvideo_image" class=" mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Video Url') }} </label>
                                            <input type="text" class="form-control" name="video_url"
                                                placeholder="{{ __('Enter video url') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Purpose') }}*</label>

                                            <select name="purpose" class="form-control">
                                                <option selected disabled value="">
                                                    {{ __('Select Purpose') }}
                                                </option>
                                                <option value="rent">{{ __('Rent') }}</option>
                                                <option value="sale">{{ __('Sale') }}</option>
                                            </select>
                                        </div>

                                    </div>


                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Price') . ' (' . $userBs->base_currency_text . ')' }}
                                            </label>
                                            <input type="number" class="form-control" name="price"
                                                placeholder="{{ __('Enter Current Price') }}">

                                            <p class="text-warning">
                                                {{ __('If you leave it blank, price will be negotiable.') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if (request('type') == 'residential')
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label>{{ __('Beds') }} *</label>
                                                <input type="text" class="form-control" name="beds"
                                                    placeholder="{{ __('Enter number of bed') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label>{{ __('Baths') }} *</label>
                                                <input type="text" class="form-control" name="bath"
                                                    placeholder="{{ __('Enter number of bath') }}">
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Area (sqft)') }} *</label>
                                            <input type="text" class="form-control" name="area"
                                                placeholder="{{ __('Enter area (sqft)') }} ">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Status') }} *</label>
                                            <select name="status" id="" class="form-control">
                                                <option value="1">{{ __('Active') }}</option>
                                                <option value="0">{{ __('Deactive') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Latitude') }} * </label>
                                            <input type="text" class="form-control" name="latitude"
                                                placeholder="{{ __('Enter Latitude') }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Longitude') }} * </label>
                                            <input type="text" class="form-control" name="longitude"
                                                placeholder="{{ __('Enter Longitude') }}">
                                        </div>
                                    </div>
                                </div>


                                <div id="accordion" class="mt-3 custom-accordion px-2">
                                    @foreach ($languages as $language)
                                        <div class="version">
                                            <div class="version-header " id="heading{{ $language->id }}">
                                                <h5 class="mb-0">
                                                    <button type="button" class="btn accordion-btn"
                                                        data-toggle="collapse" data-target="#collapse{{ $language->id }}"
                                                        aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                                        aria-controls="collapse{{ $language->id }}">
                                                        {{ $language->name . __(' Language') }}
                                                        {{ $language->is_default == 1 ? '(Default)' : '' }}

                                                        <span class="caret"></span>
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="collapse{{ $language->id }}"
                                                class="collapse {{ $language->is_default == 1 ? 'show' : '' }}"
                                                aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                                                <div class="version-body">
                                                    <div class="row">
                                                        @php
                                                            $propertyCategories = $language
                                                                ->propertyCategories()
                                                                ->where('type', request()->input('type'))
                                                                ->where('status', 1)
                                                                ->get();
                                                        @endphp
                                                        <div class="col-lg-4">
                                                            <div
                                                                class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Category') }} *</label>
                                                                <select name="{{ $language->code }}_category_id"
                                                                    class="form-control category">
                                                                    <option disabled selected>
                                                                        {{ __('Select Category') }}
                                                                    </option>

                                                                    @foreach ($propertyCategories as $category)
                                                                        <option value="{{ $category->id }}">
                                                                            {{ $category->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        @if ($propertySettings->property_country_status == 1)
                                                            @php

                                                            @endphp
                                                            <div class="col-lg-4">
                                                                <div
                                                                    class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">


                                                                    <label>{{ __('Country') }} *</label>
                                                                    <select name="{{ $language->code }}_country_id"
                                                                        class="form-control country js-example-basic-single">
                                                                        <option disabled selected>
                                                                            {{ __('Select Country') }}
                                                                        </option>

                                                                        @foreach ($language->propertyCountries as $country)
                                                                            <option value="{{ $country->id }}">
                                                                                {{ $country->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if ($propertySettings->property_state_status == 1)
                                                            <div class="col-lg-4 state">
                                                                <div
                                                                    class="form-group   {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">

                                                                    <label>{{ __('State') }} *</label>
                                                                    <select onchange="getCities(event)"
                                                                        name="{{ $language->code }}_state_id"
                                                                        class="form-control state_id states js-example-basic-single3">
                                                                        <option selected disabled>
                                                                            {{ __('Select State') }}
                                                                        </option>
                                                                        @if ($propertySettings->property_country_status != 1)
                                                                            @foreach ($language->propertyStates as $state)
                                                                                <option value="{{ $state->id }}">
                                                                                    {{ $state->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <div class="col-lg-4 city">
                                                            <div
                                                                class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('City') }} *</label>
                                                                <select name="{{ $language->code }}_city_id"
                                                                    class="form-control city_id js-example-basic-single3">
                                                                    <option selected disabled>
                                                                        {{ __('Select City') }}
                                                                    </option>

                                                                    @if ($propertySettings->property_state_status == 0 && $propertySettings->property_country_status == 0)
                                                                        @foreach ($language->propertyCities as $city)
                                                                            <option value="{{ $city->id }}">
                                                                                {{ $city->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-4">
                                                            <div
                                                                class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label for="">{{ __('Amenity') }}*</label>
                                                                <select name="{{ $language->code }}_amenities[]"
                                                                    class="form-control js-example-basic-multiple"
                                                                    multiple>
                                                                    <option value="" se></option>
                                                                    @foreach ($language->propertyAmenities as $amenity)
                                                                        <option value="{{ $amenity->id }}">
                                                                            {{ $amenity->name }}</option>
                                                                    @endforeach
                                                                </select>

                                                            </div>
                                                        </div>

                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Title') . '*' }}</label>
                                                                <input type="text" class="form-control"
                                                                    name="{{ $language->code }}_title"
                                                                    placeholder="{{ __('Enter Title') }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ $keywords['Address'] ?? __('Address') . '*' }}</label>
                                                                <input type="text"
                                                                    name="{{ $language->code }}_address"
                                                                    class="form-control"
                                                                    placeholder="{{ $keywords['Enter Address'] ?? __('Enter Address') }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Description') . '*' }}</label>
                                                                <textarea id="{{ $language->code }}_PostContent" class="form-control summernote"
                                                                    name="{{ $language->code }}_description" placeholder="{{ __('Enter Content') }}" data-height="300"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Meta keyword') }}</label>
                                                                <input class="form-control"
                                                                    name="{{ $language->code }}_keyword"
                                                                    placeholder="{{ __('Enter Meta Keywords') }}"
                                                                    data-role="tagsinput">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Meta Descroption') }}</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_meta_keyword" rows="5"
                                                                    placeholder="{{ __('Enter Meta Descroption') }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            @php $currLang = $language; @endphp
                                                            @foreach ($languages as $lang)
                                                                @continue($lang->id == $currLang->id)
                                                                <div class="form-check py-0">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $lang->id }}', event)">
                                                                        <span
                                                                            class="form-check-sign">{{ __('Clone for') }}
                                                                            <strong
                                                                                class="text-capitalize text-secondary">{{ $lang->name }}</strong>
                                                                            {{ __('language') }}</span>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="row">
                                    <div class="col-lg-12" id="variation_pricing">
                                        <h4 for="">
                                            {{ ($keywords['Additional Specifications'] ?? __('Additional Specifications')) . ' (' . ($keywords['Optional'] ?? __('Optional')) . ')' }}

                                        </h4>
                                        <table class="table table-bordered ">
                                            <thead>
                                                <tr>
                                                    <th>{{ $keywords['Label'] ?? __('Label') }}</th>
                                                    <th>{{ $keywords['Value'] ?? __('Value') }}</th>
                                                    <th><a href="" class="btn btn-sm btn-success addRow"><i
                                                                class="fas fa-plus-circle"></i></a></th>
                                                </tr>
                                            <tbody id="tbody">
                                                <tr>
                                                    <td>
                                                        @foreach ($languages as $language)
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <input type="text"
                                                                    name="{{ $language->code }}_label[]"
                                                                    class="form-control"
                                                                    placeholder="{{ ($keywords['Label for'] ?? __('Label for')) . ' ' . $language->name . ' ' . ($keywords['language'] ?? __('language')) }}">
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($languages as $language)
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <input type="text"
                                                                    name="{{ $language->code }}_value[]"
                                                                    class="form-control"
                                                                    placeholder="{{ ($keywords['Value for'] ?? __('Value for')) . ' ' . $language->name . ' ' . ($keywords['language'] ?? __('language')) }}">
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        <a href="javascript:void(0)"
                                                            class="btn btn-danger  btn-sm deleteRow">
                                                            <i class="fas fa-minus"></i></a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="propertySubmit" class="btn btn-success">
                                {{ $keywords['Save'] ?? __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    // $languages = App\Models\Language::get();
    $labels = '';
    $values = '';

    foreach ($languages as $language) {
        $labels_placeholder = __('Label for') . ' ' . $language->name . ' ' . __('language');
        $values_placeholder = __('Value for') . ' ' . $language->name . ' ' . __('language');

        $label_name = $language->code . '_label[]';
        $value_name = $language->code . '_value[]';
        if ($language->rtl == 1) {
            $direction = 'form-group rtl text-right';
        } else {
            $direction = 'form-group';
        }

        $labels .=
            "<div class='$direction'><input type='text' name='" .
            $label_name .
            "' class='form-control' placeholder='$labels_placeholder'></div>";
        $values .= "<div class='$direction'><input type='text' name='$value_name' class='form-control' placeholder='$values_placeholder'></div>";
    }
@endphp

{{-- // var storeUrl = "{{ route('user.property.imagesstore') }}"; --}}
{{-- var removeUrl = "{{ route('user.property.imagermv') }}"; --}}
@section('scripts')
    <script>
        'use strict';
        var labels = "{!! $labels !!}";
        var values = "{!! $values !!}";
        var stateUrl = "{{ route('user.property_management.get_state_cities', ':countryId') }}";

        let cityUrl = "{{ route('user.property_management.get_cities') }}";
    </script>

    <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-partial.js') }}"></script>



    <script type="text/javascript" src="{{ asset('assets/tenant/js/property-dropzone.js') }}"></script>
    {{-- <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-dropzone.js') }}"></script> --}}
    <script type="text/javascript" src="{{ asset('assets/tenant/js/property.js') }}"></script>
@endsection
