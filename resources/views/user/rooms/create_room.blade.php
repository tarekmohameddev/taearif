@extends('user.layout')
{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('user.partials.rtl-style')
@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Add Room') }}</h4>
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
                <a href="#">{{ __('Hotel Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Rooms') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Add Room') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Add  Room') }}</div>
                    <a class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('user.rooms_management.rooms') }}">
                        <span class="btn-label">
                            <i class="fas fa-backward" style="font-size: 12px;"></i>
                        </span>
                        {{ __('Back') }}
                    </a>
                </div>

                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="alert alert-danger pb-1" id="roomErrors" style="display: none;">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul></ul>
                            </div>



                            <div style="margin-left: 10px;">
                                <label for=""><strong>{{ __('Slider Images') . '*' }}</strong></label>
                                <form id="slider-dropzone" enctype="multipart/form-data" class="dropzone mt-2 mb-0">
                                    @csrf
                                    <div class="fallback"></div>
                                </form>
                                <p class="text-warning mt-3 mb-0">
                                    {{ '*' . __('Upload 770X600 pixel size image for best quality.') }}</p>
                                <p class="em text-danger mt-3 mb-0" id="err_slider_image"></p>
                            </div>


                            <form id="roomForm" action="{{ route('user.rooms_management.store_room') }}" method="POST">
                                @csrf

                                <div id="slider-image-id"></div>


                                {{-- featured image start --}}
                                <div class="form-group">
                                    <div class="col-12 mb-2">
                                        <label for="image"><strong>{{ __('Featured Image') }}
                                                **</strong></label>
                                    </div>
                                    <div class="col-md-12 showImage mb-3">
                                        <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..."
                                            class="img-thumbnail ">
                                    </div>
                                    <input type="file" name="featured_img" id="image" class="form-control ">
                                    <p id="errfeatured_img" class="mb-0 text-danger em"></p>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Room Status') . '*' }}</label>
                                            <select name="status" class="form-control">
                                                <option selected disabled>{{ __('Select a Status') }}</option>
                                                <option value="1">{{ __('Show') }}</option>
                                                <option value="0">{{ __('Hide') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Rent / Night') }} (in
                                                {{ $currencyInfo->base_currency_text . ' *' }} )
                                            </label>
                                            <input type="number" step="0.01" class="form-control" name="rent"
                                                placeholder="{{ __('Enter Room Rent') }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Quantity') . '*' }}</label>
                                            <input type="number" class="form-control" name="quantity"
                                                placeholder="{{ __('Enter no of rooms') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Beds') . '*' }}</label>
                                            <input type="number" class="form-control" name="bed"
                                                placeholder="{{ __('Enter no of beds') }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Baths') . '*' }}</label>
                                            <input type="number" class="form-control" name="bath"
                                                placeholder="{{ __('Enter on of bath') }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Max Guests') }}</label>
                                            <input type="number" class="form-control" name="max_guests"
                                                placeholder="{{ __('Enter maximum guests') }}">
                                            <p class="text-warning mb-0">
                                                {{ 'Leave blank if you want to make it unlimited.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Latitude') }}</label>
                                            <input type="text" class="form-control" name="latitude"
                                                placeholder="Enter latitude for map">
                                            <p class="text-warning mb-0">{{ 'Will be used to show in google map.' }}</p>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Longitude') }}</label>
                                            <input type="text" class="form-control" name="longitude"
                                                placeholder="Enter longitude for map">
                                            <p class="text-warning mb-0">{{ 'Will be used to show in google map.' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Address') }}</label>
                                            <input type="text" class="form-control" name="address"
                                                placeholder="Enter Address">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Phone') }}</label>
                                            <input type="text" class="form-control" name="phone"
                                                placeholder="Enter Phone">
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Email') }}</label>
                                            <input type="email" class="form-control" name="email"
                                                placeholder="Enter Email">
                                        </div>
                                    </div>
                                </div>

                                <div id="accordion" class="custom-accordion mt-5">
                                    @foreach ($languages as $language)
                                        <div class="version">
                                            <div class="version-header" id="heading{{ $language->id }}">
                                                <h5 class="mb-0">
                                                    <button type="button" class="btn accordion-btn"
                                                        data-toggle="collapse" data-target="#collapse{{ $language->id }}"
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
                                                        <div
                                                            class="
                            @if ($roomSetting->room_category_status == 0) col-lg-12
                            @else
                            col-lg-6 @endif">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Room Title') . '*' }}</label>
                                                                <input type="text" class="form-control"
                                                                    name="{{ $language->code }}_title"
                                                                    placeholder="Enter Title">
                                                            </div>
                                                        </div>

                                                        @if ($roomSetting->room_category_status == 1)
                                                            <div class="col-lg-6">
                                                                <div
                                                                    class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                    @php
                                                                        $categories = App\Models\User\HotelBooking\RoomCategory::where([['language_id', $language->id], ['user_id', Auth::guard('web')->user()->id]])
                                                                            ->where('status', 1)
                                                                            ->get();
                                                                    @endphp

                                                                    <label>{{ __('Category') . '*' }}</label>
                                                                    <select name="{{ $language->code }}_category"
                                                                        class="form-control">
                                                                        <option selected disabled>
                                                                            {{ __('Select a Category') }}
                                                                        </option>

                                                                        @foreach ($categories as $category)
                                                                            <option value="{{ $category->id }}">
                                                                                {{ $category->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                @php
                                                                    $amenities = App\Models\User\HotelBooking\RoomAmenity::where('language_id', $language->id)
                                                                        ->orderBy('serial_number', 'asc')
                                                                        ->get();
                                                                @endphp

                                                                <label>{{ __('Room Amenities') . '*' }}</label>
                                                                <div>
                                                                    @foreach ($amenities as $amenity)
                                                                        <div class="d-inline mr-3">
                                                                            <input type="checkbox" class="mr-1"
                                                                                name="{{ $language->code }}_amenities[]"
                                                                                value="{{ $amenity->id }}"
                                                                                id="amenity{{ $amenity->id }}">
                                                                            <label
                                                                                for="amenity{{ $amenity->id }}">{{ $amenity->name }}</label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Summary') . '*' }}</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_summary" placeholder="{{ __('Enter Summary') }}"
                                                                    rows="3"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Room Description') . '*' }}</label>
                                                                <textarea id="{{ $language->code }}DescriptionSummernote" class="form-control summernote"
                                                                    name="{{ $language->code }}_description" placeholder="{{ __('Enter room descriptions') }}" data-height="300"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __('Meta Keywords') }}</label>
                                                                <input class="form-control"
                                                                    name="{{ $language->code }}_meta_keywords"
                                                                    placeholder="{{ __('Enter Meta Keywords') }}"
                                                                    data-role="tagsinput">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div
                                                                class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">
                                                                <label>{{ __(' Meta Description') }}</label>
                                                                <textarea class="form-control" name="{{ $language->code }}_meta_description" rows="5"
                                                                    placeholder="{{ __('Enter Meta Description') }}"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            @php
                                                                $currLang = $language;
                                                            @endphp
                                                            @foreach ($languages as $language)
                                                                @continue($currLang->id == $language->id)

                                                                <div class="form-check py-0">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            value=""
                                                                            onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $language->id }}', event)">
                                                                        <span
                                                                            class="form-check-sign">{{ __('Clone for') }}
                                                                            <strong
                                                                                class="text-capitalize text-secondary">{{ $language->name }}</strong>
                                                                            {{ __('Language') }}</span>
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
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="roomForm" class="btn btn-success">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const imgUpUrl = "{{ route('user.rooms_management.upload_slider_image') }}";
        const imgRmvUrl = "{{ route('user.rooms_management.remove_slider_image') }}";
        const baseUrl = "{{ url('') }}";
    </script>
    <script src="{{ asset('assets/admin/js/slider-image.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin-room.js') }}"></script>
@endsection
