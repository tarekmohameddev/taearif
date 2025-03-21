@php
    $sliderData = json_decode($api_Banner_settingsData);
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
    .header-area.header-2:not(.header-static, .is-sticky) :is(.nav-link:not(:is(.active, .menu-dropdown .nav-link)), .wishlist-btn, .nice-select, .nice-select::after) {
        color: var(--color-dark);
        font-weight: var(--font-medium);
    }
</style>
@endsection

@section('content')

@if ($slidertype === 'slider')
<section class="home-banner home-banner-2">
    <div class="container">

        <div class="swiper home-slider" id="home-slider-1">
            <div class="swiper-wrapper">
                @foreach ($hero->slides as $slide)
                <div class="swiper-slide">
                    <div class="content">
                        <span class="subtitle color-white">{{ $slide->title }}</span>
                        <h1 class="title color-white mb-0">{{ $slide->subtitle }}</h1>
                        <br>
                        @if ($slide->showButton)
                            <a href="{{ $slide->buttonUrl }}" class="btn btn-{{ $slide->buttonStyle ?? 'primary' }}">
                                {{ $slide->buttonText }}
                            </a>
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
            <div class="swiper-slide">
                <img class="lazyload bg-img" src="https://taearifdev.com/assets/front/img/user/home_settings/67d16c0704ed7.jpg">
            </div>
        @endforeach


        </div>
    </div>
