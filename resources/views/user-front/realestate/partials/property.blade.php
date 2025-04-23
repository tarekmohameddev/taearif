@php
$content = $property->contents->first();
@endphp
<div class="product-default radius-md mb-30" data-aos="fade-up" data-aos-delay="100">
    <figure class="product-img">

        <a href="{{ $content ? route('front.user.property.details', [getParam(), 'slug' => $content->slug]) : '#' }}"

            class="lazy-container ratio ratio-1-1">
            <img class="lazyload" {{-- src="assets/images/placeholder.png" --}}
                data-src="{{ asset($property->featured_image) }}"
                src="{{  asset($property->featured_image) }}">
        </a>
    </figure>
    <div class="product-details">
        <div class="d-flex align-items-center justify-content-between mb-10">


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
                href="{{ $content ? route('front.user.property.details', [getParam(), 'slug' => $content->slug]) : '#' }}">
                {{ $content->title ?? __('No title') }}
            </a>
        </h3>
        <hr>
        <div class="product-price">
            <span class="new-price">
                {{ $keywords['Price'] ?? __('Price') }}
                {{ $property->price ? formatNumber($property->price) : ($keywords['Negotiable'] ?? __('Negotiable')) }}
                <img src="{{ $userBs->base_currency_symbol }}" alt="Currency Symbol" style="width: 22px; height: 22px; vertical-align: middle;">
            </span>
        </div>

        <ul class="product-info p-0 list-unstyled d-flex align-items-center">
            {{-- Area --}}
            @if (!empty($property->area))
            <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                title="{{ $keywords['Area'] ?? __('Area') }}">
                <i class="fal fa-vector-square"></i>
                <span>{{ $property->area }} {{ $keywords['Sqft'] ?? __('Sqft') }}</span>
            </li>
            @endif
            {{-- Beds --}}
            @if (!empty($property->beds))
            <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                title="{{ $keywords['Beds'] ?? __('Beds') }}">
                <i class="fal fa-bed"></i>
                <span>{{ $property->beds }} {{ $keywords['Beds'] ?? __('Beds') }}</span>
            </li>
            @endif

            {{-- Baths --}}
            @if (!empty($property->bath))
            <li class="icon-start" data-tooltip="tooltip" data-bs-placement="top"
                title="{{ $keywords['Baths'] ?? __('Baths') }}">
                <i class="fal fa-bath"></i>
                <span>{{ $property->bath }} {{ $keywords['Baths'] ?? __('Baths') }}</span>
            </li>
            @endif
        </ul>

    </div>

    @if (!empty($property->purpose))
    <span class="label">
        @if ($property->purpose == 'rent')
        {{ $keywords['Rent'] ?? __('Rent') }}
        @elseif($property->purpose == 'sale')
        {{ $keywords['Sale'] ?? __('Sale') }}
        @else
        {{ __(ucfirst($property->purpose)) }}
        @endif
    </span>
    @endif

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
