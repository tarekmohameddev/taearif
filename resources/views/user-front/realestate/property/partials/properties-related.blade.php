
@props([
    'relatedProperty',
    'userBs',
    'keywords',
])

<div class="widget widget-recent radius-md mb-30">
    <h3 class="title">
        <button class="accordion-button" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#products"
                aria-expanded="true">
            {{ $keywords['Related'] ?? __('Related') }}
        </button>
    </h3>

    <div id="products" class="collapse show">
        <div class="accordion-body p-0">
            @foreach ($relatedProperty as $property)
                <div class="product-default product-inline mt-20">
                    {{-- thumbnail --}}
                    <figure class="product-img">
                        <a href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug]) }}"
                           class="lazy-container ratio ratio-1-1 radius-md">
                            <img  class="lazyload"
                                  data-src="{{ asset($property->featured_image) }}"
                                  src="{{ asset($property->featured_image) }}">
                        </a>
                    </figure>

                    {{-- details --}}
                    <div class="product-details">
                        <h6 class="product-title">
                            <a href="{{ route('front.user.property.details', [getParam(), 'slug' => $property->slug]) }}">
                                {{ $property->title }}
                            </a>
                        </h6>

                        {{-- Location (city / state / country) --}}
                        @php
                            // Build the full location once, so we can test it easily
                            $location = trim(
                                ($property->city_name ?? '') .
                                ($userBs->property_state_status  && $property->state_name   ? ', ' . $property->state_name   : '') .
                                ($userBs->property_country_status && $property->country_name ? ', ' . $property->country_name : '')
                            );
                        @endphp

                        @if($location !== '')
                            <span class="product-location icon-start">
                                <i class="fal fa-map-marker-alt"></i>
                                {{ $location }}
                            </span>
                        @endif


                        {{-- price --}}
                        @if(!empty($property->price))
                            <div class="product-price">
                                <span class="new-price">
                                    {{ ($keywords['Price'] ?? __('Price')).':' }}
                                    {{ number_format($property->price, fmod($property->price,1)==0?0:2) }}
                                </span>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/9/98/Saudi_Riyal_Symbol.svg"
                                     alt="SAR" style="width:12px;height:15px;vertical-align:middle;">
                                @if($property->payment_method && $property->payment_method !== 'null')
                                    <span class="new-price">
                                        / {{ __($property->payment_method) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- quick specs --}}
                        <ul class="product-info p-0 list-unstyled d-flex align-items-center">
                            @if($property->area > 0)
                                <li class="icon-start" data-tooltip="tooltip"
                                    title="{{ $keywords['Area'] ?? __('Area') }}">
                                    <i class="fal fa-vector-square"></i>
                                    <span>{{ number_format($property->area, fmod($property->area,1)==0?0:2) }}</span>
                                </li>
                            @endif

                            @if($property->type === 'residential')
                                @if($property->beds > 0)
                                    <li class="icon-start" title="{{ $keywords['Bed'] ?? __('Bed') }}">
                                        <i class="fal fa-bed"></i><span>{{ $property->beds }}</span>
                                    </li>
                                @endif
                                @if($property->bath > 0)
                                    <li class="icon-start" title="{{ $keywords['Bath'] ?? __('Bath') }}">
                                        <i class="fal fa-bath"></i><span>{{ $property->bath }}</span>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>
                </div><!-- .product-default -->
            @endforeach
        </div>
    </div>
</div>
