@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')
@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))
@section('pageHeading', $propertyContent->title)
@section('metaKeywords', !empty($propertyContent) ? $propertyContent->meta_keyword : '')
@section('metaDescription', !empty($propertyContent) ? $propertyContent->meta_description : '')

@section('og:tag')
<meta property="og:title" content="{{ $propertyContent->title }}">
<meta property="og:image" content="{{ asset($propertyContent->featured_image) }}">
<meta property="og:url"
    content="{{ route('front.user.property.details', [getParam(), 'slug' => $propertyContent->slug]) }}">
@endsection
@else

@section('tab-title')
{{ $propertyContent->title }}
@endsection

@section('meta-description', !empty($propertyContent) ? $propertyContent->meta_description : '')
@section('meta-keywords', !empty($propertyContent) ? $propertyContent->meta_keyword : '')

@section('page-name')
{{ $propertyContent->title }}
@endsection
@section('br-name')
{{ $keywords['property_details'] ?? 'Property Details' }}
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/vendors/swiper-bundle.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/partials.css') }}">
@if ($userCurrentLang->rtl == 1)
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/rtl.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/responsive.css') }}">

@endsection
@section('scripts')

<script src="{{ asset('/assets/front/user/realestate/js/vendors/swiper-bundle.min.js') }}"></script>
<script>
    'use-strict'
    // Product single slider
    var proSingleThumbs = new Swiper(".slider-thumbnails", {
        loop: true,
        spaceBetween: 20,
        slidesPerView: 3
    });
    var proSingleSlider = new Swiper(".product-single-slider", {
        loop: false,
        spaceBetween: 30,
        // Navigation arrows
        navigation: {
            nextEl: ".slider-btn-next",
            prevEl: ".slider-btn-prev",
        },
        thumbs: {
            swiper: proSingleThumbs,
        },
    });
</script>

@endsection
@endif
@include('user-front.realestate.partials.header.header-pages')

