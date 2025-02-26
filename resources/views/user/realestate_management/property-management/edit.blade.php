@extends('user.layout')

@section('styles')
    <style>
        #imgtable .table-row {
            display: inline-block;
            position: relative;
            margin-right: 15px;
            margin-bottom: 15px;
        }

        .wf-200 {
            width: 200px;
        }

        #imgtable .table-row td i {
            position: absolute;
            top: -7px;
            right: -7px;
            color: #ff3737;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0px 0px 8px #888888;
            font-size: 20px;
            cursor: pointer;
            padding: 5px 8px;
            height: 30px;
            width: 30px;
        }
    </style>
    <style>
    #map {
        width: 100%;
        height: 250px;
        max-width: 600px;
    }
    </style>

@endsection
@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Edit Property') }}</h4>
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
                <a href="#">{{ __('Property Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Edit Property') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Edit Property') }}</div>
                    {{-- <a class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('user.property_management.properties', ['language' => $defaultLang->code]) }}">
                        <span class="btn-label">
                            <i class="fas fa-backward"></i>
                        </span>
                        {{ __('Back') }}
                    </a> --}}
                    {{-- @php
                        $dContent = App\Models\Property\Content::where('property_id', $property->id)
                            ->where('language_id', $defaultLang->id)
                            ->first();
                        $slug = !empty($dContent) ? $dContent->slug : '';
                    @endphp
                    @if ($dContent)
                        <a class="btn btn-success btn-sm float-right mr-1 d-inline-block"
                            href="{{ route('frontend.property.details', ['slug' => $slug]) }}" target="_blank">
                            <span class="btn-label">
                                <i class="fas fa-eye"></i>
                            </span>
                            {{ __('Preview') }}
                        </a>
                    @endif --}}

                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1">
                            <div class="alert alert-danger pb-1 " style="display:none" id="propertyErrors">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>

                            <div class="col-lg-12">
                                {{-- <label for=""
                                    class="mb-2"><strong>{{ __('Gallery Images') . '*' }}</strong></label>
                                <div id="reload-slider-div">
                                    <div class="row">

                                        <div class="col-12">
                                            <table class="table table-striped" id="imgtable">

                                                @foreach ($galleryImages as $item)
                                                    <tr class="trdb table-row" id="trdb{{ $item->id }}">
                                                        <td>
                                                            <div class="">
                                                                <img class="thumb-preview wf-200"
                                                                    src="{{ asset('assets/img/property/slider-images/' . $item->image) }}"
                                                                    alt="Ad Image">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <i
                                                                class="fa fa-times rmvbtndb
                                                                data-indb="{{ $item->id }}"></i>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div> --}}

                                {{-- <form action="{{ route('user.property.imagesstore') }}" id="my-dropzone" enctype="multipart/formdata"
                                    class="dropzone create">
                                    @csrf
                                    <div class="fallback">
                                        <input name="file" type="file" multiple />
                                    </div>
                                    <input type="hidden" value="{{ $property->id }}" name="property_id">
                                </form>
                                <p class="em text-danger mb-0" id="errslider_images"></p> --}}

                            </div>

                            <form id="propertyForm"
                                action="{{ route('user.property_management.update_property', $property->id) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="property_id" value="{{ $property->id }}">
                                <input type="hidden" name="type" value="{{ $property->type }}">
                                <input type="hidden" name="vendor_id" value="{{ $property->vendor_id }}">


                                <div class="row">
                                    <div class="col-lg-12">
                                        <label for=""
                                            class="mb-2"><strong>{{ __('Gallery Images') . '*' }}</strong></label>
                                        <div id="reload-slider-div">
                                            <div class="row">

                                                <div class="col-12">
                                                    <table class="table table-striped" id="imgtable">

                                                        @foreach ($galleryImages as $item)
                                                            <tr class="trdb table-row" id="trdb{{ $item->id }}">
                                                                <td>
                                                                    <div class="">
                                                                        <img class="thumb-preview wf-200"
                                                                            src="{{ asset('assets/img/property/slider-images/' . $item->image) }}"
                                                                            alt="Ad Image">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <i class="fa fa-times rmvbtndb"
                                                                        data-indb="{{ $item->id }}"></i>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        {{-- <label for="" class="mb-2"><strong>{{ __('Gallery Images') . '*' }}
                                            </strong></label> --}}
                                        <div class=" dropzone create" id="myDropzoneI">
                                            <div class="fallback">
                                                <input name="file" type="file" multiple />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    {{-- <div class="col-lg-4">
                                        <div class="form-group">
                                            <label
                                                for="">{{ $keywords['Thumbnail Image'] ?? __('Thumbnail Image') . '*' }}</label>
                                            <br>
                                            <div class="thumb-preview">
                                                <img src="{{ $property->featured_image ? asset('assets/img/property/featureds/' . $property->featured_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="uploaded-img">
                                            </div>
                                            <div class="mt-3">
                                                <div role="button" class="btn btn-primary btn-sm upload-btn">
                                                    {{ $keywords['Choose Image'] ?? __('Choose Image') }}
                                                    <input type="file" class="img-input" name="featured_image">
                                                </div>
                                            </div>
                                        </div>
                                    </div> --}}

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Thumbnail Image') . '*' }}</label>
                                            <br>
                                            <div class="showImage">
                                                <img src="{{ $property->featured_image ? asset('assets/img/property/featureds/' . $property->featured_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="img-thumbnail img-fluid">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control " id="image"
                                                    name="featured_image">
                                            </div>
                                            <p id="errfeatured_image" class=" mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    {{-- <div class="col-lg-4">
                                        <div class="form-group">
                                            <label
                                                for="">{{ $keywords['Floor Planning Image'] ?? __('Floor Planning Image') }}</label>
                                            <br>
                                            <div class="thumb-preview remove">

                                                <img src="{{ !empty($property->floor_planning_image) ? asset('assets/img/property/plannings/' . $property->floor_planning_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="uploaded-img2">
                                                @if (!empty($property->floor_planning_image))
                                                    <i class="fas fa-times text-danger rmvflrImg"
                                                        data-indb="{{ $property->id }}"></i>
                                                @endif
                                            </div>

                                            <div class="mt-3">
                                                <div role="button" class="btn btn-primary btn-sm upload-btn">
                                                    {{ $keywords['Choose Image'] ?? __('Choose Image') }}
                                                    <input type="file" class="img-input2" name="floor_planning_image">
                                                </div>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Floor Planning Image') . '*' }}</label>
                                            <br>
                                            <div class="showImage2">
                                                <img src="{{ !empty($property->floor_planning_image) ? asset('assets/img/property/plannings/' . $property->floor_planning_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="img-thumbnail img-fluid">
                                            </div>

                                            <div class="mt-3">
                                                <input type="file" class="form-control " id="image2"
                                                    name="floor_planning_image">
                                            </div>
                                            <p id="errimage" class=" mb-0 text-danger em"></p>
                                        </div>
                                    </div>
                                    {{-- <div class="col-lg-4">
                                        <div class="form-group">
                                            <label
                                                for="">{{ $keywords['Video Image'] ?? __('Video Image') }}</label>
                                            <br>
                                            <div class="thumb-preview remove">

                                                <img src="{{ !empty($property->video_image) ? asset('assets/img/property/video/' . $property->video_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="uploaded-img3">
                                                @if (!empty($property->video_image))
                                                    <i class="fas fa-times text-danger rmvvdoImg"
                                                        data-indb="{{ $property->id }}"></i>
                                                @endif
                                            </div>

                                            <div class="mt-3">
                                                <div role="button" class="btn btn-primary btn-sm upload-btn">
                                                    {{ $keywords['Choose Image'] ?? __('Choose Image') }}
                                                    <input type="file" class="img-input3" name="video_image">
                                                </div>
                                            </div>
                                        </div>
                                    </div> --}}

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="">{{ __('Video Image') }}</label>
                                            <br>
                                            <div class="showImage3">
                                                <img src="{{ !empty($property->video_image) ? asset('assets/img/property/video/' . $property->video_image) : asset('assets/img/noimage.jpg') }}"
                                                    alt="..." class="img-thumbnail img-fluid">
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
                                            <label>{{ $keywords['Video Url'] ?? __('Video Url') }} </label>
                                            <input type="text" class="form-control" name="video_url"
                                                placeholder="{{ $keywords['Enter video url'] ?? __('Enter video url') }}"
                                                value="{{ $property->video_url }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ $keywords['Purpose'] ?? __('Purpose') }}*</label>

                                            <select name="purpose" class="form-control">
                                                <option value="" {{ empty($property->purpose) ? 'selected' : '' }}>
                                                    {{ $keywords['Select a Purpose'] ?? __('Select a Purpose') }}
                                                </option>
                                                <option value="rent" {{ $property->purpose == 'rent' ? 'selected' : '' }}>
                                                    {{ $keywords['Rent'] ?? __('Rent') }}
                                                </option>
                                                <option value="sale" {{ $property->purpose == 'sale' ? 'selected' : '' }}>
                                                    {{ $keywords['Sale'] ?? __('Sale') }}
                                                </option>
                                            </select>
                                        </div>


                                    </div>



                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Price') . ' (' . $userBs->base_currency_text . ')' }}
                                            </label>
                                            <input type="number" class="form-control" name="price"
                                                placeholder="{{ $keywords['Enter Current Price'] ?? __('Enter Current Price') }}"
                                                value="{{ $property->price }}">
                                            <p class="text-warning">
                                                {{ $keywords['If you leave it blank, price will be negotiable'] ?? __('If you leave it blank, price will be negotiable.') }}
                                            </p>
                                        </div>
                                    </div>


                                    @if ($property->type == 'residential')
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label>{{ __('Beds') }} *</label>
                                                <input type="text" class="form-control" name="beds"
                                                    value="{{ $property->beds }}"
                                                    placeholder="{{ $keywords['Enter number of bed'] ?? __('Enter number of bed') }}">
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label>{{ __('Baths') }} *</label>
                                                <input type="text" class="form-control" name="bath"
                                                    value="{{ $property->bath }}"
                                                    placeholder="{{ __('Enter number of bath') }}">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label>{{ __('Area (sqft)') }} *</label>
                                            <input type="text" class="form-control" name="area"
                                                value="{{ $property->area }}"
                                                placeholder="{{ $keywords['Enter area (sqft)'] ?? __('Enter area (sqft)') }} ">
                                        </div>
                                    </div>

                                    <div class="col-lg-3 d-none">
                                        <div class="form-group">
                                            <label>{{ __('Status') }} *</label>
                                            <select name="status" id="" class="form-control">
                                                <option {{ $property->status == 1 ? 'selected' : '' }} value="1">
                                                    {{ __('Active') }}</option>
                                                <option {{ $property->status == 0 ? 'selected' : '' }} value="0">
                                                    {{ __('Deactive') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                    </div>

        <!-- Latitude -->
        <div class="col-lg-3">
            <div class="form-group">
                <label>{{ __('Latitude') }} *</label>
                <input type="text" class="form-control" id="latitude" name="latitude"
                       value="{{ $property->latitude }}"
                       placeholder="{{ __('Enter Latitude') }}">
            </div>
        </div>

        <!-- Longitude -->
        <div class="col-lg-3">
            <div class="form-group">
                <label>{{ __('Longitude') }} *</label>
                <input type="text" class="form-control" id="longitude" name="longitude"
                       value="{{ $property->longitude }}"
                       placeholder="{{ __('Enter Longitude') }}">
            </div>
        </div>

                                    <!-- Map Container -->
                                    <div class="col-lg-12 mb-3">
                                        <div id="map" style="width: 100%; height: 400px;"></div>
                                    </div>





                                </div>

                                <div id="accordion" class="mt-3 custom-accordion px-2">
                                    @foreach ($languages as $language)
                                        @php
                                            $peopertyContent = $propertyContents
                                                ->where('language_id', $language->id)
                                                ->first();

                                        @endphp
                                        <div class="version">
                                            <div class="version-header" id="heading{{ $language->id }}">
                                                <h5 class="mb-0">
                                                    <button type="button" class="btn btn-link" data-toggle="collapse"
                                                        data-target="#collapse{{ $language->id }}"
                                                        aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                                        aria-controls="collapse{{ $language->id }}">
                                                        {{ $language->name . __(' Language') }}
                                                        {{ $language->is_default == 1 ? '(Default)' : '' }}
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
                                                                ->where('type', $property->type)
                                                                ->where('status', 1)
                                                                ->get();
                                                        @endphp
                                                        <div class="col-lg-3 ">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Category') }} *</label>
                                                                <select name="{{ $language->code }}_category_id"
                                                                    class="form-control category">
                                                                    <option disabled selected>
                                                                        {{ $keywords['Select a Category'] ?? __('Select a Category') }}
                                                                    </option>

                                                                    @foreach ($propertyCategories as $category)
                                                                        <option value="{{ $category->id }}"
                                                                            {{ $peopertyContent->category_id == $category->id ? 'selected' : '' }}>
                                                                            {{ $category->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        @if ($propertySettings->property_country_status == 1)
                                                            <div class="col-lg-3 d-none">
                                                                <div
                                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }} ">

                                                                    <label>{{ __('Country') }} *</label>
                                                                    <select name="{{ $language->code }}_country_id"
                                                                        class="form-control country">
                                                                        <option disabled selected>
                                                                            {{ __('Select Country') }}
                                                                        </option>


                                                                        @foreach ($language->propertyCountries as $country)
                                                                            <option value="{{ $country->id }}"
                                                                                {{ $peopertyContent->country_id == $country->id ? 'selected' : '' }}>
                                                                                {{ $country->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if ($propertySettings->property_state_status == 1)
                                                            <div class="col-lg-3 state d-none"
                                                                @if (is_null($peopertyContent->state_id)) style="display:none !important;" @else @endif>
                                                                <div
                                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">

                                                                    <label>{{ __('State') }} *</label>
                                                                    <select onchange="getCities(event)"
                                                                        name="{{ $language->code }}_state_id"
                                                                        class="form-control  state_id states">
                                                                        <option disabled>{{ __('Select State') }}
                                                                        </option>
                                                                        @if ($peopertyContent->state_id)
                                                                            @foreach ($language->propertyStates as $state)
                                                                                <option value="{{ $state->id }}"
                                                                                    {{ $peopertyContent->state_id == $state->id ? 'selected' : '' }}>
                                                                                    {{ $state->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        @endif


                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="col-lg-3 city "
                                                            @if (empty($peopertyContent->city_id)) style="display:none;"@else style="display:block;" @endif>
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">

                                                                <label>{{ __('City') }} *</label>
                                                                <select name="{{ $language->code }}_city_id"
                                                                    class="form-control city_id">
                                                                    <option value="" disabled>
                                                                        {{ __('Select City') }}
                                                                    </option>
                                                                    @if ($peopertyContent->city_id)
                                                                        @foreach ($language->propertyCities as $city)
                                                                            <option
                                                                                value="{{ $peopertyContent->city_id }}"
                                                                                {{ $peopertyContent->city_id == $city->id ? 'selected' : '' }}>
                                                                                {{ $city->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    @endif


                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-3 mt-4">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label for="">{{ __('Amenities') }}*</label>
                                                                <select name="{{ $language->code }}_amenities[]"
                                                                    class="form-control js-example-basic-multiple"
                                                                    multiple="multiple">
                                                                    <option value="" disabled>
                                                                        {{ __('Please Select Amenities') }}
                                                                    </option>
                                                                    {{-- @foreach ($language->propertyAmenities as $amenity)
                                                                        <option value="{{ $amenity->id }}">
                                                                            {{ $amenity->name }}</option>
                                                                    @endforeach --}}

                                                                    @foreach ($language->propertyAmenities as $amenity)
                                                                        <option value="{{ $amenity->id }}"
                                                                            @foreach ($propertyAmenities as $propertyAmenity)
                                                            {{ $propertyAmenity->amenity_id == $amenity->id ? 'selected' : '' }} @endforeach>
                                                                            {{ $amenity->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Title') }}*</label>
                                                                <input type="text" class="form-control"
                                                                    name="{{ $language->code }}_title"
                                                                    placeholder="{{ __('Enter Title') }}"
                                                                    value="{{ $peopertyContent ? $peopertyContent->title : '' }}">
                                                            </div>
                                                        </div>



                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Address') . '*' }}
                                                                </label>
                                                                <input type="text"
                                                                    name="{{ $language->code }}_address"
                                                                    placeholder="{{ __('Enter Address') }}"
                                                                    value="{{ @$peopertyContent->address }}"
                                                                    class="form-control">
                                                            </div>
                                                        </div>


                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Description') }}
                                                                    *</label>
                                                                <textarea class="form-control summernote " id="{{ $language->code }}_description"
                                                                    placeholder="{{ __('Enter Description') }}" name="{{ $language->code }}_description" data-height="300">{{ @$peopertyContent->description }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row d-none">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Meta Keywords') }}
                                                                    *</label>
                                                                <input class="form-control"
                                                                    name="{{ $language->code }}_meta_keyword"
                                                                    placeholder="{{ __('Enter Meta Keywords') }}"
                                                                    data-role="tagsinput"
                                                                    value="{{ $peopertyContent ? $peopertyContent->meta_keyword : '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row d-none">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Meta Description') }}
                                                                    *</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_meta_description" rows="5"
                                                                    placeholder=" {{ __('Enter Meta Description') }}">{{ $peopertyContent ? $peopertyContent->meta_description : '' }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row ">
                                                        <div class="col">
                                                            @php $currLang = $language; @endphp

                                                            @foreach ($languages as $language)
                                                                @continue($language->id == $currLang->id)

                                                                <div class="form-check py-0">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $language->id }}', event)">
                                                                        <span
                                                                            class="form-check-sign">{{ __('Clone for') }}
                                                                            <strong
                                                                                class="text-capitalize text-secondary">{{ $language->name }}</strong>
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
                                            {{ __('Additional Specifications') . ' (' . __('Optional') . ')' }}
                                        </h4>
                                        <table class="table table-bordered ">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Label') }}</th>
                                                    <th>{{ __('Value') }}</th>
                                                    <th><a href="javascrit:void(0)"
                                                            class="btn  btn-sm btn-success addRow"><i
                                                                class="fas fa-plus-circle"></i></a></th>
                                                </tr>
                                            <tbody id="tbody">

                                                @if (count($specifications) > 0)
                                                    @foreach ($specifications as $specification)
                                                        <tr>
                                                            <td>
                                                                @foreach ($languages as $language)
                                                                    {{-- @php
                                                                        $sp_content = $specification->getContent(
                                                                            $language->id,
                                                                        );
                                                                    @endphp --}}
                                                                    <div
                                                                        class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                        <input type="text"
                                                                            name="{{ $language->code }}_label[]"
                                                                            value="{{ $specification->label }}"
                                                                            class="form-control"
                                                                            plplaceholder="{{ __('Label for') . ' ' . $language->name . ' ' . __('language') }}">
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                            <td>
                                                                @foreach ($languages as $language)
                                                                    {{-- @php
                                                                        $sp_content = $specification->getContent(
                                                                            $language->id,
                                                                        );
                                                                    @endphp --}}
                                                                    <div
                                                                        class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                        <input type="text"
                                                                            name="{{ $language->code }}_value[]"
                                                                            value="{{ $specification->value }}"
                                                                            class="form-control"
                                                                            placeholder="{{ __('Value for') . ' ' . $language->name . ' ' . __('language') }}">
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                            <td>
                                                                <a href="javascript:void(0)"
                                                                    data-specification="{{ $specification->id }}"
                                                                    class="btn  btn-sm btn-danger deleteSpecification">
                                                                    <i class="fas fa-minus"></i></a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td>
                                                            @foreach ($languages as $language)
                                                                <div
                                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                    <input type="text"
                                                                        name="{{ $language->code }}_label[]"
                                                                        class="form-control"
                                                                        placeholder="{{ __('Label for') . ' ' . $language->name . ' ' . __('language') }}">
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
                                                                        placeholder="{{ __('Value for') . ' ' . $language->name . ' ' . __('language') }}">
                                                                </div>
                                                            @endforeach
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-danger  btn-sm deleteRow">
                                                                <i class="fas fa-minus"></i></a>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                                <div id="sliders"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" id="propertySubmit" class="btn btn-primary">
                                {{ __('Update') }}
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
        $label_name = $language->code . '_label[]';
        $value_name = $language->code . '_value[]';
        $labels_placeholder = __('Label for') . ' ' . $language->name . ' ' . __('language');
        $values_placeholder = __('Value for') . ' ' . $language->name . ' ' . __('language');

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


@section('scripts')
    {{-- // var storeUrl = "{{ route('user.property.imagesupdate', ['vendor_id' => $property->vendor_id]) }}";
// var removeUrl = "{{ route('user.property.imagermv') }}"; --}}
    <script>
        var labels = "{!! $labels !!}";
        var values = "{!! $values !!}";
        var stateUrl = "{{ route('user.property_management.get_state_cities', ':countryId') }}";
        var cityUrl = "{{ route('user.property_management.get_cities', ':cityId') }}";
        var rmvdbUrl = "{{ route('user.property.imgdbrmv') }}";
        var specificationRmvUrl = "{{ route('user.property_management.specification_delete') }}";
    </script>


    <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-partial.js') }}"></script>

    <script type="text/javascript" src="{{ asset('assets/tenant/js/property-dropzone.js') }}"></script>

    <script type="text/javascript" src="{{ asset('assets/tenant/js/property.js') }}"></script>


<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCshOz-S6yMXGEPwrhQf2T1XtS8oqZqR-c&callback=initMap"
        async defer></script>

<script>
  function initMap() {
    // Convert the existing lat/lng from your Blade variables to floats.
    // If they are null/empty, use a default location (e.g., 0,0 or some known location).
    const existingLat = parseFloat("{{ $property->latitude ?? 0 }}");
    const existingLng = parseFloat("{{ $property->longitude ?? 0 }}");

    // If your $property->latitude/$property->longitude might be null,
    // you can do something like:
    let lat = isNaN(existingLat) ? 40.7128 : existingLat;  // Default to NYC if not valid
    let lng = isNaN(existingLng) ? -74.0060 : existingLng; // Default to NYC if not valid

    // The initial position
    const initPosition = { lat: lat, lng: lng };

    // Create the map
    const map = new google.maps.Map(document.getElementById("map"), {
      center: initPosition,
      zoom: 10, // adjust zoom level as desired
    });

    // Create a draggable marker at the existing or default lat/lng
    const marker = new google.maps.Marker({
      position: initPosition,
      map: map,
      draggable: true,
    });

    // Update the input fields when the marker is dragged
    google.maps.event.addListener(marker, 'dragend', function(event) {
      document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
      document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
    });

    // If the user clicks somewhere else on the map, move the marker there
    google.maps.event.addListener(map, 'click', function(event) {
      marker.setPosition(event.latLng);
      document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
      document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
    });
  }
</script>

@endsection
