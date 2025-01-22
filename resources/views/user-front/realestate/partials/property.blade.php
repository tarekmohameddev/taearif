<div class="product-default radius-md mb-30" data-aos="fade-up" data-aos-delay="100">
    <figure class="product-img">
        <a href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug ?? $property->propertyContent->slug]) }}"
            class="lazy-container ratio ratio-1-1">
            <img class="lazyload" {{-- src="assets/images/placeholder.png" --}}
                data-src="{{ asset('assets/img/property/featureds/' . $property->featured_image) }}"
                src="{{ asset('assets/img/property/featureds/' . $property->featured_image) }}">
        </a>
    </figure>
    <div class="product-details">
        <div class="d-flex align-items-center justify-content-between mb-10">
            <div class="author  ">


                @if ($property->user)
                    <a class="color-medium" {{-- href="{{ route('frontend.vendor.details', ['username' => $property->vendor->username]) }}" --}} target="_self">

                        <img class="blur-up ls-is-cached lazyloaded"
                            data-src="{{ $property->user->photo ? asset('assets/front/img/user/' . $property->user->photo) : asset('assets/img/user-profile.jpg') }}"
                            src="{{ $property->user->photo ? asset('assets/front/img/user/' . $property->user->photo) : asset('assets/img/user-profile.jpg') }}">



                        <span>{{ $keywords['By'] ?? __('By') }} {{ $property->user->username }}</span>
                @endif

                </a>
            </div>

            <span class="product-category text-sm @if (in_array($userBs->theme, ['home_five'])) text-dark @endif">
                @if ($property->type == 'residential')
                    {{ $keywords['Residential'] ?? __('Residential') }}
                @elseif($property->type == 'commercial')
                    {{ $keywords['Commercial'] ?? __('Commercial') }}
                @else
                    {{ __(ucfirst($property->type)) }}
                @endif
            </span>

        </div>
        <h3 class="product-title">
            <a class="@if (in_array($userBs->theme, ['home_five'])) text-dark @endif"
                href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug ?? $property->propertyContent->slug]) }}">{{ $property->title ?? $property->propertyContent->title }}</a>
        </h3>

        <span class="product-location icon-start @if (in_array($userBs->theme, ['home_five'])) text-dark @endif"> <i
                class="fal fa-map-marker-alt"></i>
            {{ $property->city_name }}
            {{ $userBs->property_state_status == 1 && $property->state_name != null ? ', ' . $property->state_name : '' }}
            {{ $userBs->property_country_status == 1 && $property->country_name != null ? ', ' . $property->country_name : '' }}
        </span>
        <div class="product-price">
            <span class="new-price">{{ $keywords['Price'] ?? __('Price') }} ({{ $userBs->base_currency_text }}) :
                {{ $property->price ? formatNumber($property->price) : $keywords['Negotiable'] ?? __('Negotiable') }}</span>
        </div>
        <ul class="product-info p-0 list-unstyled d-flex align-items-center">
            <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                title="{{ $keywords['Area'] ?? __('Area') }}">
                <i class="fal fa-vector-square"></i>
                <span>{{ $property->area }} {{ $keywords['Sqft'] ?? __('Sqft') }}</span>
            </li>
            @if ($property->type == 'residential')
                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                    title="{{ $keywords['Beds'] ?? __('Beds') }}">
                    <i class="fal fa-bed"></i>
                    <span>{{ $property->beds }} {{ $keywords['Beds'] ?? __('Beds') }}</span>
                </li>
                <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                    title="{{ $keywords['Baths'] ?? __('Baths') }}">
                    <i class="fal fa-bath"></i>
                    <span>{{ $property->bath }} {{ $keywords['Baths'] ?? __('Baths') }}</span>
                </li>
            @endif
        </ul>
    </div>

    <span class="label">
        @if ($property->purpose == 'rent')
            {{ $keywords['Rent'] ?? __('Rent') }}
        @elseif($property->purpose == 'sale')
            {{ $keywords['Sale'] ?? __('Sale') }}
        @else
            {{ __(ucfirst($property->purpose)) }}
        @endif
    </span>
    @if (Auth::guard('customer')->check())
        @php
            $customer_id = Auth::guard('customer')->user()->id;

            $checkWishList = checkWishList($property->id, $customer_id);
        @endphp
    @else
        @php
            $checkWishList = false;
        @endphp
    @endif

    <a href="{{ route('front.user.property.add-to-wishlist', [getParam(), 'id' => $property->id]) }}"
        class="btn-wishlist {{ $checkWishList == false ? '' : 'wishlist-active' }}" data-tooltip="tooltip"
        data-bs-placement="top"
        title="{{ $checkWishList == false ? $keywords['Add to Wishlist'] ?? __('Add to Wishlist') : $keywords['Saved'] ?? __('Saved') }}">
        <i class="fal fa-heart"></i>
    </a>
</div>