@section('content')
<div style="margin-bottom: 15%;" class="product-single pt-100  border-top header-next">
    <div class="container">
        <div class="row gx-xl-5">
            <div class="col-lg-9 col-xl-8">
                <div class="product-single-gallery mb-40">
                    <!-- Slider navigation buttons -->
                    <div class="slider-navigation">
                        <button type="button" title="Slide prev" class="slider-btn slider-btn-prev">
                            <i class="fal fa-angle-left"></i>
                        </button>
                        <button type="button" title="Slide next" class="slider-btn slider-btn-next">
                            <i class="fal fa-angle-right"></i>
                        </button>
                    </div>
                    <div class="swiper product-single-slider">
                        <div class="swiper-wrapper">
                            @foreach ($sliders as $slider)
                            <div class="swiper-slide">
                                <figure class="radius-lg lazy-container ratio ratio-16-11">
                                    <a href="{{ asset($slider->image) }}"
                                        class="lightbox-single">
                                        <img class="lazyload"
                                            data-src="{{ asset($slider->image) }}"
                                            src="{{ asset($slider->image) }}">
                                    </a>
                                </figure>
                            </div>
                            @endforeach

                        </div>
                    </div>

                    <div class="swiper slider-thumbnails">
                        <div class="swiper-wrapper">
                            @foreach ($sliders as $slider)
                            <div class="swiper-slide">
                                <div class="thumbnail-img lazy-container radius-md ratio ratio-16-11">
                                    <img class="lazyload"
                                        data-src="{{ asset($slider->image) }}"
                                        src="{{ asset($slider->image) }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="product-single-details">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center justify-content-between mb-10">
                                <span class="product-category text-sm"> <a
                                        href="{{ route('front.user.properties', [getParam(), 'category' => $propertyContent->slug]) }}">
                                        {{ $propertyContent->name }}</a></span>
                            </div>
                            <h3 class="product-title">
                                <a href="#">{{ $propertyContent->title }}</a>
                            </h3>

                                @php
                                $district = App\Models\User\UserDistrict::find($propertyContent->state_id);
                                $city = $district ? $district->city : null;
                                @endphp

                                <div class="product-location icon-start ">
                                    <i class="fal fa-map-marker-alt"></i>
                                    <span>
                                        {{ $propertyContent->address }}
                                        @if($city && $district)
                                            / {{ $city->name_ar }} / {{ $district->name_ar }}
                                        @endif
                                    </span>
                                </div>


                            <ul class="product-info p-0 list-unstyled d-flex align-items-center mt-10 mb-30">

                                @if($propertyContent->type ?? null)
                                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top" title="{{ __('Type') }}">
                                    <span >
                                        <i class="fal fa-vector-square"></i>
                                            {{ __($propertyContent->type) }}
                                    </span>
                                </li>
                                @endif
                                @if($propertyContent->purpose ?? null)
                                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top" title="{{ __('Purpose') }}">
                                    <span >
                                        <i class="fa-solid fa-handshake"></i>
                                            {{ __($propertyContent->purpose) }}
                                    </span>
                                </li>
                                @endif

                                @if ($propertyContent->area)
                                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                                    title="{{ __('Area') }}">
                                    <i class="fal fa-vector-square"></i>
                                    <span>
                                        {{ fmod($propertyContent->area, 1) == 0 ? number_format($propertyContent->area, 0) : number_format($propertyContent->area, 2) }}
                                        {{ $keywords['Sqft'] ?? __('Sqft') }}
                                    </span>

                                </li>
                                @endif
                                @if ($propertyContent->beds)
                                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                                    title="{{ $keywords['Beds'] ?? __('Beds') }}">
                                    <i class="fal fa-bed"></i>
                                    <span>{{ $propertyContent->beds }}
                                        {{ $keywords['Beds'] ?? __('Beds') }}</span>
                                </li>
                                @endif
                                @if ($propertyContent->bath)
                                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                                    title="{{ $keywords['Baths'] ?? __('Baths') }}">
                                    <i class="fal fa-bath"></i>
                                    <span>{{ $propertyContent->bath }}
                                        {{ $keywords['Baths'] ?? __('Baths') }}</span>
                                </li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-md-5">

                            <!--  -->
                            @if ($propertyContent->price && $propertyContent->price != 0 && $propertyContent->price != 'null')
                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 4px; direction: rtl;">
                                <span class="new-price" style="font-weight: 600; font-size: 1.0em;">
                                    {{ $keywords['Price'] ?? __('ThePrice') }}:
                                     {{ fmod($propertyContent->price, 1) == 0 ? number_format($propertyContent->price, 0) : number_format($propertyContent->price, 2) }}
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg"
                                    alt="Currency Symbol"
                                    style="width: 12px; height: 15px; vertical-align: middle;">
                                    @if ($propertyContent->payment_method && $propertyContent->payment_method != 'null')
                                        <span class="new-price" style="font-weight: 600; font-size: 1.0em;">
                                            / {{ __($propertyContent->payment_method) }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                            @endif
                            <!--  -->

                            <!-- price of meter -->

                            @if ($propertyContent->meter_price && $propertyContent->meter_price != 0 && $propertyContent->meter_price != 'null')
                            <div class="product-price mb-10">
                            <span class="meter-price">
                                <span class="text-muted">
                                    ({{ $keywords['Meter Price'] ?? __('Meter Price') }}:
                                    {{ fmod($propertyContent->meter_price, 1) == 0 ? number_format($propertyContent->meter_price, 0) : number_format($propertyContent->meter_price, 2) }}

                                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg"
                                    alt="Currency Symbol"
                                    style="width: 12px; height: 15px; vertical-align: middle;">)

                                </span>
                            </span>
                            </div>

                            @endif

                            <!-- payment_method -->
                            @if ($propertyContent->payment_method && $propertyContent->payment_method != 'null')
                            <div class="product-price mb-10">
                                <span class="payment-method">

                                </span>
                            </div>
                            @endif

                            <a class="d-none" {{-- href="{{ route('frontend.agent.details', [getParam(), 'agentusername' => $user->username, 'admin' => 'true']) }}" --}}>

                                <div class="user mb-20">
                                    <div class="user-img">
                                        <div class="lazy-container ratio ratio-1-1 rounded-pill">
                                            <img class="lazyload"
                                                src="{{ $user->photo ? asset('assets/front/img/user/' . $user->photo) : asset('assets/img/blank-user.jpg') }} "
                                                data-src=" {{ $user->photo ? asset('assets/front/img/user/' . $user->photo) : asset('assets/img/blank-user.jpg') }} ">

                                        </div>
                                    </div>
                                    <div class="user-info">
                                        <h5 class="m-0">
                                            {{ $user->first_name . ' ' . $user->last_name }}
                                        </h5>

                                    </div>
                                </div>
                            </a>

                            <ul class="share-link list-unstyled mb-30">
                                <li class="d-none">
                                    <a class="btn blue" style="padding: 9px;" href="#" data-bs-toggle="modal"
                                        data-bs-target="#socialMediaModal">
                                        <i class="far fa-share-alt"></i>
                                    </a>
                                    <span>شارك</span>

                                </li>

                                <li>
                                    <a class="btn green" style="width: 100px !important;padding: 14px;height: 45px !important;" href="https://wa.me/{{ $user->phone }}?text={{ urlencode(__('انا مهتم بهذا العقار: ') . route('front.user.property.details', [getParam(), 'slug' => $propertyContent->slug])) }}">
                                        <i class="fab fa-whatsapp" ></i>
                                    </a>
                                    <span>{{ __('WhatsApp') }}</span>
                                </li>

                                <li>
                                    @if (Auth::guard('customer')->check())
                                    @php
                                    $user_id = Auth::guard('customer')->user()->id;
                                    $checkWishList = checkWishList($propertyContent->propertyId, $user_id);
                                    @endphp
                                    @else
                                    @php
                                    $checkWishList = false;
                                    @endphp
                                    @endif
                                    <a href="{{ route('front.user.property.add-to-wishlist', [getParam(), 'id' => $propertyContent->propertyId]) }}"
                                        class="btn red d-none " data-tooltip="tooltip" data-bs-placement="top"
                                        title="{{ $checkWishList == false ? __('Add to Wishlist') : __('Saved') }}">

                                        @if ($checkWishList == false)
                                        <i class="fal fa-heart"></i>
                                        @else
                                        <i class="fas fa-heart"></i>
                                        @endif
                                    </a>
                                    <span class="d-none">{{ $checkWishList == false ? __('Save') : __('Saved') }}</span>

                                </li>

                            </ul>
                        </div>

                        <!-- The Characteristics Section -->
                        @php
    $characteristics = $propertyContent->property->userPropertyCharacteristics;

    $fields = [
        'facade_id', 'length', 'width',
        'street_width_north', 'street_width_south',
        'street_width_east', 'street_width_west',
        'building_age', 'rooms', 'bathrooms',
        'floors', 'floor_number', 'kitchen',
        'driver_room', 'maid_room', 'dining_room',
        'living_room', 'majlis', 'storage_room',
        'basement', 'swimming_pool', 'balcony',
        'garden', 'annex', 'elevator',
        'private_parking',
    ];

    // Collect and filter values that are NOT null, empty, or zero-ish
    $nonEmpty = collect($fields)
        ->map(fn($key) => data_get($characteristics, $key))
        ->filter(fn($val) =>
            !is_null($val) &&
            trim((string)$val) !== '' &&
            floatval($val) !== 0.0
        );
@endphp

@if($nonEmpty->isNotEmpty())
    <div class="product-characteristics mb-40">
        <h3 class="mb-20">
            {{ $keywords['The Characteristics'] ?? __('The Characteristics') }}
        </h3>
        <div class="row">
            {{-- Facade --}}
            @if(!empty($characteristics->facade_id))
                <div class="col-md-4 mb-3 d-flex align-items-center">
                    <i class="product-info fal fa-layer-group me-2"></i>
                    <strong class="me-1">{{ __('Facade') }}</strong>
                    <span>{{ optional($characteristics->UserFacade)->name }}</span>
                </div>
            @endif

            {{-- Other Characteristics --}}
            @foreach ([
                'length'             => ['label' => __('Length'),              'icon' => 'fal fa-ruler-horizontal'],
                'width'              => ['label' => __('Width'),               'icon' => 'fal fa-ruler-combined'],
                'street_width_north' => ['label' => __('Street Width (North)'), 'icon' => 'fal fa-ruler-vertical'],
                'street_width_south' => ['label' => __('Street Width (South)'), 'icon' => 'fal fa-ruler-vertical'],
                'street_width_east'  => ['label' => __('Street Width (East)'),  'icon' => 'fal fa-ruler-vertical'],
                'street_width_west'  => ['label' => __('Street Width (West)'),  'icon' => 'fal fa-ruler-vertical'],
                'building_age'       => ['label' => __('Building Age'),        'icon' => 'fal fa-calendar-alt'],
                'rooms'              => ['label' => __('Rooms'),               'icon' => 'fal fa-door-open'],
                'bathrooms'          => ['label' => __('Bathrooms'),           'icon' => 'fal fa-toilet'],
                'floors'             => ['label' => __('Floors'),              'icon' => 'fal fa-building'],
                'floor_number'       => ['label' => __('Floor Number'),        'icon' => 'fal fa-sort-numeric-up'],
                'kitchen'            => ['label' => __('Kitchen'),             'icon' => 'fal fa-utensils'],
                'driver_room'        => ['label' => __('Driver Room'),         'icon' => 'fal fa-user-tie'],
                'maid_room'          => ['label' => __('Maid Room'),           'icon' => 'fal fa-broom'],
                'dining_room'        => ['label' => __('Dining Room'),         'icon' => 'fal fa-utensils'],
                'living_room'        => ['label' => __('Living Room'),         'icon' => 'fal fa-couch'],
                'majlis'             => ['label' => __('Majlis'),              'icon' => 'fal fa-users'],
                'storage_room'       => ['label' => __('Storage Room'),        'icon' => 'fal fa-boxes'],
                'basement'           => ['label' => __('Basement'),            'icon' => 'fal fa-warehouse'],
                'swimming_pool'      => ['label' => __('Swimming Pool'),       'icon' => 'fal fa-swimmer'],
                'balcony'            => ['label' => __('Balcony'),             'icon' => 'fal fa-archway'],
                'garden'             => ['label' => __('Garden'),              'icon' => 'fal fa-tree'],
                'annex'              => ['label' => __('Annex'),               'icon' => 'fal fa-house-user'],
                'elevator'           => ['label' => __('Elevator'),            'icon' => 'fal fa-elevator'],
                'private_parking'    => ['label' => __('Private Parking'),     'icon' => 'fal fa-parking'],
            ] as $key => $meta)
                @php $value = data_get($characteristics, $key); @endphp
                @if(!is_null($value) && trim((string)$value) !== '' && floatval($value) !== 0.0)
                    <div class="col-md-4 mb-3 d-flex align-items-center">
                        <i class="product-info {{ $meta['icon'] }} me-2"></i>
                        <span class="me-2">{{ $value }}</span>
                        <strong>{{ $meta['label'] }}</strong>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif



                        <!-- End of Characteristics Section -->

                        <!-- Features Section -->
                        @if (!empty($propertyContent->features))
                            @php
                                $features = json_decode($propertyContent->features, true);
                                // Define feature-to-icon mapping
                                $featureIcons = [
                                    'Garden' => 'fal fa-leaf',
                                    'Pool' => 'fal fa-swimming-pool',
                                    'Garage' => 'fal fa-car',
                                    'Fireplace' => 'fal fa-fireplace',
                                    'Air Conditioning' => 'fal fa-air-conditioner',
                                    'Balcony' => 'fal fa-balcony',
                                    'Security System' => 'fal fa-shield-alt',
                                    'Gym' => 'fal fa-dumbbell',
                                    'Parking' => 'fal fa-parking',
                                ];
                            @endphp
                            @if (!empty($features) && is_array($features))
                                <div class="product-featured mb-40">
                                    <h3 class="mb-20">{{ $keywords['Features'] ?? __('Features') }}</h3>
                                    <ul class="featured-list list-unstyled p-0 m-0">
                                        @foreach ($features as $feature)
                                            <li class="d-inline-block icon-start">
                                                <!-- Use the icon from the mapping, fallback to a default icon if not found -->
                                                <i class="{{ isset($featureIcons[$feature]) ? $featureIcons[$feature] : 'fal fa-star' }}"></i>
                                                <span>{{ __($feature) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif

                    </div>
                    <div class="mb-20"></div>
                    @if (!empty($propertyContent->description))
                    <div class="product-desc mb-40">
                        <h3 class="mb-20">{{ $keywords['The Description'] ?? __('The Description') }}</h3>
                        <div style="white-space: pre-wrap ;">{!! $propertyContent->description !!}</div>
                        <div class="mb-20"></div>
                    </div>
                    @endif
                    {{-- @if (!empty(showAd(3)))
                            <div class="text-center mb-3 mt-3">
                                {!! showAd(3) !!}
                            </div>
                        @endif --}}

                    @if (count($propertyContent->propertySpacifications) > 0)
                    <div class="row" class="mb-20">
                        <div class="col-12">
                            <h3 class="mb-20">{{ $keywords['Features'] ?? __('Features') }}</h3>
                        </div>

                        @foreach ($propertyContent->propertySpacifications as $specification)
                        <div class="col-lg-3 col-sm-6 col-md-4 mb-20">
                            <strong
                                class="mb-1 @if ($userBs->theme != 'home_five') text-dark @endif d-block">{{ $specification?->label }}</strong>
                            <span>{{ $specification?->value }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="pb-20"></div>
                    @endif
                    @if (!empty($amenities) && count($amenities) > 0)

                    <div class="product-featured mb-40">
                        <h3 class="mb-20">{{ $keywords['Amenities'] ?? __('Amenities') }}</h3>
                        <ul class="featured-list list-unstyled p-0 m-0">
                            @foreach ($amenities as $amenity)
                            <li class="d-inline-block icon-start">

                                <i class="{{ $amenity->amenity?->icon }}"></i>
                                <span>{{ $amenity->amenity?->name }}</span>
                            </li>
                            @endforeach

                        </ul>
                    </div>
                    @endif

                    @if (!empty($propertyContent->video_url))
                    <div class="product-video mb-40">
                        <h3 class="mb-20"> {{ $keywords['Video'] ?? __('Video') }}</h3>
                        <div class="lazy-container radius-lg ratio ratio-16-11">
                            <img class="lazyload"
                                data-src="{{ $propertyContent->video_image ? asset('assets/img/property/video/' . $propertyContent->video_image) : asset('assets/front/images/placeholder.png') }}"
                                src="{{ $propertyContent->video_image ? asset('assets/img/property/video/' . $propertyContent->video_image) : asset('assets/front/images/placeholder.png') }}">
                            <a href="{{ $propertyContent->video_url }}"
                                class="video-btn youtube-popup p-absolute">
                                <i class="fas fa-play"></i>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if (!empty($propertyContent->floor_planning_image))
                    @php
                    $floorPlanningImages = json_decode($propertyContent->floor_planning_image, true);
                    @endphp

                    @if (!empty($floorPlanningImages) && is_array($floorPlanningImages))
                    <div class="product-planning mb-40">
                        <h3 class="mb-20">{{ $keywords['Floor Planning'] ?? __('Floor Planning') }}</h3>

                        @foreach ($floorPlanningImages as $image)
                        <div class="lazy-container radius-lg ratio ratio-16-11 border mb-3">
                            <img class="lazyload"
                                src="{{ asset($image) }}"
                                data-src="{{ asset($image) }}">
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @endif

                    @if (!empty($propertyContent->latitude) && !empty($propertyContent->longitude))
                    <div class="product-location mb-40">
                        <h3 class="mb-20">{{ $keywords['Location'] ?? __('Location') }}</h3>
                        <div class="lazy-container radius-lg ratio ratio-21-9 border">
                            <iframe class="lazyload"
                                src="https://maps.google.com/maps?q={{ $propertyContent->latitude }},{{ $propertyContent->longitude }}&hl={{ $userCurrentLang->code }}&z=14&amp;output=embed"></iframe>
                        </div>
                    </div>
                    @endif

                    <div class="product-video mb-40">
                        <!-- faqs -->
                        @if($propertyContent->displayFaqs())
                            <h3 class="mb-3">{{ $keywords['FAQs'] ?? __('FAQs') }}</h3>

                            <div class="accordion" id="faqAccordion">
                                @foreach($propertyContent->displayFaqs() as $i => $faq)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $i }}">
                                            <button class="accordion-button collapsed"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#collapse{{ $i }}"
                                                    aria-expanded="false">
                                                {{ $faq['question'] }}
                                            </button>
                                        </h2>

                                        <div id="collapse{{ $i }}"
                                            class="accordion-collapse collapse"
                                            data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                {{ $faq['answer'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                        @endif
                    </div>

                    {{-- @if (!empty(showAd(3)))
                            <div class="text-center mb-3 mt-3">
                                {!! showAd(3) !!}
                            </div>
                        @endif --}}
                </div>
            </div>
            <div class="col-lg-3 col-xl-4">
                <aside class="sidebar-widget-area mb-10" data-aos="fade-up">
                    <div class="widget widget-form radius-md mb-30 d-none">
                        <div class="user mb-20">
                            <div class="user-img">
                                <div class="lazy-container ratio ratio-1-1 rounded-pill d-none">

                                    <a {{-- href="{{ route('frontend.agent.details', [getParam(), 'agentusername' => $user->username, 'admin' => 'true']) }} --}}>

                                        <img class="lazyload"
                                            src="{{ $user->photo ? asset('assets/front/img/user/' . $user->photo) : asset('assets/img/blank-user.jpg') }} "
                                            data-src=" {{ $user->photo ? asset('assets/front/img/user/' . $user->photo) : asset('assets/img/blank-user.jpg') }}">
                                    </a>


                                </div>
                            </div>
                            <div class="user-info d-none">
                                <h4 class="mb-0">
                                    <a {{-- href="{{ route('frontend.agent.details', [getParam(), 'agentusername' => $user->username, 'admin' => 'true']) }}" --}}> {{ $user->first_name . ' ' . $user->last_name }}
                                    </a>
                                </h4>
                                <a class="d-block" href="tel:{{ $user->phone }}">
                                    {{ $user->phone }}
                                </a>
                                <a href="mailto:{{ $user->email }}">
                                    {{ $user->email }}
                                </a>
                            </div>
                        </div>

                        <form action="{{ route('front.user.property_contact', getParam()) }}" method="POST">
                            @csrf

                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <input type="hidden" name="property_id" value="{{ $propertyContent->propertyId }}">

                            <div class="form-group mb-20">
                                <input type="text" class="form-control" name="name"
                                    placeholder="{{ $keywords['Name'] ?? __('Name') }}*" required
                                    value="{{ old('name') }}">
                                @error('name')
                                <p class=" text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group mb-20">
                                <input type="email" class="form-control" required name="email"
                                    placeholder="{{ $keywords['Email Address'] ?? __('Email Address') }}*"
                                    value="{{ old('email') }}">
                                @error('email')
                                <p class=" text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group mb-20">
                                <input type="number" class="form-control" name="phone" required
                                    value="{{ old('phone') }}"
                                    placeholder="{{ $keywords['Phone Number'] ?? __('Phone Number') }}*">
                                @error('phone')
                                <p class=" text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group mb-20">
                                <textarea name="message" id="message" class="form-control" cols="30" rows="8" required=""
                                    data-error="Please enter your message" placeholder="{{ $keywords['Message'] ?? __('Message') }}...">{{ old('message') }}</textarea>

                                @error('message')
                                <p class=" text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                                {{-- @if ($info->google_recaptcha_status == 1)
                                        <div class="form-group mb-30">
                                            {!! NoCaptcha::renderJs() !!}
                                            {!! NoCaptcha::display() !!}

                                            @error('g-recaptcha-response')
                                                <p class="mt-1 text-danger">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif --}}
                            <button type="submit" class="btn btn-md btn-primary w-100">{{ $keywords['Send message'] ?? __('Send message') }}</button>
                        </form>
                    </div>
                {{-- <x-tenant.frontend.agentContact :agent="$agent" :agentContact='false' :user="$user" :propertyContent="$propertyContent" /> --}}

                            <div class="widget widget-recent radius-md mb-30 ">
                                <h3 class="title">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#products" aria-expanded="true" aria-controls="products">
                                        {{ $keywords['Related'] ?? __('Related') }}
                                    </button>
                                </h3>
                                <div id="products" class="collapse show">
                                    <div class="accordion-body p-0">
                                        @foreach ($relatedProperty as $property)
                                        <div class="product-default product-inline mt-20">
                                            <figure class="product-img">
                                                <a href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug]) }}"
                                                    class="lazy-container ratio ratio-1-1 radius-md">
                                                    <img class="lazyload"
                                                        data-src="{{ asset($property->featured_image) }}"
                                                        src="{{ asset($property->featured_image) }}">
                                                </a>
                                            </figure>
                                            <div class="product-details">
                                                <h6 class="product-title"><a
                                                        href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug]) }}">{{ $property->title }}</a>
                                                </h6>
                                                <span class="product-location icon-start">
                                                    <i class="fal fa-map-marker-alt"></i>
                                                    {{ $property->city_name }}
                                                    {{ $userBs->property_state_status == 1 && $property->state_name != null ? ', ' . $property->state_name : '' }}
                                                    {{ $userBs->property_country_status == 1 && $property->country_name != null ? ', ' . $property->country_name : '' }}
                                                </span>

                                                @if (!empty($property->price))

                                                <div class="product-price">

                                                    <span class="new-price">{{ ($keywords['Price'] ?? __('Price')) . ':' }}
                                                        {{ fmod($propertyContent->price, 1) == 0 ? number_format($propertyContent->price, 0) : number_format($propertyContent->price, 2) }}
                                                    </span>
                                                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg"
                                                        alt="Currency Symbol"
                                                        style="width: 12px; height: 15px; vertical-align: middle;">
                                                        @if ($propertyContent->payment_method && $propertyContent->payment_method != 'null')
                                                            <span class="new-price" style="font-weight: 600; font-size: 1.0em;">
                                                                / {{ __($propertyContent->payment_method) }}
                                                            </span>
                                                        @endif
                                                </div>

                                                @endif

                                                <ul class="product-info p-0 list-unstyled d-flex align-items-center">

                                                {{-- Area --}}
                                                @if($property->area > 0)
                                                    <li class="icon-start" data-tooltip="tooltip"
                                                        data-bs-placement="top"
                                                        title="{{ $keywords['Area'] ?? __('Area') }}">
                                                        <i class="fal fa-vector-square"></i>
                                                        <span>
                                                            {{ fmod($property->area, 1) == 0
                                                                    ? number_format($property->area, 0)
                                                                    : number_format($property->area, 2) }}
                                                        </span>
                                                    </li>
                                                @endif

                                                {{-- Only for residential units --}}
                                                @if($property->type === 'residential')

                                                    @if($property->beds > 0)
                                                        <li class="icon-start" data-tooltip="tooltip"
                                                            data-bs-placement="top"
                                                            title="{{ $keywords['Bed'] ?? __('Bed') }}">
                                                            <i class="fal fa-bed"></i>
                                                            <span>{{ $property->beds }}</span>
                                                        </li>
                                                    @endif

                                                    @if($property->bath > 0)
                                                        <li class="icon-start" data-tooltip="tooltip"
                                                            data-bs-placement="top"
                                                            title="{{ $keywords['Bath'] ?? __('Bath') }}">
                                                            <i class="fal fa-bath"></i>
                                                            <span>{{ $property->bath }}</span>
                                                        </li>
                                                    @endif

                                                @endif
                                                </ul>

                                            </div>
                                        </div><!-- product-default -->
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        {{-- @if (!empty(showAd(2)))
                            <div class="text-center mb-3 mt-3">
                                {!! showAd(2) !!}
                            </div>
                        @endif --}}
                </aside>
            </div>
        </div>
    </div>
