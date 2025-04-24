@php
    $sliderData = is_string($api_Banner_settingsData) ? $api_Banner_settingsData : json_decode($api_Banner_settingsData);
    $slidertype = $sliderData->banner_type ?? null;
    $hero = null;
    if ($slidertype  === 'static') {
        $hero = $sliderData->static;
    }elseif ($slidertype  === 'slider'){
        $hero = $sliderData->slider;
    }

@endphp

@extends('user-front.realestate.layout')
@section('pageHeading', $keywords['Home'] ?? 'Home')
@section('metaDescription', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('metaKeywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')


@section('style')
<style>
    .caption {
            font-family: 'Courier New', Courier, monospace;
            color: #fff;
            font-size: 16px;
            font-weight: 400;
            margin-top: 10px;
        }
</style>

@endsection

@section('content')

@if ($sliderData->status !== false)
    @if($slidertype == 'slider')
    <section class="home-banner home-banner-2"  style="max-height: 600px; width: 100%; object-fit: cover;">
        <div class="container">

            <div class="swiper home-slider" id="home-slider-1">
                <div class="swiper-wrapper">
                    @foreach ($hero->slides as $slide)
                    <div class="swiper-slide"  data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                        <div class="content">
                            <span class="subtitle color-white">{{ $slide->title }}</span>
                            <h1 class="title color-white mb-0">{{ $slide->subtitle }}</h1>
                            <br>
                            @if ($slide->showButton)
                                <a href="{{ $slide->buttonUrl }}" class="btn btn-{{ $slide->buttonStyle ?? 'primary' }}">
                                    {{ $slide->buttonText }}
                                </a>
                                <p class="caption">{{ $slide->caption ?? '' }}</p>


                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="swiper-pagination pagination-fraction mt-40" id="home-slider-1-pagination"></div>
        </div>

        <div class="swiper home-img-slider" id="home-img-slider-1">
            <div class="swiper-wrapper">
            @foreach ($hero->slides as $slider)
                <div class="swiper-slide"  data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                    <img class="lazyload bg-img" src="{{ asset($slider->image) }}">
                </div>
            @endforeach


            </div>
        </div>
    </section>
    @elseif($slidertype == 'static')
        <section class="home-banner home-banner-1">
            @if (!empty($hero->image))
                <img class="lazyload bg-img" src="{{ asset($hero->image) }}">
            @else
                <div class="bg-img" style="background-color: #222; height: 500px;"></div>
            @endif

            <div class="container">
                <div class="row justify-content-center text-center align-items-center">
                    <div class="col-xxl-5">
                        <div class="content" data-aos="fade-up">
                            <h1 class="title">
                                {{ $hero->title }}
                            </h1>
                            <p class="text text-white">
                                {{ $hero->subtitle }}
                            </p>
                            @if ($hero->showButton)
                                <a href="{{ $hero->buttonUrl }}" class="btn btn-lg btn-{{ $hero->buttonStyle ?? 'primary' }}">
                                    {{ $hero->buttonText }}
                                </a>

                            @endif
                            <p class="caption">{{ isset($slide->caption) ? $slide->caption : '' }}</p>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    @endif
@endif
<div style="margin-top: 100px;">
</div>
<!-- // categories -->
@if (!empty($api_general_settingsData['show_properties']))
    @if ($properties->count() > 0)
        <div class="categories pb-100">
            <section id="property-filter-section">
                @include('user-front.realestate.partials.property-filter-list', [
                    'property_contents' => $properties,
                    'categories' => $all_proeprty_categories,
                    'all_cities' => $all_cities,
                    'min' => $min,
                    'max' => $max,
                    'userBs' => $userBs,
                    'keywords' => $keywords,
                    'userCurrentLang' => $userCurrentLang,
                    'userSeo' => $userSeo ?? null,
                    'amenities' => $amenities ?? []
                ])
            </section>

        </div>
    @endif
@endif

@if ($home_sections->counter_info_section == 1)
    @if(count($counterInformations) > 0)
        <div class="counter-area pt-100 pb-70">
            <div class="container">
                <div class="row gx-xl-5" data-aos="fade-up">
                    @forelse ($counterInformations as $counter)
                        <div class="col-sm-6 col-lg-3">
                            <div class="card mb-30">
                                <div class="d-flex align-items-center justify-content-center mb-10">
                                    <div class="card-icon me-2 color-secondary"><i class="{{ $counter->icon }}"></i>
                                    </div>
                                    <h2 class="m-0 color-secondary"><span class="counter">{{ $counter->count }}</span>+
                                    </h2>
                                </div>
                                <p class="card-text text-center">{{ $counter->title }}</p>
                            </div>
                        </div>
                    @empty
                        <!-- <div class="col-12">
                            <h3 class="text-center mt-20">
                                {{ $keywords['No Counter Information Found'] ?? __('No Counter Information Found') }} </h3>
                        </div> -->
                    @endforelse
                </div>
            </div>

        </div>
    @endif
@endif

<!-- // about -->
@if ($api_about_settingsData->status !== false)
    @if (!empty($api_about_settingsData))
    <section class="about-area pb-70 pt-30">
        <div class="container">
            <div class="row gx-xl-5">
                <div class="col-lg-6">
                    <div class="img-content" data-aos="fade-up">
                        @if (!empty($api_about_settingsData['image_path']))
                        <img class="lazyload blur-up"
                                    src="{{ asset('assets/front/images/placeholder.png') }}"
                                    data-src="{{ asset($api_about_settingsData['image_path']) }}"
                                    alt="About Image">


                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="content mb-30" data-aos="fade-up">
                        <div class="content-title">
                            <span class="subtitle"><span class="line"></span>
                                {{ $api_about_settingsData['title'] ?? '' }}</span>
                            <h2>{{ $api_about_settingsData['subtitle'] ?? '' }}</h2>
                        </div>

                        <div class="text summernote-content">
                            <p><strong>التاريخ:</strong> {{ $api_about_settingsData['history'] ?? '' }}</p>
                            <p><strong>مهمتنا:</strong> {{ $api_about_settingsData['mission'] ?? '' }}</p>
                            <p><strong>رؤيتنا:</strong> {{ $api_about_settingsData['vision'] ?? '' }}</p>
                        </div>

                        @if (!empty($api_about_settingsData['features']) && is_array($api_about_settingsData['features']))
                            <div class="features-list mt-4">
                                @foreach ($api_about_settingsData['features'] as $feature)
                                    <div class="mb-3">
                                        <h5 class="mb-1">{{ $feature['title'] ?? '' }}</h5>
                                        <p class="mb-0">{{ $feature['description'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Optional button if needed --}}
                        {{-- <div class="d-flex align-items-center flex-wrap gap-15 mt-4">
                            <a href="#" class="btn btn-lg btn-primary bg-secondary">اعرف المزيد</a>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
@endif



    <!-- skills -->
@if(count($skills) > 0)
    <section class="skills-area pb-70 pt-70">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 text-center">
                <div class="section-title title-inline mb-50" data-aos="fade-up">
                    <h2 class="title">{{ $home_text?->skill_title }}</h2>
                </div>
            </div>
        </div>

        <div class="row g-4" data-aos="fade-up">
            @forelse ($skills as $skill)
                <div class="col-lg-3 col-md-6">
                    <div class="card skill text-center p-4 shadow-sm border-0">
                        <div class="card-body">
                            <!-- Skill Icon -->
                            <div class="skill-icon mb-3 d-flex justify-content-center align-items-center"
                                 style="width: 60px; height: 60px; border-radius: 50%; background: #f8f9fa;">
                                <i class="{{ $skill->icon }}" style="font-size: 24px; color: {{ $skill->color ? '#' . $skill->color : '#007bff' }};"></i>
                            </div>

                            <!-- Skill Title -->
                            <h4 class="card-title mb-3" style="font-weight: 600; font-size: 1.2rem;">
                                {{ $skill->title }}
                            </h4>

                            <!-- Progress Bar -->
                            <div class="progress" style="height: 8px; border-radius: 10px; background: #e9ecef;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $skill->percentage }}%;
                                            background-color: {{ $skill->color ? '#' . $skill->color : 'var(--bs-primary)' }};
                                            border-radius: 10px;"
                                     aria-valuenow="{{ $skill->percentage }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>

                            <!-- Percentage Value -->
                            <span class="mt-2 d-block" style="font-size: 0.9rem; font-weight: 500; color: #555;">
                                {{ $skill->percentage }}%
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <!-- <div class="col-12">
                    <h3 class="text-center mt-20">{{ $keywords['No Skill Found'] ?? __('No Skill Found') }}</h3>
                </div> -->
            @endforelse
        </div>
    </div>
</section>
@endif

<!--// skills -->


@if ($home_sections->why_choose_us_section == 1)
    @if (!empty($home_text?->why_choose_us_section_image))
        <section class="choose-area pb-70">
            <div class="container">
                <div class="row gx-xl-5">
                    <div class="col-lg-7">
                        <div class="img-content mb-30 image-right" data-aos="fade-up">
                            <div class="img-1">
                                @if (!empty($home_text?->why_choose_us_section_image))
                                    <img class="lazyload blur-up"
                                        data-src="https://codecanyon8.kreativdev.com/estaty/assets/img/why-choose-us/65757ae71b9f0.jpg "
                                        alt="Image">
                                @endif
                                @if (!empty($home_text->why_choose_us_section_video_url))
                                    <a href="{{ $home_text->why_choose_us_section_video_url }}"
                                        class="video-btn youtube-popup p-absolute">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="img-2">
                                @if (!empty($home_text->why_choose_us_section_image_two))
                                    <img class="lazyload blur-up"
                                        data-src="https://codecanyon8.kreativdev.com/estaty/assets/img/why-choose-us/65757ae71c078.jpg"
                                        alt="Image">
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 order-lg-first">
                        <div class="content" data-aos="fade-up">
                            <div class="content-title">
                                <span class="subtitle"><span
                                        class="line"></span>{{ $home_text?->why_choose_us_section_title }}</span>
                                <h2>{{ $home_text?->why_choose_us_section_subtitle }}</h2>
                            </div>
                            <div class="text">{!! $home_text->why_choose_us_section_text !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endif

@if ($home_sections->project_section == 1)
    @if(count($projects) > 0)
        <section class="projects-area pt-100 pb-70">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title title-center mb-40" data-aos="fade-up">
                            <span class="subtitle"></span>
                            <h2 class="title mb-20">مشاريعنا</h2>
                        </div>
                    </div>
                    <div class="col-12" data-aos="fade-up">
                        <div class="row">
                            @forelse ($projects as $project)
                                <div class="col-lg-4 col-md-6 mb-30">
                                    <a
                                        href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                                        <div class="card product-default">
                                            <div class="card-img">
                                                <img src="{{ asset($project->featured_image) }}"
                                                    alt="Product">
                                                <span class="label">
                                                    {{ $project->complete_status == 1 ?  __('start selling') :  __('Under Construction') }}
                                                </span>
                                            </div>
                                            <div class="card-text product-title text-center p-3">
                                                <h3 class="card-title product-title color-white mb-1">
                                                    {{ @$project->title }}

                                                </h3>
                                                <span class="location icon-start"><i
                                                        class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>


                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <!-- <div class="p-3 text-center mb-30 w-100">
                                    <h3 class="mb-0"> {{ $keywords['No Projects Found'] ?? __('No Projects Found') }}
                                    </h3>
                                </div> -->
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endif

@if ($home_sections->testimonials_section == 1)

    @if(count($testimonials) > 0)
        <section class="testimonial-area pt-100 pb-70">
            <div class="overlay-bg d-none d-lg-block">
                <img class="lazyload blur-up"
                    data-src="https://codecanyon8.kreativdev.com/estaty/assets/front/images/gallery-bg.png">
            </div>
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4">
                        <div class="content mb-30" data-aos="fade-up">
                            <div class="content-title">
                                <span class="subtitle"><span
                                        class="line"></span>{{ $home_text?->testimonial_title }}</span>
                                <h2 class="title">
                                    {{ $home_text?->testimonial_subtitle }}</h2>
                            </div>
                            <p class="text mb-30">
                                {{ $home_text?->testimonial_text }}</p>

                            <div class="slider-navigation scroll-animate">
                                <button type="button" title="Slide prev" class="slider-btn slider-btn-prev">
                                    <i class="fal fa-angle-left"></i>
                                </button>
                                <button type="button" title="Slide next" class="slider-btn slider-btn-next">
                                    <i class="fal fa-angle-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8" data-aos="fade-up">
                        <div class="swiper" id="testimonial-slider-1">
                            <div class="swiper-wrapper">
                                @forelse ($testimonials as $testimonial)
                                    <div class="swiper-slide pb-30" data-aos="fade-up">
                                        <div class="slider-item">
                                            <div class="client-img">
                                                <div class="lazy-container ratio ratio-1-1">

                                                    @if (is_null($testimonial->image))
                                                        @if ($testimonial->gender === 'female')
                                                            <img data-src="{{ asset('assets/img/female-profile.jpg') }}" class="lazyload">
                                                        @else
                                                            <img data-src="{{ asset('assets/img/profile.jpg') }}" class="lazyload">
                                                        @endif
                                                    @else
                                                        <img class="lazyload"
                                                            data-src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}">
                                                    @endif


                                                </div>
                                            </div>
                                            <div class="client-content mt-30">
                                                <div class="quote">
                                                    <p class="text">{{ $testimonial->content }}</p>
                                                </div>
                                                <div
                                                    class="client-info d-flex flex-wrap gap-10 align-items-center justify-content-between">
                                                    <div class="content">
                                                        <h6 class="name">{{ $testimonial->name }}</h6>
                                                        <span class="designation">{{ $testimonial->occupation }}</span>
                                                    </div>
                                                    {{-- <div class="ratings">

                                                        <div class="rate">
                                                            <div class="rating-icon"
                                                                style="width: {{ $testimonial->rating * 20 }}%"></div>
                                                        </div>
                                                        <span class="ratings-total">({{ $testimonial->rating }}) </span>
                                                    </div> --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <!-- <div class="bg-light p-3 text-center mb-30 w-100">
                                        <h3 class="mb-0">
                                            {{ $keywords['No Testimonials Found'] ?? __('No Testimonials Found') }}</h3>
                                    </div> -->
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($home_sections->brand_section == 1)
        <div class="sponsor ptb-100 d-none" data-aos="fade-up">
            <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="swiper sponsor-slider">
                        <div class="swiper-wrapper">
                        @forelse ($brands as $brand)
                            <div class="swiper-slide">
                                <div class="item-single d-flex justify-content-center">
                                    <div class="sponsor-img">
                                    <a href="{{ $brand->brand_url }}" target="_blank">
                                        <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }} ">
                                    </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <!-- <div class="p-3 text-center mb-30 w-100">
                                <h3 class="mb-0">{{ $keywords['No Brands Found'] ?? __('No Brands Found') }}
                                </h3>
                            </div> -->
                        @endforelse
                        </div>
                        <!-- Slider pagination -->
                        <div class="swiper-pagination position-static mt-30" id="sponsor-slider-pagination"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    @endif

    @if ($home_sections->newsletter_section == 1)
        <section class="newsletter-area pb-100" data-aos="fade-up">
            <div class="container">
                <div class="newsletter-inner px-4">
                    <img class="lazyload bg-img"
                        src="https://codecanyon8.kreativdev.com/estaty/assets/img/6577e0ff3ab05.jpg">
                    <div class="row justify-content-center text-center" data-aos="fade-up">
                        <div class="col-lg-6 col-xxl-5">
                            <div class="content mb-30">
                                <span
                                    class="subtitle color-white mb-10 d-block">{{ $home_text?->newsletter_title }}</span>
                                <h2 class="color-white">{{ $home_text?->newsletter_subtitle }}</h2>
                            </div>
                            <form id="newsletterForm" class="subscription-form newsletter-form"
                                action="{{ route('front.user.subscriber', getParam()) }}" method="POST">
                                @csrf
                                <div class="input-group radius-md">
                                    <input class="form-control"
                                        placeholder="{{ $keywords['Enter Your Email'] ?? __('Enter Your Email') }}"
                                        type="email" name="email_id" required>
                                    <button class="btn btn-lg btn-primary" type="submit">
                                    كٌن على تواصل</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

@endif
@endsection

<script>
    if (typeof baseURL === 'undefined') {
        var baseURL = "{{ getDynamicBaseUrl() }}";
    }
</script>

<script>
    'use strict';

    $(window).on("load", function() {
        const delay = 350;

        /*============================================
            Aos animation
        ============================================*/
        var aosAnimation = function() {
            AOS.init({
                easing: "ease",
                duration: 1500,
                once: true,
                offset: 60,
                disable: 'mobile'
            });
        }
        aosAnimation();

    })

    /*============================================
        Price range
    ============================================*/


    var range_slider_max = document.getElementById('min');
    if (range_slider_max) {
        var sliders = document.querySelectorAll("[data-range-slider='priceSlider']");
        var filterSliders = document.querySelectorAll("[data-range-slider='filterPriceSlider']");
        var filterSliders2 = document.querySelectorAll("[data-range-slider='filterPriceSlider2']");
        var input0 = document.getElementById('min1');
        var input1 = document.getElementById('max1');

        var input20 = document.getElementById('min2');
        var input21 = document.getElementById('max2');

        var min = document.getElementById('min').value;
        var max = document.getElementById('max').value;

        var o_min = document.getElementById('o_min').value;
        var o_max = document.getElementById('o_max').value;

        // var c_min = document.getElementsByClassName('minval');
        // var c_max = document.getElementsByClassName('maxval');

        var currency_symbol = document.getElementById('currency_symbol').value;
        var min = parseFloat(min);
        var max = parseFloat(max);

        var o_min = parseFloat(o_min);
        var o_max = parseFloat(o_max);
        var inputs = [input0, input1];
        var inputs2 = [input20, input21];
        // Home price slider
        for (let i = 0; i < sliders.length; i++) {
            const el = sliders[i];

            noUiSlider.create(el, {
                start: [min, max],
                connect: true,
                step: 10,
                margin: 0,
                range: {
                    'min': o_min,
                    'max': o_max
                }
            }), el.noUiSlider.on("end", function(values, handle) {

                $("[data-range-value='priceSliderValue']").text(currency_symbol + values.join(" - " +
                    currency_symbol));

                inputs[handle].value = values[handle];
                updateURL('min=' + values[0]);
                updateURL('max=' + values[1]);
            })
        }
        // Filter price slider
        if (filterSliders) {
            for (let i = 0; i < filterSliders.length; i++) {
                const fsl = filterSliders[i];

                noUiSlider.create(fsl, {

                        start: [min, max],
                        connect: !0,
                        step: 10,
                        margin: 40,
                        range: {
                            'min': o_min,
                            'max': o_max
                        }
                    }), fsl.noUiSlider.on("update", function(values, handle) {
                        $("[data-range-value='filterPriceSliderValue']").text(currency_symbol + values.join(" - " +
                            currency_symbol));

                        inputs[handle].value = values[handle];
                    }), fsl.noUiSlider.on("change", function(values, handle) {

                        $("[data-range-value='filterPriceSliderValue']").text(currency_symbol + values.join(" - " +
                            currency_symbol));
                        inputs[handle].value = values[handle];
                    }),

                    inputs.forEach(function(input, handle) {
                        if (input) {
                            input.addEventListener('change', function() {
                                fsl.noUiSlider.setHandle(handle, this.value);
                            });
                        }
                    });
            }
        }


        // Filter price slider 2
        if (filterSliders2) {
            for (let i = 0; i < filterSliders2.length; i++) {
                const fsl2 = filterSliders2[i];

                noUiSlider.create(fsl2, {

                        start: [min, max],
                        connect: !0,
                        step: 10,
                        margin: 40,
                        range: {
                            'min': o_min,
                            'max': o_max
                        }
                    }), fsl2.noUiSlider.on("update", function(values, handle) {
                        $("[data-range-value='filterPriceSlider2Value']").text(currency_symbol + values.join(" - " +
                            currency_symbol));

                        inputs2[handle].value = values[handle];
                    }), fsl2.noUiSlider.on("change", function(values, handle) {

                        $("[data-range-value='filterPriceSlider2Value']").text(currency_symbol + values.join(" - " +
                            currency_symbol));
                        inputs2[handle].value = values[handle];
                    }),

                    inputs2.forEach(function(input, handle) {
                        if (input) {
                            input.addEventListener('change', function() {
                                fsl2.noUiSlider.setHandle(handle, this.value);
                            });
                        }
                    });

            }
        }
    }

    var imgUrl = "{{ url('/') }}";
    let baseURL = mainurl;
    var property_contents = @json($property_contents);
    var properties = property_contents.data;
    var siteURL = "{{ route('front.user.detail.view', getParam()) }}"
    const categoryUrl = "{{ route('front.user.get_categories', getParam()) }}";
</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="{{ asset('/assets/front/user/realestate/js/map.js') }}"></script>
<script src="{{ asset('/assets/front/user/realestate/js/properties.js') }}"></script>

