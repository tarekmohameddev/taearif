@extends('user-front.realestate.layout')

@section('pageHeading', $keywords['Home'] ?? 'Home')

@section('metaDescription', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('metaKeywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')

@section('content')

    @if (!is_null($heroStatic))
        <section class="home-banner home-banner-1">
            <img class="lazyload bg-img" src="{{ asset('assets/front/img/hero_static/' . $heroStatic->img) }}">
            <div class="container">
                <div class="row justify-content-center text-center align-items-center">
                    <div class="col-xxl-5">
                        <div class="content" data-aos="fade-up">
                            <h1 class="title">{{ $heroStatic?->title }}</h1>
                            <p class="text text-white">
                                {{ $heroStatic?->subtitle }}
                            </p>
                        </div>
                        <div class="banner-filter-form d-none" data-aos="fade-up">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rent"
                                        type="button">{{ $keywords['Rent'] ?? __('Rent') }}</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale"
                                        type="button">{{ $keywords['Sale'] ?? __('Sale') }}</button>
                                </li>
                            </ul>
                            <div class="tab-content form-wrapper">
                                <input type="hidden" value="{{ $min }}" id="min">
                                <input type="hidden" value="{{ $max }}" id="max">

                                <input type="hidden" id="currency_symbol" value="{{ $userBs->base_currency_symbol }}">
                                <input class="form-control" type="hidden" value="{{ $min }}" id="o_min">
                                <input class="form-control" type="hidden" value="{{ $max }}" id="o_max">

                                <div class="tab-pane fade active show" id="rent">
                                    <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                        <input type="hidden" name="purposre" value="rent">
                                        <input type="hidden" name="min" value="{{ $min }}" id="min1">
                                        <input type="hidden" name="max" value="{{ $max }}" id="max1">
                                        <div class="grid">
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label
                                                        for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                                    <input type="text" id="search1" name="location"
                                                        class="form-control"
                                                        placeholder="{{ $keywords['Enter Location'] ?? __('Enter Location') }}">
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label for="type"
                                                        class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                                    <select aria-label="#" name="type" class="form-control select2 type"
                                                        id="type">
                                                        <option selected disabled value="">
                                                            {{ $keywords['Select Property'] ?? __('Select Property') }}
                                                        </option>
                                                        <option value="all">{{ $keywords['All'] ?? __('All') }}</option>
                                                        <option value="residential">
                                                            {{ $keywords['Residential'] ?? __('Residential') }}</option>
                                                        <option value="commercial">
                                                            {{ $keywords['Commercial'] ?? __('Commercial') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label for="category"
                                                        class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                                    <select aria-label="#" class="form-control select2 bringCategory"
                                                        id="category" name="category">
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
                                                    <label for="city"
                                                        class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                                    <select aria-label="#" name="city"
                                                        class="form-control select2 city_id" id="city">
                                                        <option selected disabled value="">
                                                            {{ $keywords['Select City'] ?? __('Select City') }}
                                                        </option>
                                                        <option value="all">{{ $keywords['All'] ?? __('All') }}</option>

                                                        @foreach ($all_cities as $city)
                                                            <option data-id="{{ $city->id }}"
                                                                value="{{ $city->name }}">
                                                                {{ $city->name }}</option>
                                                        @endforeach

                                                    </select>
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                                    <span
                                                        data-range-value="filterPriceSliderValue">{{ formatNumber($min) }}
                                                        -
                                                        {{ formatNumber($max) }}</span>
                                                </label>
                                                <div data-range-slider="filterPriceSlider"></div>
                                            </div>
                                            <div class="grid-item">
                                                <button type="submit"
                                                    class="btn btn-lg btn-primary bg-secondary icon-start w-100">
                                                    {{ $keywords['Search'] ?? __('Search') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="sale">
                                    <form action="{{ route('front.user.properties', getParam()) }}" method="get">
                                        <input type="hidden" name="purposre" value="sale">
                                        <input type="hidden" name="min" value="{{ $min }}"
                                            id="min2">
                                        <input type="hidden" name="max" value="{{ $max }}"
                                            id="max2">
                                        <div class="grid">
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label
                                                        for="search1">{{ $keywords['Location'] ?? __('Location') }}</label>
                                                    <input type="text" id="search1" name="location"
                                                        class="form-control"
                                                        placeholder="{{ $keywords['Enter Location'] ?? __('Enter Location') }}">
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label for="type1"
                                                        class="icon-end">{{ $keywords['Property Type'] ?? __('Property Type') }}</label>
                                                    <select aria-label="#" name="type"
                                                        class="form-control select2 type" id="type1">
                                                        <option selected disabled value="">
                                                            {{ $keywords['Select Property'] ?? __('Select Property') }}
                                                        </option>
                                                        <option value="all">{{ $keywords['All'] ?? __('All') }}
                                                        </option>
                                                        <option value="residential">
                                                            {{ $keywords['Residential'] ?? __('Residential') }}</option>
                                                        <option value="commercial">
                                                            {{ $keywords['Commercial'] ?? __('Commercial') }}</option>

                                                    </select>
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <div class="form-group">
                                                    <label for="category1"
                                                        class="icon-end">{{ $keywords['Categories'] ?? __('Categories') }}</label>
                                                    <select aria-label="#" class="form-control select2 bringCategory"
                                                        id="category1" name="category">
                                                        <option selected disabled value="">
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

                                            <div class="grid-item city">
                                                <div class="form-group">
                                                    <label for="city1"
                                                        class="icon-end">{{ $keywords['City'] ?? __('City') }}</label>
                                                    <select aria-label="#" name="city"
                                                        class="form-control select2 city_id" id="city1">
                                                        <option selected disabled value="">
                                                            {{ $keywords['Select City'] ?? __('Select City') }}
                                                        </option>
                                                        <option value="all">{{ $keywords['All'] ?? __('All') }}
                                                        </option>

                                                        @foreach ($all_cities as $city)
                                                            <option data-id="{{ $city->id }}"
                                                                value="{{ $city->name }}">
                                                                {{ $city->name }}</option>
                                                        @endforeach

                                                    </select>
                                                </div>
                                            </div>
                                            <div class="grid-item">
                                                <label class="price-value">{{ $keywords['Price'] ?? __('Price') }}: <br>
                                                    <span
                                                        data-range-value="filterPriceSlider2Value">{{ formatNumber($min) }}
                                                        -
                                                        {{ formatNumber($max) }}</span>
                                                </label>
                                                <div data-range-slider="filterPriceSlider2"></div>
                                            </div>
                                            <div class="grid-item">
                                                <button type="submit"
                                                    class="btn btn-lg btn-primary bg-secondary icon-start w-100">
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

    @if ($home_sections->counter_info_section == 1)
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
                        <div class="col-12">
                            <h3 class="text-center mt-20">
                                {{ $keywords['No Counter Information Found'] ?? __('No Counter Information Found') }} </h3>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    @endif

    @if ($home_sections->intro_section == 1)
        <section class="about-area pb-70 pt-30">
            <div class="container">
                <div class="row gx-xl-5">
                    <div class="col-lg-6"> about-areaabout-area
                        <div class="img-content" data-aos="fade-up">

                                @if (!empty($home_text->about_image))
                                    <img class="lazyload blur-up"
                                        data-src="{{ asset('assets/front/img/user/home_settings/' . $home_text->about_image) }}">
                                @endif

                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content mb-30" data-aos="fade-up">
                            <div class="content-title">
                                <span class="subtitle"><span class="line"></span>
                                    {{ $home_text?->about_title }}</span>
                                <h2>{{ $home_text?->about_subtitle }}</h2>
                            </div>
                            <div class="text summernote-content">{!! $home_text?->about_content !!}</div>

                            <div class="d-flex align-items-center flex-wrap gap-15">
                                @if (!empty($home_text->about_button_url))
                                    <a href="{{ $home_text->about_button_url }}"
                                        class="btn btn-lg btn-primary bg-secondary">{{ $home_text->about_button_text }}</a>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </section>
    @endif


    <!-- skills -->

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
                <div class="col-12">
                    <h3 class="text-center mt-20">{{ $keywords['No Skill Found'] ?? __('No Skill Found') }}</h3>
                </div>
            @endforelse
        </div>
    </div>
</section>


    <!--// skills -->

    @if ($home_sections->featured_properties_section == 1)
        <section class="product-area featured-product pb-70">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section-title title-inline mb-40" data-aos="fade-up">
                            <h2 class="title">{{ $home_text?->featured_property_title }}</h2>
                        </div>
                    </div>
                    <div class="col-12" data-aos="fade-up">
                        <div class="swiper product-slider">
                            <div class="swiper-wrapper">
                                @forelse ($featured_properties as $property)
                                    <div class="swiper-slide">
                                        @include('user-front.realestate.partials.property')
                                    </div>
                                @empty
                                    <div class=" p-3 text-center mb-30 w-100">
                                        <h3 class="mb-0">
                                            {{ $keywords['No Featured Property Found'] ?? __('No Featured Property Found') }}
                                        </h3>
                                    </div>
                                @endforelse
                            </div>
                            <!-- Slider pagination -->
                            <div class="swiper-pagination position-static mb-30" id="product-slider-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif



    @if ($home_sections->why_choose_us_section == 1)
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

    @if ($home_sections->project_section == 1)
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
                                                <img src="{{ asset('assets/img/project/featured/' . $project->featured_image) }}"
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

    @if ($home_sections->testimonials_section == 1)
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
                                    <div class="bg-light p-3 text-center mb-30 w-100">
                                        <h3 class="mb-0">
                                            {{ $keywords['No Testimonials Found'] ?? __('No Testimonials Found') }}</h3>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
@endsection
