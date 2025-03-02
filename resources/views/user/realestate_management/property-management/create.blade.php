@extends('user.layout')
<style>
    #map {
        width: 100%;
        height: 250px;
        max-width: 600px;
    }
</style>
@section('content')

<div class="page-header">
    <h4 class="page-title">{{ __('Add Property') }}</h4>
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
            <a href="#">{{ __('Add Property') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title d-inline-block">{{ __('Add Property') }}</div>
            </div>

            <div class="card-body">
                <div class="row" style="text-align: center;">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="alert alert-danger pb-1 " style="display: none;" id="propertyErrors">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <ul></ul>
                        </div>
                        {{-- <div class="col-lg-12">
                                <label for="" class="mb-2"><strong>{{ __('Gallery Images') }}
                        *</strong></label>
                        <form action="{{ route('user.property.imagesstore') }}" id="myDropzoneI" enctype="multipart/form-data" class="dropzone create">
                            @csrf
                            <div class="fallback">
                                <input name="file" type="file" multiple />
                            </div>
                        </form>
                        <p class="em text-danger mb-0" id="errslider_images"></p>
                    </div> --}}
                    <form id="propertyForm" action="{{ route('user.property_management.store_property') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="row mb-3 mt-3" >
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Thumbnail Image')}}</label>
                                    <br>
                                    <div class="showImage">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control " id="image" name="featured_image">
                                    </div>
                                    <p id="errfeatured_image" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Floor Planning Image')}}</label>
                                    <br>
                                    <div class="showImage2">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control " id="image2" name="floor_planning_image">
                                    </div>
                                    <p id="errimage" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label for="">{{ __('Video Image') }}</label>
                                    <br>
                                    <div class="showImage3">
                                        <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                                    </div>

                                    <div class="mt-3">
                                        <input type="file" class="form-control" id="image3" name="video_image">
                                    </div>
                                    <p id="errvideo_image" class=" mb-0 text-danger em"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row " style="margin-top: 100px;">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Video Url') }} </label>
                                    <input type="text" class="form-control" name="video_url" placeholder="{{ __('Enter video url') }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Purpose') }}*</label>

                                    <select name="purpose" class="form-control">
                                        <option selected disabled value="">
                                            {{ __('Select Purpose') }}
                                        </option>
                                        <option value="0" selected></option>
                                        <option value="rent">{{ __('Rent') }}</option>
                                        <option value="sale">{{ __('Sale') }}</option>
                                    </select>
                                </div>

                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Price') . ' (' . $userBs->base_currency_text . ')' }}
                                    </label>
                                    <input type="number" class="form-control" name="price" placeholder="{{ __('Enter Current Price') }}">

                                    <p class="text-warning">
                                        {{ __('If you leave it blank, price will be negotiable.') }}
                                    </p>
                                </div>
                            </div>

                            @if (request('type') == 'residential')
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Beds') }} <i class="fal fa-bed"></i></label>
                                    <input type="text" class="form-control" name="beds" placeholder="{{ __('Enter number of bed') }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Baths') }} <i class="fal fa-bath"></i></label>
                                    <input type="text" class="form-control" name="bath" placeholder="{{ __('Enter number of bath') }}">
                                </div>
                            </div>
                            @endif
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>{{ __('Area (sqft)') }} <i class="fal fa-vector-square"></i></label>
                                    <input type="text" class="form-control" name="area" placeholder="{{ __('Enter area (sqft)') }} ">
                                </div>
                            </div>

                            <div class="col-lg-3 d-none">
                                <div class="form-group">
                                    <label>{{ __('Status') }} </label>
                                    <select name="status" id="" class="form-control">
                                        <option value="1" selected>{{ __('Active') }}</option>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12 mb-3">

                            </div>

                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label>Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Longitude" readonly>
                                </div>
                            </div>
                            <!-- Map Container -->
                            <div class="col-lg-12 mb-3">
                                <div id="map"></div>
                            </div>

                        </div>

                        <!--  -->
                        <div id="accordion" class="mt-3 custom-accordion px-2">
                            @foreach ($languages as $language)
                            <div class="version">
                                <div class="version-header " id="heading{{ $language->id }}">
                                    <h5 class="mb-0">
                                        <button type="button" class="btn accordion-btn" data-toggle="collapse" data-target="#collapse{{ $language->id }}" aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}" aria-controls="collapse{{ $language->id }}">
                                            {{ $language->name . __(' Language') }}
                                            {{ $language->is_default == 1 ? '(Default)' : '' }}

                                            <span class="caret"></span>
                                        </button>
                                    </h5>
                                </div>

                                <div id="collapse{{ $language->id }}" class="collapse {{ $language->is_default == 1 ? 'show' : '' }}" aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
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
                                                <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Category') }} *</label>
                                                    <select name="{{ $language->code }}_category_id" class="form-control category">
                                                        <option disabled selected>
                                                            {{ __('Select Category') }}
                                                        </option>

                                                        @foreach ($propertyCategories as $key => $category)
                                                        <option value="{{ $category->id }}" {{ $key === 0 ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            @if ($propertySettings->property_country_status == 1)
                                                <div class="col-lg-4 country">
                                                    <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                        <label>{{ __('Country') }} *</label>
                                                        <select name="{{ $language->code }}_country_id" class="form-control country js-example-basic-single">
                                                            <option disabled>{{ __('Select Country') }}</option>
                                                            @foreach ($language->propertyCountries as $key => $country)
                                                                <option value="{{ $country->id }}" {{ $key === 0 ? 'selected' : '' }}>
                                                                    {{ $country->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif

                                                @if ($regions != null)
                                                    <div class="col-lg-4 state">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Region') }}</label>
                                                            <select name="region_id" id="region_id" class="form-control js-example-basic-single3" onchange="loadGovernorates()">
                                                                <option selected disabled>{{ __('Select Region') }}</option>
                                                                @foreach ($regions as $region)
                                                                    <option value="{{ $region->id }}">{{ $region->name_en }} / {{ $region->name_ar }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif

                                                    <div class="col-lg-4 city">
                                                        <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Governorate') }} *</label>
                                                            <select name="governorate_id" id="governorate_id" class="form-control js-example-basic-single3">
                                                                <option selected disabled>{{ __('Select Governorate') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                <div class="col-lg-6">
                                                    <div class="form-group  {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                        <label for="">{{ __('Amenity') }}</label> <br>
                                                        <select name="{{ $language->code }}_amenities[]" class="form-control js-example-basic-multiple" multiple>
                                                            <option value="" se></option>
                                                            @foreach ($language->propertyAmenities as $amenity)
                                                            <option value="{{ $amenity->id }}">
                                                                {{ $amenity->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>

                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="row">
                                                        {{-- Property Title Field --}}
                                                        <div class="col-lg-12">
                                                            <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                                <label>
                                                                    {{ $keywords['Property Title'] ?? __('Property Title') . '*' }}
                                                                </label>
                                                                <input type="text" class="form-control" name="{{ $language->code }}_title" placeholder="{{ $keywords['Enter a clear, concise property title'] ?? __('Enter a clear, concise property title') }}">
                                                                <small class="form-text text-muted">
                                                                    {{ $keywords['property_title_hint'] ?? __('Example: Spacious 2 Bedroom Apartment or Modern Office Space') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                                <div class="row">
                                                    {{-- Property Address Field --}}
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>
                                                                {{ $keywords['Full Property Address'] ?? __('Full Property Address') . '*' }}
                                                            </label>
                                                            <input type="text" name="{{ $language->code }}_address" class="form-control" placeholder="{{ $keywords['Enter the complete address'] ?? __('Enter the complete address') }}">
                                                            <small class="form-text text-muted">
                                                                {{ $keywords['address_hint'] ?? __('Include street name city state province and ZIP postal code') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Description') . '*' }}</label>
                                                            <textarea id="{{ $language->code }}_PostContent" class="form-control summernote" name="{{ $language->code }}_description" placeholder="{{ __('Enter Content') }}" data-height="300"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row d-none">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Meta keyword') }}</label>
                                                            <input class="form-control" name="{{ $language->code }}_keyword" placeholder="{{ __('Enter Meta Keywords') }}" data-role="tagsinput">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row d-none">
                                                    <div class="col-lg-12">
                                                        <div class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                            <label>{{ __('Meta Descroption') }}</label>
                                                            <textarea class="form-control" name="{{ $language->code }}_meta_keyword" rows="5" placeholder="{{ __('Enter Meta Descroption') }}"></textarea>
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
                                                                <input class="form-check-input" type="checkbox" onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $lang->id }}', event)">
                                                                <span class="form-check-sign">{{ __('Clone for') }}
                                                                    <strong class="text-capitalize text-secondary">{{ $lang->name }}</strong>
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
                        <!--  -->
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
                                            <th><a href="" class="btn btn-sm btn-success addRow"><i class="fas fa-plus-circle"></i></a></th>
                                        </tr>
                                    <tbody id="tbody">
                                        <tr>


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

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCshOz-S6yMXGEPwrhQf2T1XtS8oqZqR-c&callback=initMap" async defer></script>

<script>
    function initMap() {
        // Default map center. Adjust to your desired default location.
        const defaultLocation = {
            lat: 40.7128,
            lng: -74.0060
        }; // New York

        // Create the map
        const map = new google.maps.Map(document.getElementById("map"), {
            center: defaultLocation,
            zoom: 8,
        });

        // Create a marker
        const marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true, // allow dragging
        });

        // Update lat/long on marker drag
        google.maps.event.addListener(marker, 'dragend', function(event) {
            document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
            document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
        });

        // Update marker & lat/long on map click
        google.maps.event.addListener(map, 'click', function(event) {
            marker.setPosition(event.latLng);
            document.getElementById('latitude').value = event.latLng.lat().toFixed(6);
            document.getElementById('longitude').value = event.latLng.lng().toFixed(6);
        });
    }
</script>

<script>
    function loadGovernorates() {
        let regionId = document.getElementById("region_id").value;

        if (regionId) {
            fetch(`/user/realestate/property/get-governorates/${regionId}`)
                .then(response => response.json())
                .then(data => {
                    let governorateDropdown = document.getElementById("governorate_id");
                    governorateDropdown.innerHTML = '<option selected disabled>Select Governorate</option>';

                    data.forEach(gov => {
                        governorateDropdown.innerHTML += `<option value="${gov.id}">${gov.name_en} / ${gov.name_ar}</option>`;
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    }
</script>

@endsection
