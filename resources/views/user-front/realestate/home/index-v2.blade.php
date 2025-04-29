@php
$sliderData = is_string($api_Banner_settingsData) ? $api_Banner_settingsData : json_decode($api_Banner_settingsData);
$slidertype = $sliderData->banner_type ?? null;
$hero = null;
if ($slidertype === 'static') {
$hero = $sliderData->static;
}elseif ($slidertype === 'slider'){
$hero = $sliderData->slider;
}
@endphp

@extends('user-front.realestate.layout')
@section('pageHeading', $keywords['Home'] ?? 'Home')
@section('style')
<style>
    .header-area.header-2:not(.header-static, .is-sticky) :is(.nav-link:not(:is(.active, .menu-dropdown .nav-link)), .wishlist-btn, .nice-select, .nice-select::after) {
        font-weight: var(--font-medium);
    }

    .caption {
        font-family: 'Courier New', Courier, monospace;
        color: #fff;
        font-size: 16px;
        font-weight: 400;
        margin-top: 10px;
    }
</style>
@endsection
{{-- @section('pageHeading')
     {{ $keywords['Home'] ?? 'Home' }}
@endsection --}}


@section('metaDescription', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('metaKeywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')


@section('content')

@if (!empty($sliderData) && $sliderData->status !== false)

    @if($slidertype == 'slider')
    <section class="home-banner home-banner-2" style="max-height: 600px; width: 100%; object-fit: cover;">

        <div class="container">

            <div class="swiper home-slider" id="home-slider-1">
                <div class="swiper-wrapper">
                    @foreach ($hero->slides as $slide)
                    <div class="swiper-slide" data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                        <div class="content">
                            <span class="subtitle color-white">{{ $slide->title }}</span>
                            <h1 class="title color-white mb-0">{{ $slide->subtitle }}</h1>
                            <br>
                            @if ($slide->showButton)
                            <a href="{{ $slide->buttonUrl }}" class="btn btn-{{ $slide->buttonStyle ?? 'primary' }}">
                                {{ $slide->buttonText }}
                            </a>
                            <p class="caption">{{ isset($slide->caption) ? $slide->caption : '' }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="banner-filter-form mt-40 d-none" data-aos="fade-up">
                <div class="row justify-content-center">
                    <div class="col-xxl-10">
                        <div class="tabs-navigation">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <button class="nav-link btn-md rounded-pill active" data-bs-toggle="tab" data-bs-target="#rent" type="button">{{ $keywords['Rent'] ?? __('Rent') }}</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link btn-md rounded-pill" data-bs-toggle="tab" data-bs-target="#sale" type="button">{{ $keywords['Sale'] ?? __('Sale') }}</button>
                                </li>

                            </ul>
                        </div>
                        <div class="tab-content form-wrapper radius-md">
                            <input type="hidden" id="currency_symbol" value="{{ $userBs->base_currency_symbol }}">
                            <input type="hidden" name="min" value="{{ $min }}" id="min">
                            <input type="hidden" name="max" value="{{ $max }}" id="max">

                            <input class="form-control" type="hidden" value="{{ $min }}" id="o_min">
                            <input class="form-control" type="hidden" value="{{ $max }}" id="o_max">
                            <div class="tab-pane fade show active" id="rent">
                                <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                    <input type="hidden" name="purposre" value="rent">
                                    <input type="hidden" name="min" value="{{ $min }}" id="min1">
                                    <input type="hidden" name="max" value="{{ $max }}" id="max1">
                                    <div class="grid">
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                                <input type="text" id="search1" name="location" class="form-control" placeholder="{{ $keywords['Location'] ?? __('Location') }}">
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="type" class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                                <select aria-label="#" name="type" class="form-control select2 type" id="type">
                                                    <option selected disabled value="">
                                                        {{ $keywords['Select Property'] ?? __('Select Property') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                    <option value="residential">
                                                        {{ $keywords['Residential'] ?? __('Residential') }}
                                                    </option>
                                                    <option value="commercial">
                                                        {{ $keywords['Commercial'] ?? __('Commercial') }}
                                                    </option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="category" class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                                <select aria-label="#" class="form-control select2 bringCategory" id="category" name="category">
                                                    <option selected disabled value="">{{ __('Select Category') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                    @foreach ($all_proeprty_categories as $category)
                                                    <option value="{{ $category->slug }}">
                                                        {{ $category->name }}
                                                    </option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid-item city">
                                            <div class="form-group">
                                                <label for="city" class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                                <select aria-label="#" name="city" class="form-control select2 city_id" id="city">
                                                    <option selected disabled value="">
                                                        {{ $keywords['Select City'] ?? __('Select City') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                    @foreach ($all_cities as $city)
                                                    <option data-id="{{ $city->id }}" value="{{ $city->name }}">
                                                        {{ $city->name }}
                                                    </option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                                <span data-range-value="filterPriceSliderValue">{{ formatNumber($min) }}
                                                    -
                                                    {{ formatNumber($max) }}</span>
                                            </label>
                                            <div data-range-slider="filterPriceSlider"></div>
                                        </div>
                                        <div class="grid-item">
                                            <button type="submit" class="btn btn-lg btn-primary bg-primary icon-start w-100">
                                                {{ $keywords['Search'] ?? __('Search') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="sale">
                                <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                    <input type="hidden" name="purposre" value="sale">
                                    <input type="hidden" name="min" value="{{ $min }}" id="min2">
                                    <input type="hidden" name="max" value="{{ $max }}" id="max2">
                                    <div class="grid">
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                                <input type="text" id="search1" name="location" class="form-control" placeholder="{{ $keywords['Location'] ?? __('Location') }}">
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="type1" class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                                <select aria-label="#" name="type" class="form-control select2 type" id="type1">
                                                    <option selected disabled value="">
                                                        {{ $keywords['Select Property'] ?? __('Select Property') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                    <option value="residential">
                                                        {{ $keywords['Residential'] ?? __('Residential') }}
                                                    </option>
                                                    <option value="commercial">
                                                        {{ $keywords['Commercial'] ?? __('Commercial') }}
                                                    </option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <div class="form-group">
                                                <label for="category1" class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                                <select aria-label="#" class="form-control select2 bringCategory" id="category1" name="category">
                                                    <option selected disabled value="">
                                                        {{ $keywords['Select Category'] ?? __('Select Category') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                    @foreach ($all_proeprty_categories as $category)
                                                    <option value="{{ $category->slug }}">
                                                        {{ $category->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid-item city">
                                            <div class="form-group">
                                                <label for="city1" class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                                <select aria-label="#" name="city" class="form-control select2 city_id" id="city1">
                                                    <option selected disabled value="">
                                                        {{ $keywords['Select City'] ?? __('Select City') }}
                                                    </option>
                                                    <option value="all">{{ $keywords['All'] ?? __('All') }}</option>

                                                    @foreach ($all_cities as $city)
                                                    <option data-id="{{ $city->id }}" value="{{ $city->name }}">
                                                        {{ $city->name }}
                                                    </option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid-item">
                                            <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                                <span data-range-value="filterPriceSlider2Value">{{ formatNumber($min) }}
                                                    -
                                                    {{ formatNumber($max) }}</span>
                                            </label>
                                            <div data-range-slider="filterPriceSlider2"></div>
                                        </div>
                                        <div class="grid-item">
                                            <button type="submit" class="btn btn-lg btn-primary bg-primary icon-start w-100">
                                                {{ $keywords['Search'] ?? __('Search') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination pagination-fraction mt-40" id="home-slider-1-pagination"></div>
        </div>

        <div class="swiper home-img-slider" id="home-img-slider-1">
            <div class="swiper-wrapper">
                @foreach ($hero->slides as $slider)
                <div class="swiper-slide" data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                    <img class="lazyload bg-img" src="{{ asset($slider->image) }}">
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @else($slidertype == 'static')
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
        @include('user-front.realestate.partials.categories-filter-list', [
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

<style>
    .info-box {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .info-box:hover {
        transform: translateY(-5px);
    }

    .info-icon {
        font-size: 1.5rem;
        margin-left: 10px;
        color: #002d72;
        /* Primary blue color */
    }

    .title-wrapper {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .slide-in {
        animation: slideFromLeft 0.5s ease-out forwards;
    }

    @keyframes slideFromLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    h4 {
        margin: 0;
        font-size: 1.5rem;
        color: #002d72;
        /* Primary blue color */
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- // about -->
@if (!empty($api_about_settingsData) && $api_about_settingsData->status !== false)
    @if (!empty($api_about_settingsData))
    <div class="container py-5">
        <div class="row align-items-center">

            @if (!empty($api_about_settingsData['image_path']))
            <div class="col-lg-6 mb-4 mb-lg-0 lazyload blur-up">
                <img class="lazyload img-fluid blur-up"
                    src="{{ asset('assets/front/images/placeholder.png') }}"
                    data-src="{{ asset($api_about_settingsData['image_path']) }}"
                    alt="About Image">
            </div>
            @endif


            <div class="col-lg-6">

                <div class="info-box slide-in">
                    <div class="title-wrapper">
                        <i class="bi bi-building info-icon"></i>
                        <h4>هويتنا</h4>
                    </div>
                    <p class="mb-0">{{ $api_about_settingsData['history'] ?? '...' }}</p>
                </div>


                <div class="info-box slide-in" style="animation-delay: 0.5s;">
                    <div class="title-wrapper">
                        <i class="bi bi-rocket-takeoff info-icon"></i>
                        <h4>مهمتنا</h4>
                    </div>
                    <p class="mb-0">{{ $api_about_settingsData['mission'] ?? '...' }}</p>
                </div>


                <div class="info-box slide-in" style="animation-delay: 1s;">
                    <div class="title-wrapper">
                        <i class="bi bi-stars info-icon"></i>
                        <h4>رؤيتنا</h4>
                    </div>
                    <p class="mb-0">{{ $api_about_settingsData['vision'] ?? '...' }}</p>
                </div>
            </div>
        </div>
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

@if ($home_sections->category_section == 1)
@if(count($property_categories) > 0)
<!-- الفئات -->

<section class="category pt-100 pb-70 bg-light d-none">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-inline mb-40" data-aos="fade-up">
                    <!-- <h2 class="title">{{ $home_text?->category_section_title }}</h2> -->
                    <h2 class="title">الفئات</h2>
                    <!-- Slider navigation buttons -->
                    <div class="slider-navigation">
                        <button type="button" title="Slide prev" class="slider-btn cat-slider-btn-prev rounded-pill">
                            <i class="fal fa-angle-left"></i>
                        </button>
                        <button type="button" title="Slide next" class="slider-btn cat-slider-btn-next rounded-pill">
                            <i class="fal fa-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="swiper" id="category-slider-1">
                    <div class="swiper-wrapper">
                        @forelse ($property_categories as $category)
                        <div class="swiper-slide mb-30 color-1" data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                            <a href="{{ route('front.user.properties', [getParam(), 'category' => $category->categoryContent?->slug]) }}">
                                <div class="category-item bg-white radius-md text-center">
                                    <div class="category-icons ">
                                        <img src="{{ asset('assets/img/property-category/' . $category->image) }}">
                                    </div>
                                    <span class="category-title d-block mt-3 m-0 color-medium">{{ $category->name }}</span>
                                </div>
                            </a>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class=" p-3 text-center mb-30">
                                <h3 class="mb-0">
                                    {{ $keywords['No Categories Found'] ?? __('No Categories Found') }}
                                </h3>
                            </div>
                        </div>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endif


<section class="video-banner with-radius pt-100 pb-70 d-none">
    <!-- Background Image -->
    <div class="bg-overlay">
        <img class="lazyload bg-img" src="https://codecanyon8.kreativdev.com/estaty/assets/img/6576af4f8ac2d.jpg">
    </div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <div class="content mb-30" data-aos="fade-up">
                    <span class="subtitle text-white">فيديو</span>
                    <h2 class="title text-white mb-10">كيف يمكتك شراء وحدة عقارية من شركة شاهقة ومراحل الشراء</h2>
                    <p class="text-white m-0 w-75 w-sm-100">يمكنك عن طريق شاهقة تملك وحده عقارية بسهوله تامة, وبدون اي تعقيدات ادارية</p>
                </div>
            </div>
            <div class="col-lg-7">

                <div class="d-flex align-items-center justify-content-center h-100 mb-30" data-aos="fade-up">
                    <a href="#" class="video-btn youtube-popup">
                        <i class="fas fa-play"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>


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
                        <a href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                            <div class="card product-default">
                                <div class="card-img">
                                    <img src="{{ asset($project->featured_image) }}" alt="Product">
                                    <span class="label">
                                    {{ $project->complete_status == 1 ?  __('start selling') :  __('Under Construction') }}
                                    </span>
                                </div>
                                <div class="card-text product-title text-center p-3">
                                    <h3 class="card-title product-title color-white mb-1">
                                        {{ @$project->title }}

                                    </h3>
                                    <span class="location icon-start"><i class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>


                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="p-3 text-center mb-30 w-100">
                        <h3 class="mb-0"> {{ $keywords['No Projects Found'] ?? __('No Projects Found') }}
                        </h3>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endif


@if ($home_sections->property_section == 1)
@if(count($properties) > 0)
<section class="product-area popular-product pb-70 d-none">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-center mb-10" data-aos="fade-up">
                    <h2 class="title mb-20">الوحدات</h2>
                    <div class="slider-navigation mb-20">
                        <button type="button" title="Slide prev" class="slider-btn product-slider-btn-prev">
                            <i class="fal fa-angle-left"></i>
                        </button>
                        <button type="button" title="Slide next" class="slider-btn product-slider-btn-next">
                            <i class="fal fa-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="swiper product-slider">
                    <div class="swiper-wrapper">
                        @forelse ($properties as $property)
                        <div class="swiper-slide">
                            @include('user-front.realestate.partials.property')
                        </div>
                        @empty
                        <!-- <div class="p-3 text-center mb-30 w-100">
                                        <h3 class="mb-0">
                                            {{ $keywords['No Properties Found'] ?? __('No Properties Found') }}</h3>
                                    </div> -->
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endif


@if ($home_sections->testimonials_section == 1)
@if(count($testimonials) > 0)
<section class="testimonial-area testimonial-2 with-radius pt-100 pb-70">
    <!-- Bg image -->
    @if ($home_text->testimonial_image)
    <img class="lazyload bg-img" src="https://aqar-riyadh.site/website/images/our_clintes.png">
    @endif

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4">
                <div class="content mb-30" data-aos="fade-up">
                    <div class="content-title">
                        <span class="subtitle">
                            {{ $home_text?->testimonial_title }}</span>
                        <h2 class="title">
                            {{ $home_text?->testimonial_subtitle }}
                        </h2>
                    </div>
                    <p class="text mb-30">
                        {{ $home_text?->testimonial_text }}
                    </p>
                    <!-- Slider pagination -->
                    <div class="swiper-pagination pagination-fraction" id="testimonial-slider-2-pagination">
                    </div>
                </div>
            </div>
            <div class="col-lg-8" data-aos="fade-up">
                <div class="swiper" id="testimonial-slider-2">
                    <div class="swiper-wrapper">
                        @forelse ($testimonials as $testimonial)
                        <div class="swiper-slide pb-30" data-swiper-autoplay="{{ $hero->autoplaySpeed ?? 5000 }}">
                            <div class="slider-item">
                                <div class="client-content">
                                    <div class="quote">
                                        <p class="text mb-20">{{ $testimonial->content }}</p>
                                        {{-- <div class="ratings">
                                                        <div class="rate">
                                                            <div class="rating-icon"
                                                                style="width: {{ $testimonial->rating * 20 }}%">
                                    </div>
                                </div>
                                <span class="ratings-total">({{ $testimonial->rating }}) </span>
                            </div> --}}
                        </div>
                        <div class="client-info d-flex align-items-center">
                            <div class="client-img position-static">
                                <div class="lazy-container rounded-pill ratio ratio-1-1">
                                    @if (is_null($testimonial->image))
                                    <img data-src="{{ asset('assets/img/profile.jpg') }}" class="lazyload">
                                    @else
                                    <img class="lazyload" data-src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}">
                                    @endif

                                </div>
                            </div>
                            <div class="content">
                                <h6 class="name">{{ $testimonial->name }}</h6>
                                <span class="designation">{{ $testimonial->occupation }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-3 text-center mb-30 w-100">
                <h3 class="mb-0">
                    {{ $keywords['No Testimonials Found'] ?? __('No Testimonials Found') }}
                </h3>
            </div>
            @endforelse
        </div>
    </div>
    </div>
    </div>
    </div>
</section>
@endif
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

@if ($home_sections->brand_section == 1)
@if(count($brands) > 0)
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
                        <div class="p-3 text-center mb-30 w-100">
                            <h3 class="mb-0">{{ $keywords['No Brands Found'] ?? __('No Brands Found') }}
                            </h3>
                        </div>
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
@endif
@endsection

<script>
    if (typeof baseURL === 'undefined') {
        var baseURL = "{{ getDynamicBaseUrl() }}";
    }
</script>
