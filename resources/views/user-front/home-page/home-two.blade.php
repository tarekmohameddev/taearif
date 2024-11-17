@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Home'] ?? 'Home' }}
@endsection
@php
    Config::set('app.timezone', $userBs->timezoneinfo->timezone);
@endphp
@section('meta-description', !empty($userSeo) ? $userSeo->home_meta_description : '')
@section('meta-keywords', !empty($userSeo) ? $userSeo->home_meta_keywords : '')

@section('content')
    <!--====== Banner part start ======-->
    <section class="banner-section banner-section-two">
        <div class="banner-slider" id="bannerSlider">
            @if (count($sliders) > 0)
                @foreach ($sliders as $slider)
                    <div>
                        <div class="single-banner"
                            style="background-image: url({{ asset('assets/front/img/hero_slider/' . $slider->img) }});">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <div class="banner-content text-center">
                                            <h1 data-animation="fadeInDown" data-delay="0.8s">
                                                {{ convertUtf8($slider->title) }}
                                            </h1>
                                            <p data-animation="fadeInUp" data-delay="1s">
                                                {{ convertUtf8($slider->subtitle) }}
                                            </p>
                                            <ul class="btn-wrap">
                                                <li data-animation="fadeInLeft" data-delay="1.2s">
                                                    <a href="{{ $slider->btn_url }}"
                                                        class="main-btn main-btn-4">{{ convertUtf8($slider->btn_name) }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div>
                    <div class="single-banner"
                        style="background-image: url({{ asset('assets/front/img/hero_slider/hero_bg_2.jpg') }});">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-10">
                                    <div class="banner-content text-center">
                                        <h1 data-animation="fadeInDown" data-delay="0.8s">Take Great Idea To Grow Your
                                            Business
                                        </h1>
                                        <p data-animation="fadeInUp" data-delay="1s">
                                            Sedut perspiciatis unde omnis iste natus error sit voluptatem accusantium
                                            doloremque
                                            laudanti totam raperiaeaque ipsa quaeab
                                        </p>
                                        <ul class="btn-wrap">
                                            <li data-animation="fadeInLeft" data-delay="1.2s">
                                                <a href="#" class="main-btn main-btn-4">Our Services</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--====== Banner part end ======-->

    <!--====== About Part start ======-->
    @if (isset($home_sections->intro_section) && $home_sections->intro_section == 1)
        <section class="about-section section-gap">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-10">
                        <div class="about-video">
                            @php
                                $aboutBg = $home_text->about_image ?? 'about2.jpg';
                            @endphp
                            <div class="video-poster-one wow fadeInUp lazy" data-wow-duration="1500ms"
                                data-wow-delay="400ms"
                                data-bg="{{ asset('assets/front/img/user/home_settings/' . $aboutBg) }}">
                            </div>
                            <div class="video-poster-two wow fadeInUp lazy" data-wow-duration="1500ms"
                                data-wow-delay="600ms"
                                data-bg="{{ asset(isset($home_text->about_video_image) ? asset('assets/front/img/user/home_settings/' . $home_text->about_video_image) : 'assets/front/user/img/video-bg/poster-2.jpg') }}">
                                @if (!empty($home_text->about_video_url))
                                    <a href="{{ $home_text->about_video_url }}" class="video-play-icon popup-video">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-10">
                        <div class="about-text-two">
                            <div class="section-title left-border mb-40">
                                @if (!empty($home_text->about_title))
                                    <span class="title-tag">{{ $home_text->about_title }}</span>
                                @endif
                                <h2 class="title">{{ $home_text->about_subtitle ?? null }}</h2>
                            </div>
                            @if (!empty($home_text))
                                <p class="mb-4">{!! nl2br($home_text->about_content) ?? '' !!}</p>
                            @endif
                            @if (!empty($home_text->about_button_url))
                                <a href="{{ $home_text->about_button_url }}"
                                    class="main-btn">{{ $home_text->about_button_text }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!--====== About Part end ======-->
    <!--====== Service Part Start ======-->
    @if (in_array('Service', $packagePermissions) &&
            isset($home_sections->featured_services_section) &&
            $home_sections->featured_services_section == 1)
        <section class="service-section shape-style-two service-line-shape section-gap grey-bg">
            <div class="container">
                <!-- Section Title -->
                <div class="section-title text-center both-border mb-50">
                    @if (!empty($home_text->service_title))
                        <span class="title-tag">{{ $home_text->service_title }}</span>
                    @endif
                    <h2 class="title">{{ $home_text->service_subtitle ?? null }}</h2>
                </div>
                <!-- Services Boxes -->
                <div class="row service-boxes justify-content-center">
                    @foreach ($services as $key => $service)
                        <div class="col-lg-3 col-sm-6 col-10 wow fadeInUp" data-wow-duration="1500ms"
                            data-wow-delay="{{ $key * 100 + 200 }}ms">
                            <div class="service-box-two text-center">
                                <a class="icon"
                                    @if ($service->detail_page == 1) href="{{ route('front.user.service.detail', [getParam(), 'slug' => $service->slug, 'id' => $service->id]) }}" @endif>
                                    <img class="lazy"
                                        data-src="{{ isset($service->image) ? asset('assets/front/img/user/services/' . $service->image) : asset('assets/front/img/profile/service-1.jpg') }}"
                                        alt="Icon">
                                </a>
                                <h3>
                                    <a
                                        @if ($service->detail_page == 1) href="{{ route('front.user.service.detail', [getParam(), 'slug' => $service->slug, 'id' => $service->id]) }}" @endif>{{ $service->name }}</a>
                                </h3>
                                @if ($service->detail_page == 1)
                                    <a href="{{ route('front.user.service.detail', [getParam(), 'slug' => $service->slug, 'id' => $service->id]) }}"
                                        class="service-link">
                                        <i class="fal fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="line-one">
                <img src="{{ asset('assets/front/user/img/lines/12.png') }}" alt="line-shape">
            </div>
            <div class="line-two">
                <img src="{{ asset('assets/front/user/img/lines/11.png') }}" alt="line-shape">
            </div>
        </section>
    @endif
    <!--====== Service Part End ======-->

    <!--====== Project section Start ======-->
    @if (in_array('Portfolio', $packagePermissions) &&
            isset($home_sections->portfolio_section) &&
            $home_sections->portfolio_section == 1)
        <section class="project-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-7 col-md-8">
                        <!-- Section Title -->
                        <div class="section-title left-border">
                            @if (!empty($home_text->portfolio_title))
                                <span class="title-tag">{{ $home_text->portfolio_title }}</span>
                            @endif
                            <h2 class="title">{{ $home_text->portfolio_subtitle ?? null }}</h2>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-4">
                        <div class="view-moore-btn text-md-right mt-30 mt-md-0">
                            <a href="{{ route('front.user.portfolios', getParam()) }}"
                                class="main-btn">{{ $home_text->view_all_portfolio_text ?? 'View All' }}</a>
                        </div>
                    </div>
                </div>

                <!-- Project Boxes -->
                <div class="row project-boxes mt-80 justify-content-center">
                    @foreach ($portfolios as $portfolio)
                        <div class="col-lg-4 col-sm-6">
                            <div class="project-box">
                                <a class="project-thumb d-block"
                                    href="{{ route('front.user.portfolio.detail', [getParam(), $portfolio->slug, $portfolio->id]) }}">
                                    <div class="thumb bg-img-c lazy"
                                        data-bg="{{ asset('assets/front/img/user/portfolios/' . $portfolio->image) }}">
                                    </div>
                                </a>
                                <div class="project-desc text-center">
                                    <h4>
                                        <a
                                            href="{{ route('front.user.portfolio.detail', [getParam(), $portfolio->slug, $portfolio->id]) }}">{{ strlen($portfolio->title) > 25 ? mb_substr($portfolio->title, 0, 25, 'UTF-8') . '...' : $portfolio->title }}</a>
                                    </h4>
                                    <p>{{ $portfolio->bcategory->name }}</p>
                                    <a href="{{ route('front.user.portfolio.detail', [getParam(), $portfolio->slug, $portfolio->id]) }}"
                                        class="project-link">
                                        <i class="fal fa-long-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!--====== Project section End ======-->

    <!--====== Fact Part Start ======-->
    @if (isset($home_sections->counter_info_section) && $home_sections->counter_info_section == 1)
        <section class="fact-section-two">
            <div class="container">
                <div class="fact-two-inner">
                    <div class="fact-boxes row justify-content-between align-items-center">
                        @foreach ($counterInformations as $counterInformation)
                            <div class="col-lg-3 col-6">
                                <div class="fact-box fact-box-two text-center mb-40">
                                    <div class="icon">
                                        <i class="{{ $counterInformation->icon }}"></i>
                                    </div>
                                    <h2 class="counter">{{ $counterInformation->count }}</h2>
                                    <p class="title">{{ convertUtf8($counterInformation->title) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!--====== Fact Part End ======-->

    <!--====== Working Process Part Start ======-->
    @if (isset($home_sections->work_process_section) && $home_sections->work_process_section == 1)
        <section class="working-process-section grey-bg">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-6 col-md-10 order-lg-1 order-2">
                        <div class="process-text">
                            <!-- Section Title -->
                            <div class="section-title left-border mb-30">
                                @if (!empty($home_text->work_process_section_title))
                                    <span class="title-tag">{{ $home_text->work_process_section_title }}</span>
                                @endif
                                <h2 class="title">{{ $home_text->work_process_section_subtitle ?? null }}</h2>
                            </div>
                            @if (!empty($home_text->work_process_section_text))
                                <p>{!! nl2br($home_text->work_process_section_text) ?? '' !!}
                                </p>
                            @endif
                            <!-- process-loop -->
                            <div class="process-loop">
                                @foreach ($work_processes as $key => $work_process)
                                    <div class="single-process wow fadeInUp" data-wow-duration="1500ms"
                                        data-wow-delay="400ms">
                                        <div class="icon">
                                            <i class="{{ $work_process->icon }}"></i>
                                            <span>{{ $key + 1 < 10 ? '0' . ($key + 1) : $key + 1 }}</span>
                                        </div>
                                        <div class="content">
                                            <h4>{{ $work_process->title }}</h4>
                                            @if (!empty($work_process->text))
                                                <p>{!! nl2br($work_process->text) !!}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-10 order-lg-2 order-1">
                        @php
                            $workbg = $home_text->work_process_section_img ?? 'work_process_bg.jpg';
                            $workVidBg = $home_text->work_process_section_video_img ?? 'work_process_video_bg.jpg';
                        @endphp
                        <div class="process-video bg-img-c lazy"
                            data-bg="{{ asset('assets/front/img/work_process/' . $workbg) }}">
                            <div class="video bg-img-c wow fadeInRight lazy" data-wow-duration="1500ms"
                                data-wow-delay="400ms"
                                data-bg="{{ asset('assets/front/img/work_process/' . $workVidBg) }}">
                                @if (!empty($home_text->work_process_section_video_url))
                                    <a class="paly-icon popup-video"
                                        href="{{ $home_text->work_process_section_video_url }}">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="working-circle"></div>
        </section>
    @endif
    <!--====== Working Process Part End ======-->

    <!--====== Video Start ======-->
    @if (isset($home_sections->video_section) && $home_sections->video_section == 1)
        @php
            $videoBg = $videoSectionDetails->video_section_image ?? 'video_bg.jpg';
        @endphp
        <section class="video-section-two bg-img-c lazy"
            data-bg="{{ asset('assets/front/img/user/home_settings/' . $videoBg) }}">
            <div class="container">
                <div class="row align-content-center justify-content-center">
                    <div class="col-lg-10">
                        <div class="video-cont text-center">
                            @if (!empty($videoSectionDetails->video_section_url))
                                <a href="{{ $videoSectionDetails->video_section_url }}"
                                    class="play-btn popup-video wow fadeInDown" data-wow-duration="1500ms"
                                    data-wow-delay="400ms"><i class="fas fa-play"></i></a>
                            @endif
                            <h2>
                                {{ $videoSectionDetails->video_section_title ?? null }}
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="line-shape-one">
                <img class="lazy" data-src="{{ asset('assets/front/user/img/lines/12.png') }}" alt="Line">
            </div>
            <div class="line-shape-two">
                <img class="lazy" data-src="{{ asset('assets/front/user/img/lines/11.png') }}" alt="Line">
            </div>
        </section>
    @endif
    <!--====== Video end ======-->

    <!--====== Testimonials part start ======-->
    @if (isset($home_sections->testimonials_section) && $home_sections->testimonials_section == 1)
        <section class="testimonial-section-two section-gap">
            <div class="container">
                <div class="row justify-content-center no-gutters">
                    <div class="col-lg-9">
                        <div class="section-title both-border text-center mb-80">
                            @if (!empty($home_text->testimonial_title))
                                <span class="title-tag">{{ $home_text->testimonial_title }}</span>
                            @endif
                            <h2 class="title">{{ $home_text->testimonial_subtitle ?? null }}</h2>
                        </div>
                        @if (count($testimonials) > 0)
                            <div class="testimonial-items" id="testimonialSliderTwo">
                                @foreach ($testimonials as $testimonial)
                                    <div class="testimonial-item"
                                        data-thumb="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}">
                                        <div class="author-img">
                                            <img class="lazy"
                                                data-src="{{ asset('assets/front/img/user/testimonials/' . $testimonial->image) }}"
                                                alt="Image">
                                        </div>
                                        <div class="content">
                                            <p>
                                                <span class="quote-top">
                                                    <i class="fas fa-quote-left"></i>
                                                </span>
                                                {{ replaceBaseUrl($testimonial->content) }}
                                                <span class="quote-bottom">
                                                    <i class="fas fa-quote-right"></i>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="author-name">
                                            <h4>{{ $testimonial->name }}</h4>
                                            <span>{{ $testimonial->occupation ?? null }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="testimonial-dots"></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="testimonial-quote-icon">
                <img class="lazy" data-src="{{ asset('assets/front/img/user/home_settings/quote.png') }}"
                    alt="quote">
            </div>
        </section>
    @endif
    <!--====== Testimonials part end ======-->

    @if (in_array('Blog', $packagePermissions) && isset($home_sections->blogs_section) && $home_sections->blogs_section == 1)
        <!--====== Latest Post Start ======-->
        <section class="latest-post-section section-gap-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="section-title left-border">
                            @if (!empty($home_text->blog_title))
                                <span class="title-tag">{{ $home_text->blog_title }}</span>
                            @endif
                            <h2 class="title">{{ $home_text->blog_subtitle ?? null }}</h2>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-4">
                        <div class="text-md-right mt-30 mt-md-0">
                            <a href="{{ route('front.user.blogs', getParam()) }}"
                                class="main-btn">{{ $home_text->view_all_blog_text ?? 'View All' }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid container-1600">
                <div class="latest-post-loop loop-two">
                    <div id="latestPostSlider">
                        @foreach ($blogs as $blog)
                            <div class="latest-post-box-two">
                                <div class="post-thumb-wrap">
                                    <a class="post-thumb bg-img-c d-block"
                                        href="{{ route('front.user.blog.detail', [getParam(), $blog->slug, $blog->id]) }}"
                                        style="background-image: url('{{ asset('assets/front/img/user/blogs/' . $blog->image) }}')">
                                    </a>
                                    <span class="post-date"><i
                                            class="far fa-calendar-alt"></i>{{ \Carbon\Carbon::parse($blog->created_at)->toFormattedDateString() }}</span>
                                </div>
                                <div class="post-desc">
                                    <h3 class="title">
                                        <a
                                            href="{{ route('front.user.blog.detail', [getParam(), $blog->slug, $blog->id]) }}">
                                            {{ $blog->title }}
                                        </a>
                                    </h3>
                                    <a href="{{ route('front.user.blog.detail', [getParam(), $blog->slug, $blog->id]) }}"
                                        class="post-link">
                                        {{ $keywords['Learn_More'] ?? 'Learn More' }} <i
                                            class="far fa-long-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!--====== Latest Post Start ======-->

    <!--====== Client Area Start ======-->
    @if (isset($home_sections->brand_section) && $home_sections->brand_section == 1)
        <section class="client-section">
            <div class="container">
                <div class="client-slider section-gap">
                    <div class="row align-items-center justify-content-between" id="clientSlider">
                        @foreach ($brands as $brand)
                            <div class="col">
                                <a href="{{ $brand->brand_url }}" class="client-img d-block text-center"
                                    target="_blank">
                                    <img class="lazy"
                                        data-src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                        alt="">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!--====== Client Area End ======-->
@endsection