</section>
@elseif ($slidertype === 'static')
<section class="home-banner home-banner-3 with-radius">
    <img class="lazyload bg-img blur-up" src="https://taearifdev.com/assets/front/img/user/home_settings/67d16c0704ed7.jpg" alt="Banner">
    <div class="container">

        <div class="row align-items-center">
            <div class="col-xl-7 col-lg-7">
                <div class="content mb-40" data-aos="fade-up">
                    <h1 class="title color-white">{{ $heroStatic?->title }}</h1>
                    <p class="text color-white m-0">
                        {{ $heroStatic?->subtitle }}
                    </p>
                </div>
            </div>
            <div class="col-xl-5 col-lg-5">
                <div class="filter-form mb-40" data-aos="fade-up">
                    <div class="tabs-navigation">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rent" type="button">{{ $keywords['Rent'] ?? __('Rent') }}</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale" type="button">{{ $keywords['Sale'] ?? __('Sale') }}</button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <input type="hidden" id="currency_symbol" value="{{ $userBs->base_currency_symbol }}">
                        <input type="hidden" name="min" value="{{ $min }}" id="min">
                        <input type="hidden" name="max" value="{{ $max }}" id="max">

                        <input class="form-control" type="hidden" value="{{ $min }}" id="o_min">
                        <input class="form-control" type="hidden" value="{{ $max }}" id="o_max">
                        <div class="tab-pane fade show active" id="rent">
                            <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                <div class="row">
                                    <input type="hidden" name="purposre" value="rent">
                                    <input type="hidden" name="min" value="{{ $min }}" id="min1">
                                    <input type="hidden" name="max" value="{{ $max }}" id="max1">
                                    <div class="col-lg-12 col-md-6">
                                        <div class="form-group mb-20">
                                            <input type="text" id="search1" name="location" class="form-control" placeholder="{{ $keywords['Location'] ?? __('Location') }}">

                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mb-20">
                                            <select aria-label="#" name="type" class="form-control select2 type" id="type">
                                                <option selected disabled>
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
                                    <div class="  col-sm-6">
                                        <div class="form-group mb-20">
                                            <select aria-label="#" class="form-control select2 bringCategory" id="category" name="category">
                                                <option selected disabled>
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

                                    <div class="form-group city mb-20">

                                        <select aria-label="#" name="city" class="form-control select2 city_id" id="city">
                                            <option disabled selected>
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

                                    <div class="col-lg-12 col-md-6">
                                        <div class="form-group mb-20">
                                            <div class="form-control price-slider">
                                                <div data-range-slider="filterPriceSlider"></div>
                                                <span data-range-value="filterPriceSliderValue" class="w-60">{{ formatNumber($min) }}
                                                    -
                                                    {{ formatNumber($max) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-6 text-center">
                                        <button type="submit" class="btn btn-lg btn-primary icon-start">
                                            <i class="fal fa-search"></i>
                                            {{ $keywords['Search'] ?? __('Search') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="sale">
                            <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                <div class="row">
                                    <input type="hidden" name="purposre" value="sale">
                                    <input type="hidden" name="min" value="{{ $min }}" id="min2">
                                    <input type="hidden" name="max" value="{{ $max }}" id="max2">
                                    <div class="col-lg-12 col-md-6">
                                        <div class="form-group mb-20">
                                            <input type="text" id="search1" name="location" class="form-control" placeholder="{{ $keywords['Location'] ?? __('Location') }}">

                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group mb-20">
                                            <select aria-label="#" name="type" class="form-control select2 type" id="type1">
                                                <option selected disabled>
                                                    {{ $keywords['Select Property'] ?? __('Select Property') }}
                                                </option>
                                                <option selected value="all">
                                                    {{ $keywords['All'] ?? __('All') }}
                                                </option>
                                                <option value="residential">
                                                    {{ $keywords['Residential'] ?? __('Residential') }}
                                                </option>
                                                <option value="commercial">
                                                    {{ $keywords['Commercial'] ?? __('Commercial') }}
                                                </option>

                                            </select>
                                        </div>
                                    </div>
                                    <div class="  col-sm-6">
                                        <div class="form-group mb-20">
                                            <select aria-label="#" class="form-control select2 bringCategory" id="category1" name="category">
                                                <option selected disabled>
                                                    {{ $keywords['Select Category'] ?? __('Select Category') }}
                                                </option>
                                                <option value="all">{{ $keywords['All'] ?? __('All') }}
                                                </option>
                                                @foreach ($all_proeprty_categories as $category)
                                                <option value="{{ $category->slug }}">
                                                    {{ $category->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group city mb-20">

                                        <select aria-label="#" name="city" class="form-control select2 city_id" id="city1">
                                            <option disabled selected>
                                                {{ $keywords['Select City'] ?? __('Select City') }}
                                            </option>
                                            <option selected value="all">{{ $keywords['All'] ?? __('All') }}
                                            </option>
                                            @foreach ($all_cities as $city)
                                            <option data-id="{{ $city->id }}" value="{{ $city->name }}">
                                                {{ $city->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-lg-12 col-md-6">
                                        <div class="form-group mb-20">
                                            <div class="form-control price-slider">
                                                <div data-range-slider="filterPriceSlider2"></div>
                                                <span data-range-value="filterPriceSlider2Value" class="w-60">{{ formatNumber($min) }}
                                                    -
                                                    {{ formatNumber($max) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-6 text-center">
                                        <button type="submit" class="btn btn-lg btn-primary icon-start">
                                            <i class="fal fa-search"></i>
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

    </div>
</section>
@endif

@if ($home_sections->brand_section == 1)
@if(count($brands) > 0)
<div class="sponsor ptb-100" data-aos="fade-up">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="swiper sponsor-slider">
                    <div class="swiper-wrapper">
                        @forelse ($brands as $brand)
                        <div class="swiper-slide">
                            <div class="item-single d-flex justify-content-center" data-aos="fade-up">
                                <div class="sponsor-img">
                                    <a href="{{ $brand->brand_url }}" target="_blank">
                                        <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}" alt="Sponsor">
                                    </a>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-3 text-center w-100">
                            <h3 class="mb-0"> {{ $keywords['No Brands Found'] ?? __('No Brands Found') }}
                            </h3>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination position-static mt-30" id="sponsor-slider-pagination"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endif

@if ($home_sections->category_section == 1)
@if(count($property_categories) > 0)
<section class="category category-2 pb-100">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-center mb-40" data-aos="fade-up">
                    <span class="subtitle">{{ $home_text?->category_section_title }}</span>
                    <h2 class="title">{{ $home_text?->category_section_subtitle }}</h2>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="swiper" id="category-slider-2">
                    <div class="swiper-wrapper">
                        @forelse ($property_categories as $category)
                        <div class="swiper-slide color-1">
                            <a href="{{ route('front.user.properties', [getParam(), 'category' => $category->categoryContent?->slug]) }}">
                                <div class="category-item radius-md text-center">
                                    <div class="category-icons">
                                        <img src="{{ asset('assets/img/property-category/' . $category->image) }}" alt="">
                                    </div>
                                    <span class="category-title d-block mt-3 m-0 color-medium">
                                        {{ $category->name }}</span>
                                </div>
                            </a>
                        </div>
                        @empty
                        <div class="p-3 text-center w-100">
                            <h3 class="mb-0">
                                {{ $keywords['No Categories Found'] ?? __('No Categories Found') }}
                            </h3>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination position-static mt-30" id="category-slider-2-pagination"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="shape">
        <img class="shape-1" src="{{ asset('assets/front/user/realestate/shape/shape-1.png') }}" alt="Shape">
        <img class="shape-2" src="{{ asset('assets/front/user/realestate/shape/shape-2.png') }}" alt="Shape">
        <img class="shape-3" src="{{ asset('assets/front/user/realestate/shape/shape-3.png') }}" alt="Shape">
        <img class="shape-4" src="{{ asset('assets/front/user/realestate/shape/shape-4.png') }}" alt="Shape">
        <img class="shape-5" src="{{ asset('assets/front/user/realestate/shape/shape-10.png') }}" alt="Shape">
    </div>
</section>
@endif
@endif

@if ($home_sections->property_section == 1)
@if(count($properties) > 0)
<section class="product-area popular-product pb-70">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-inline mb-10" data-aos="fade-up">
                    <h2 class="title mb-20">{{ $home_text?->property_title }}</h2>
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
                        <div class="p-3 text-center mb-30 w-100">
                            <h3 class="mb-0">
                                {{ $keywords['No Properties Found'] ?? __('No Properties Found') }}
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


@if ($home_sections->intro_section == 1)
<section class="about-area about-2 pb-70">
    <div class="container">
        <div class="row align-items-center gx-xl-5">
            <div class="col-lg-5">
                <div class="content mb-30" data-aos="fade-up">
                    <div class="content-title">
                        <span class="subtitle">{{ $home_text?->about_title }}</span>
                        <h2>{{ $home_text?->about_subtitle }}</h2>
                    </div>
                    <div class="text summernote-content">{!! $home_text?->about_content !!}</div>

                    <div class="d-flex align-items-center flex-wrap gap-15">
                        @if (!empty($home_text->about_button_url))
                        <a href="{{ $home_text->about_button_url }}" class="btn btn-lg btn-primary">{{ $home_text?->about_button_text }}</a>
                        @endif
                        {{-- @if (!empty($aboutInfo->client_text))
                                    <div class="clients">
                                        <span class="color-primary">{{ $aboutInfo?->client_text }}</span>
                        <div class="client-img mt-1">
                            <img src="{{ asset('assets/front/') }}/images/client/client-1.jpg">
                            <img src="{{ asset('assets/front/') }}/images/client/client-2.jpg">
                            <img src="{{ asset('assets/front/') }}/images/client/client-3.jpg">
                            <img src="{{ asset('assets/front/') }}/images/client/client-4.jpg">
                        </div>
                    </div>
                    @endif --}}
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="img-content img-right mb-30" data-aos="fade-up">
                <div class="img-1">
                    @if (!empty($home_text->about_image))
                    <img class="lazyload blur-up" src="{{ asset('assets/front/images/placeholder.png') }}" data-src="{{ asset('assets/front/img/user/home_settings/' . $home_text->about_image) }}" alt="Image">
                    @endif
                </div>
                <div class="img-2">
                    @if (!empty($home_text->about_image_two))
                    <img class="lazyload blur-up" src="{{ asset('assets/front/images/placeholder.png') }}" data-src="{{ asset('assets/front/img/user/home_settings/' . $home_text->about_image_two) }}" alt="Image">
                    @endif
                    @if (!empty($home_text->about_video_url))
                    <a href="{{ $home_text->about_video_url }}" class="video-btn youtube-popup p-absolute">
                        <i class="fas fa-play"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Bg shape -->
    <div class="shape">
        <img class="shape-1" src="{{ asset('assets/front/user/realestate/shape/shape-2.png') }}" alt="Shape">
        <img class="shape-2" src="{{ asset('assets/front/user/realestate/shape/shape-9.png') }}" alt="Shape">
        <img class="shape-3" src="{{ asset('assets/front/user/realestate/shape/shape-8.png') }}" alt="Shape">
        <img class="shape-4" src="{{ asset('assets/front/user/realestate/shape/shape-3.png') }}" alt="Shape">
    </div>
</section>
@endif

@if ($home_sections->work_process_section == 1)
@if(count($work_processes) > 0)
<section class="work-process work-process-2 pb-70">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-center mb-40" data-aos="fade-up">
                    <span class="subtitle">{{ $home_text?->work_process_section_title }}</span>
                    <h2 class="title">{{ $home_text?->work_process_section_subtitle }}</h2>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="row gx-xl-5">
                    @forelse ($work_processes as $process)
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card mb-30 color-1">
                            <div class="card-content border text-center">
                                <div class="card-step h3 lh-1"><span>{{ $loop->iteration }}</span></div>
                                <div class="card-icon">
                                    <i class="{{ $process->icon }}"></i>
                                </div>
                                <h3 class="card-title">{{ $process->title }}</h3>
                                <p class="card-text m-0">{{ $process->text }}</p>
                            </div>
                            <span class="line line-top"></span>
                            <span class="line line-right"></span>
                            <span class="line line-bottom"></span>
                        </div>
                    </div>
                    @empty
                    <div class="p-3 text-center mb-30 w-100">
                        <h3 class="mb-0">
                            {{ $keywords['No Work Process Found'] ?? __('No Work Process Found') }}
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


@if ($home_sections->counter_info_section == 1)
@if(count($counterInformations) > 0)
<div class="counter-area with-radius border pt-100 pb-70">
    <img class="lazyload bg-img blur-up" src="{{ asset('assets/front/images/2567u56gy855.png') }}" alt="Image">
    <div class="container">
        <div class="row gx-xl-5">
            @forelse ($counterInformations as $counter)
            <div class="col-sm-6 col-lg-3" data-aos="fade-up">
                <div class="card mb-30">
                    <div class="d-flex align-items-center justify-content-center mb-10">
                        <div class="card-icon me-2 color-primary"><i class="{{ $counter->icon }}"></i></div>
                        <h2 class="m-0 color-primary"><span class="counter">{{ $counter->amount }}</span>+
                        </h2>
                    </div>
                    <p class="card-text text-center">{{ $counter->title }}</p>
                </div>
            </div>
            @empty
            <div class="p-3 text-center mb-30 w-100">
                <h3 class="mb-0">
                    {{ $keywords['No Counter Information Found'] ?? __('No Counter Information Found') }}
                </h3>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endif
@endif


@if ($home_sections->project_section == 1)
@if(count($projects) > 0)
<section class="projects-area pt-100 pb-70">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title title-center mb-40" data-aos="fade-up">
                    <span class="subtitle">{{ $home_text?->project_title }}</span>
                    <h2 class="title mb-20">{{ $home_text?->project_title }}</h2>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="row">
                    @forelse ($projects as $project)
                    <div class="col-lg-4 col-md-6 mb-30">
                        <a href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                            <div class="card product-default">
                                <div class="card-img">
                                    <img src="{{ asset('assets/img/project/featured/' . $project->featured_image) }}" alt="Product">
                                    <span class="label">
                                        {{ $project->complete_status == 1 ? $keywords['Complete'] ?? __('Complete') : $keywords['Under Construction'] ?? __('Under Construction') }}
                                    </span>
                                </div>
                                <div class="card-text product-title text-center p-3">
                                    <h3 class="card-title product-title color-white mb-1">
                                        {{ @$project->title }}

                                    </h3>
                                    <span class="location icon-start"><i class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>
                                    <span class="price">{{ formatNumber($project->min_price) }}
                                        {{ !empty($project->max_price) ? ' - ' . formatNumber($project->max_price) : '' }}</span>
                                    @if ($project->user)
                                    <a class="color-medium" {{-- href="{{ route('frontend.agent.details', ['username' => $project->agent->username]) }}" --}} target="_self">
                                        <div class="user rounded-pill mt-10">
                                            <div class="user-img lazy-container ratio ratio-1-1 rounded-pill">

                                                <img class="lazyload" data-src="{{ $property->user->photo ? asset('assets/front/img/user/' . $property->user->photo) : asset('assets/img/user-profile.jpg') }}" src="{{ $property->user->photo ? asset('assets/front/img/user/' . $property->user->photo) : asset('assets/img/user-profile.jpg') }}">

                                            </div>
                                            <div class="user-info">
                                                <span>{{ $project->user->username }}</span>
                                            </div>
                                        </div>
                                    </a>
                                    @endif
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


@if ($home_sections->testimonials_section == 1)
@if(count($testimonials) > 0)
<section class="testimonial-area testimonial-3 pb-100">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="section-title title-center mb-40" data-aos="fade-up">
                    <span class="subtitle">{{ $home_text?->testimonial_title }}</span>
                    <h2 class="title">{{ $home_text?->testimonial_subtitle }}</h2>
                </div>
            </div>
            <div class="col-12" data-aos="fade-up">
                <div class="swiper" id="testimonial-slider-3">
                    <div class="swiper-wrapper">
                        @forelse ($testimonials as $testimonial)
                        <div class="swiper-slide pb-30">
                            <div class="slider-item">
                                <div class="client-content">
                                    <div class="quote">
                                        <span class="icon"><i class="fas fa-quote-left"></i></span>
                                        <p class="text m-0">{{ $testimonial->content }}
                                        </p>
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
                                            <h6 class="name mb-0 lh-1">{{ $testimonial->name }}</h6>
                                            <span class="designation">{{ $testimonial->occupation }}</span>
                                            {{-- <div class="ratings">
                                                            <div class="rate">
                                                                <div class="rating-icon"
                                                                    style="width: {{ $testimonial->rating * 20 }}%">
                                        </div>
                                    </div>
                                    <span class="ratings-total">({{ $testimonial->rating }})
                                    </span>
                                </div> --}}
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
        <div class="swiper-pagination position-static text-center" id="testimonial-slider-3-pagination"></div>
    </div>
    </div>
    </div>
    </div>
    <div class="shape">
        <img class="shape-1" src="{{ asset('assets/front/user/realestate/shape/shape-10.png') }}" alt="Shape">
        <img class="shape-2" src="{{ asset('assets/front/user/realestate/shape/shape-6.png') }}" alt="Shape">
        <img class="shape-3" src="{{ asset('assets/front/user/realestate/shape/shape-3.png') }}" alt="Shape">
        <img class="shape-4" src="{{ asset('assets/front/user/realestate/shape/shape-5.png') }}" alt="Shape">
        <img class="shape-5" src="{{ asset('assets/front/user/realestate/shape/shape-2.png') }}" alt="Shape">
    </div>
</section>
@endif
@endif

@if ($home_sections->brand_section == 1)
<div class="sponsor ptb-100" data-aos="fade-up">
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

@endsection