</div>

{{-- share on social media modal --}}
<div class="modal fade" id="socialMediaModal" tabindex="-1" role="dialog" aria-labelledby="socialMediaModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle"> {{ $keywords['Share On'] ?? __('Share On') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="actions d-flex justify-content-around">
                    <div class="action-btn">
                        <a class="facebook btn"
                            href="https://www.facebook.com/sharer/sharer.php?u={{ url()->current() }}&src=sdkpreparse"><i
                                class="fab fa-facebook-f"></i></a>
                        <br>
                        <span> {{ $keywords['Facebook'] ?? __('Facebook') }} </span>
                    </div>
                    <div class="action-btn">
                        <a href="http://www.linkedin.com/shareArticle?mini=true&amp;url={{ urlencode(url()->current()) }}"
                            class="linkedin btn"><i class="fab fa-linkedin-in"></i></a>
                        <br>
                        <span> {{ $keywords['Linkedin'] ?? __('Linkedin') }} </span>
                    </div>
                    <div class="action-btn">
                        <a class="twitter btn"
                            href="https://twitter.com/intent/tweet?text={{ url()->current() }}"><i
                                class="fab fa-twitter"></i></a>
                        <br>
                        <span> {{ $keywords['Twitter'] ?? __('Twitter') }} </span>
                    </div>
                    <div class="action-btn">
                        <a class="whatsapp btn" href="whatsapp://send?text={{ url()->current() }}"><i
                                class="fab fa-whatsapp"></i></a>
                        <br>
                        <span> {{ $keywords['Whatsapp'] ?? __('Whatsapp') }} </span>
                    </div>
                    <div class="action-btn">
                        <a class="sms btn" href="sms:?body={{ url()->current() }}" class="sms"><i
                                class="fas fa-sms"></i></a>
                        <br>
                        <span> {{ $keywords['SMS'] ?? __('SMS') }} </span>
                    </div>
                    <div class="action-btn">
                        <a class="mail btn"
                            href="mailto:?subject=Digital Card&body=Check out this digital card {{ url()->current() }}."><i
                                class="fas fa-at"></i></a>
                        <br>
                        <span> {{ $keywords['Mail'] ?? __('Mail') }} </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
