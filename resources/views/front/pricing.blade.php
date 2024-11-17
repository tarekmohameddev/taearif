@extends('front.layout')

@section('pagename')
    - {{ __('Pricing') }}
@endsection

@section('meta-description', !empty($seo) ? $seo->pricing_meta_description : '')
@section('meta-keywords', !empty($seo) ? $seo->pricing_meta_keywords : '')

@section('breadcrumb-title')
    {{ __('Pricing') }}
@endsection
@section('breadcrumb-link')
    {{ __('Pricing') }}
@endsection

@section('content')

    <!--====== Start saas-pricing section ======-->
    <div class="pricing-area pt-120 pb-90">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    @if (count($terms) > 1)
                        <div class="nav-tabs-navigation text-center" data-aos="fade-up">
                            <ul class="nav nav-tabs">

                                @foreach ($terms as $term)
                                    <li class="nav-item">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                            data-bs-target="#{{ strtolower($term) }}"
                                            type="button">{{ __("$term") }}</button>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="tab-content">
                        @foreach ($terms as $term)
                            <div class="tab-pane fade  {{ $loop->first ? 'show active' : '' }}"
                                id="{{ strtolower($term) }}">
                                <div class="row justify-content-center">
                                    @php
                                        $packages = \App\Models\Package::where('status', '1')
                                            ->where('term', strtolower($term))
                                            ->orderBy('serial_number', 'ASC')
                                            ->get();
                                    @endphp
                                    @foreach ($packages as $package)
                                        @php
                                            $pFeatures = json_decode($package->features);
                                        @endphp
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card mb-30" data-aos="fade-up" data-aos-delay="100">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon"><i class="{{ $package->icon }}"></i></div>
                                                    <div class="label">
                                                        <h4>{{ __($package->title) }}</h4>
                                                    </div>
                                                </div>


                                                <div class="d-flex align-items-center my-3">
                                                    <span class="price">
                                                        {{ $package->price != 0 && $be->base_currency_symbol_position == 'left' ? $be->base_currency_symbol : '' }}{{ $package->price == 0 ? 'Free' : $package->price }}{{ $package->price != 0 && $be->base_currency_symbol_position == 'right' ? $be->base_currency_symbol : '' }}
                                                    </span>
                                                    <span class="period">/ @if ($package->term == 'monthly')
                                                            {{ __('month') }}
                                                        @elseif($package->term == 'yearly')
                                                            {{ __('year') }}
                                                        @else
                                                            {{ __($package->term) }}
                                                        @endif
                                                    </span>

                                                </div>
                                                <h5>{{ __("What's Included") }}</h5>
                                                <ul class="pricing-list list-unstyled p-0"
                                                    data-more="{{ __('Show More') }}" data-less="{{ __('Show Less') }}">
                                                    @foreach ($allPfeatures as $feature)
                                                        <li>
                                                            @if (is_array($pFeatures) && in_array($feature, $pFeatures))
                                                                <i class="fal fa-check"></i>
                                                            @else
                                                                <i class="fal fa-times"></i>
                                                            @endif

                                                            @if ($feature == 'vCard' && is_array($pFeatures) && in_array($feature, $pFeatures))
                                                                @if ($package->number_of_vcards == 999999)
                                                                    {{ __('Unlimited') }} {{ __('vCards') }}
                                                                @elseif(empty($package->number_of_vcards))
                                                                    0 {{ __('vCard') }}
                                                                @else
                                                                    {{ $package->number_of_vcards }}
                                                                    {{ $package->number_of_vcards > 1 ? __('vCards') : __('vCard') }}
                                                                @endif
                                                                @continue
                                                            @elseif($feature == 'vCard' && (is_array($pFeatures) && !in_array($feature, $pFeatures)))
                                                                {{ __('vCards') }}
                                                                @continue
                                                            @endif
                                                            {{ __("$feature") }}
                                                            @if ($feature == 'Plugins')
                                                                ({{ __('Google Analytics, Disqus, WhatsApp, Facebook Pixel, Tawk.to') }})
                                                            @endif
                                                        </li>
                                                    @endforeach


                                                </ul>
                                                <div class="btn-groups">
                                                    @if ($package->is_trial === '1' && $package->price != 0)
                                                        <a href="{{ route('front.register.view', ['status' => 'trial', 'id' => $package->id]) }}"
                                                            class="btn btn-lg btn-primary no-animation">{{ __('Trial') }}</a>
                                                    @endif
                                                    @if ($package->price == 0)
                                                        <a href="{{ route('front.register.view', ['status' => 'regular', 'id' => $package->id]) }}"
                                                            class="btn btn-lg btn-outline no-animation">{{ __('Signup') }}</a>
                                                    @else
                                                        <a href="{{ route('front.register.view', ['status' => 'regular', 'id' => $package->id]) }}"
                                                            class="btn btn-lg btn-outline no-animation">{{ __('Purchase') }}</a>
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
        <!-- Bg Shape -->
        <div class="shape">
            <img class="shape-1" src="{{ asset('assets/frontend/images/shape/shape-6.png') }}" alt="Shape">
            <img class="shape-2" src="{{ asset('assets/frontend/images/shape/shape-7.png') }}" alt="Shape">
            <img class="shape-3" src="{{ asset('assets/frontend/images/shape/shape-1.png') }}" alt="Shape">
            <img class="shape-4" src="{{ asset('assets/frontend/images/shape/shape-4.png') }}" alt="Shape">
            <img class="shape-5" src="{{ asset('assets/frontend/images/shape/shape-3.png') }}" alt="Shape">
            <img class="shape-6" src="{{ asset('assets/frontend/images/shape/shape-9.png') }}" alt="Shape">
        </div>
    </div>
    <!--====== End saas-pricing section ======-->
@endsection
