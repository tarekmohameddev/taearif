@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')
@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))
    {{-- @extends('user-front.realestate.layout') --}}

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
    {{-- @extends('user-front.layout') --}}

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

@section('content')
    <div class="product-single pt-100 pb-70 border-top header-next">
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
                            <div class="col-md-8">
                                <div class="d-flex align-items-center justify-content-between mb-10">
                                    <span class="product-category text-sm"> <a
                                            href="{{ route('front.user.properties', [getParam(), 'category' => $propertyContent->slug]) }}">
                                            {{ $propertyContent->name }}</a></span>
                                </div>
                                <h3 class="product-title">
                                    <a href="#">{{ $propertyContent->title }}</a>
                                </h3>
                                <div class="product-location icon-start">
                                    <i class="fal fa-map-marker-alt"></i>
                                    <span>
                                        {{ $propertyContent->address }}
                                    </span>
                                    <span>

                                        {{ $propertyContent->city->name ?? "" }}

                                        {{ $userBs->property_state_status == 1 && !is_null($propertyContent->state) ? ', ' . $propertyContent->state->name : '' }}
                                        {{ $userBs->property_country_status == 1 && !is_null($propertyContent->country) ? ', ' . $propertyContent->country->name : '' }}
                                    </span>
                                </div>
                                <ul class="product-info p-0 list-unstyled d-flex align-items-center mt-10 mb-30">

                                @if ($propertyContent->area)
                                    <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                                        title="{{ __('Area') }}">
                                        <i class="fal fa-vector-square"></i>
                                        <span>{{ $propertyContent->area }} {{ $keywords['Sqft'] ?? __('Sqft') }}</span>
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
                            <div class="col-md-4">

                            @if ($propertyContent->price)
                                <div class="product-price mb-10">
                                    <span class="new-price">{{ ($keywords['Price'] ?? __('Price')) . ':' }}
                                        {{ $propertyContent->price ? $propertyContent->price : $keywords['Negotiable'] ?? __('Negotiable') }}</span>
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
                                    <li>
                                        <a class="btn blue" href="#" data-bs-toggle="modal"
                                            data-bs-target="#socialMediaModal">
                                            <i class="far fa-share-alt"></i>
                                        </a>
                                        <span>شارك</span>

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
                                            class="btn red " data-tooltip="tooltip" data-bs-placement="top"
                                            title="{{ $checkWishList == false ? __('Add to Wishlist') : __('Saved') }}">

                                            @if ($checkWishList == false)
                                                <i class="fal fa-heart"></i>
                                            @else
                                                <i class="fas fa-heart"></i>
                                            @endif
                                        </a>
                                        <span>{{ $checkWishList == false ? __('Save') : __('Saved') }}</span>

                                    </li>

                                </ul>
                            </div>
                        </div>
                        <div class="mb-20"></div>
                        <div class="product-desc mb-40">
                            <h3 class="mb-20">{{ $keywords['Property Description'] ?? __('Property Description') }}</h3>
                            <p class=" summernote-content">{!! $propertyContent->description !!}</p>
                        </div>
                        {{-- @if (!empty(showAd(3)))
                             <div class="text-center mb-3 mt-3">
                                 {!! showAd(3) !!}
                             </div>
                         @endif --}}

                        @if (count($propertyContent->propertySpacifications) > 0)
                            <div class="row" class="mb-20">
                                <div class="col-12">
                                    <h3 class="mb-20"> {{ $keywords['Features'] ?? __('Features') }}</h3>
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
                            <div class="product-planning mb-40">
                                <h3 class="mb-20">{{ $keywords['Floor Planning'] ?? __('Floor Planning') }}</h3>
                                <div class="lazy-container radius-lg ratio ratio-16-11 border">
                                    <img class="lazyload"
                                        src="{{ asset($propertyContent->image) }}"
                                        data-src="{{ asset($propertyContent->image) }}">
                                </div>
                            </div>
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
                        {{-- @if (!empty(showAd(3)))
                             <div class="text-center mb-3 mt-3">
                                 {!! showAd(3) !!}
                             </div>
                         @endif --}}
                    </div>
                </div>
                <div class="col-lg-3 col-xl-4">
                    <aside class="sidebar-widget-area mb-10" data-aos="fade-up">
                        <div class="widget widget-form radius-md mb-30">
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
                                <button type="submit"
                                    class="btn btn-md btn-primary w-100">{{ $keywords['Send message'] ?? __('Send message') }}</button>


                            </form>
                        </div>
                        {{-- <x-tenant.frontend.agentContact :agent="$agent" :agentContact='false' :user="$user"
                             :propertyContent="$propertyContent" /> --}}

                        <div class="widget widget-recent radius-md mb-30 ">
                            <h3 class="title">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#products" aria-expanded="true" aria-controls="products">
                                    {{ $keywords['Related Property'] ?? __('Related Property') }}
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
                                                <span class="product-location icon-start"> <i
                                                        class="fal fa-map-marker-alt"></i>
                                                    {{ $property->city_name }}
                                                    {{ $userBs->property_state_status == 1 && $property->state_name != null ? ', ' . $property->state_name : '' }}
                                                    {{ $userBs->property_country_status == 1 && $property->country_name != null ? ', ' . $property->country_name : '' }}
                                                </span>
                                                <div class="product-price">

                                                    <span
                                                        class="new-price">{{ ($keywords['Price'] ?? __('Price')) . ':' }}
                                                        {{ $property->price ? $property->price : $keywords['Negotiable'] ?? __('Negotiable') }}</span>
                                                </div>
                                                <ul class="product-info p-0 list-unstyled d-flex align-items-center">
                                                    <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                                                        title="{{ $keywords['Area'] ?? __('Area') }}">
                                                        <i class="fal fa-vector-square"></i>
                                                        <span>{{ $property->area }}</span>
                                                    </li>
                                                    @if ($property->type == 'residential')
                                                        <li class="icon-start" data-tooltip="tooltip"
                                                            data-bs-placement="top"
                                                            title="{{ $keywords['Bed'] ?? __('Bed') }}">
                                                            <i class="fal fa-bed"></i>
                                                            <span>{{ $property->beds }} </span>
                                                        </li>
                                                        <li class="icon-start" data-tooltip="tooltip"
                                                            data-bs-placement="top"
                                                            title="{{ $keywords['Bath'] ?? __('Bath') }}">
                                                            <i class="fal fa-bath"></i>
                                                            <span>{{ $property->bath }} </span>
                                                        </li>
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
