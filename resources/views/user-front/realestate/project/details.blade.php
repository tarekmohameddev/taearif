@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')
@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))
    {{-- @extends('user-front.realestate.layout') --}}

    @section('pageHeading', $project->title)



    @section('metaKeywords', !empty($project) ? $project->meta_keyword : '')
    @section('metaDescription', !empty($project) ? $project->meta_description : '')

    @section('og:tag')
        <meta property="og:title" content="{{ $project->title }}">
        <meta property="og:image" content="{{ asset('assets/img/project/featured/' . $project->featured_image) }}">
        <meta property="og:url" content="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
    @endsection
@else
    {{-- @extends('user-front.layout') --}}

    @section('tab-title')
        {{ $project->title }}
    @endsection

    @section('meta-description', !empty($project) ? $project->meta_description : '')
    @section('meta-keywords', !empty($project) ? $project->meta_keyword : '')

    @section('page-name')
        {{ $project->title }}
    @endsection
    @section('br-name')
        {{ $keywords['project_details'] ?? 'Project Details' }}
    @endsection

    @section('styles')
        <link rel="stylesheet" href="{{ asset('assets/front/fonts/icomoon/style.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/vendors/aos.min.css') }} ">
        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/partials.css') }}">
        @if ($userCurrentLang->rtl == 1)
            <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/rtl.css') }}">
        @endif
        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/responsive.css') }}">

    @endsection
    @section('scripts')
        <script src="{{ asset('assets/front/user/realestate/js/vendors/aos.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/vendors/masonry.pkgd.js') }}"></script>
        <script>
            'use-strict'
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
                   Masonry gallery
               ============================================*/
            var $grid = $('.masonry-gallery.grid').masonry({
                itemSelector: '.grid-item',
                percentPosition: true,
                columnWidth: '.grid-sizer'
            });
            // layout Masonry after each image loads
            $grid.imagesLoaded().progress(function() {
                $grid.masonry('layout');
            });
            $(".tabs-navigation .nav-link").on("click", function() {
                $grid.masonry('layout');
            });

            var getHeaderHeight = function() {
                var headerNext = $(".header-next");
                var header = headerNext.prev(".header-area");
                var headerHeight = header.height();

                headerNext.css({
                    "margin-top": headerHeight
                })
            }
            getHeaderHeight();

            $(window).on('resize', function() {
                getHeaderHeight();
            });


            /*============================================
                Image to background image
            ============================================*/
            $(".bg-img").parent().addClass('blur-up lazyload');

            $(".bg-img").each(function() {
                var el = $(this);
                var src = el.attr("src");
                var parent = el.parent();
                if (typeof src != 'undefined') {
                    parent.css({
                        "background-image": "url(" + src + ")",
                        "background-size": "cover",
                        "background-position": "center",
                        "display": "block"
                    });
                }

                el.hide();
            });
        </script>
    @endsection
@endif


@section('content')
    <!-- Page Title Start-->
    <div class="page-title-area  @if ($userBs->theme != 'home_eight') header-next @endif"
        @if ($userBs->theme == 'home_eight') style="padding-block: 200px !important;" @endif>
        <!-- Background Image -->
        <img class="lazyload blur-up bg-img" src="{{ asset('assets/front/img/user/' . $userBs->breadcrumb) }}">
        <div class="container">
            <div class="content text-center">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <h1 class="color-white">{{ $project->title }}</h1>
                        <p class="font-lg color-white mx-auto"> <span class="product-location icon-start"><i
                                    class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Title End-->

    <div class="divider">
        <div class="icon"><a href="#tapDown"><i class="fal fa-long-arrow-down"></i></a></div>
        <span class="line"></span>
    </div>

    <div class="projects-details-area pt-100 pb-70" id="tapDown">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="project-desc mb-40" data-aos="fade-up">
                        <h3 class="mb-20">{{ $keywords['Project Overview'] ?? __('Project Overview') }}</h3>
                        <p class="summernote-content">
                            {!! $project->description !!}
                        </p>

                    </div>
                    {{-- @if (!empty(showAd(3)))
                        <div class="text-center mb-3 mt-3">
                            {!! showAd(3) !!}
                        </div>
                    @endif --}}
                    <div class="">
                        <p>

                            <a class="btn btn-primary btn-md" href="#" data-bs-toggle="modal"
                                data-bs-target="#socialMediaModal">
                                <i class="far fa-share-alt"></i>
                                <span>{{ $keywords['Share'] ?? __('Share') }} </span>
                            </a>

                        </p>
                    </div>
                    <div class="pb-20"></div>
                    @if (count($project->specifications) > 0)
                        <div class="row" class="mb-20">
                            <div class="col-12">
                                <h3 class="mb-20">{{ $keywords['Features'] ?? __('Features') }}</h3>
                            </div>

                            @foreach ($project->specifications as $specification)
                                {{-- @php

                                    $project_specification_content = $specification->getContent($language->id);
                                @endphp --}}
                                <div class="col-lg-3 col-sm-6 col-md-4 mb-20">
                                    <strong
                                        class="mb-1 @if (!in_array($userBs->theme, ['home_five'])) text-dark @endif">{{ $specification?->label }}</strong>
                                    <br>
                                    <span>{{ $specification?->value }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="pb-20"></div>
                    @endif

                    <div class="pb-20"></div>

                    <div class="project-location mb-40" data-aos="fade-up">
                        <h3 class="mb-20"> {{ $keywords['Location'] ?? __('Location') }}</h3>
                        <div class="lazy-container radius-lg ratio ratio-21-8 border">
                            <iframe class="lazyload"
                                src="https://maps.google.com/maps?q={{ $project->latitude }},{{ $project->longitude }}&hl={{ $userCurrentLang->code }};z=15&amp;output=embed"></iframe>
                        </div>
                    </div>

                    <div class="pb-20"></div><!-- Space -->

                    <div class="project-planning mb-10" data-aos="fade-up">
                        <h3 class="mb-20">{{ $keywords['Floor Planning'] ?? __('Floor Planning') }}</h3>
                        <div class="row">
                            @foreach ($floorPlanImages as $floorplan)
                                <div class="col-lg-4">
                                    <div class="mb-30">
                                        <img class="lazyload blur-up radius-lg"
                                            src="{{ asset('assets/img/project/floor-paln-images/' . $floorplan->image) }}"
                                            data-src="{{ asset('assets/img/project/floor-paln-images/' . $floorplan->image) }}">
                                    </div>
                                </div>
                            @endforeach



                        </div>
                    </div>

                    <div class="pb-20"></div><!-- Space -->
                    @if (count($project->projectTypes) > 0)
                        <div class="project-type mb-10" data-aos="fade-up">
                            <h3 class="mb-20">{{ $keywords['Project Types'] ?? __('Project Types') }}</h3>
                            <div class="row">
                                @foreach ($project->projectTypes as $typeContent)
                                    <div class="col-lg-4 col-md-6">
                                        <div class="card border mb-30">
                                            <div class="card-content">
                                                <ul class="m-0 p-0">
                                                    <li class="d-flex align-items-center">
                                                        <span
                                                            class="font-lg  color-dark">{{ $keywords['Area'] ?? __('Area') }}</span>
                                                        <span
                                                            class="icon-start @if (in_array($userBs->theme, ['home_five'])) color-dark @endif">
                                                            <i class="fal fa-vector-square"></i>

                                                            {{ $typeContent?->min_area }}
                                                            @if (!empty($typeContent->max_area))
                                                                {{ ' - ' . $typeContent->max_area }}
                                                            @endif
                                                            {{ $keywords['Sqft'] ?? __('Sqft') }}
                                                        </span>
                                                    </li>
                                                    <li class="d-flex align-items-center">
                                                        <span
                                                            class="font-lg color-dark">{{ $keywords['Price'] ?? __('Price') }}</span>
                                                        <span
                                                            class="icon-start  @if (in_array($userBs->theme, ['home_five'])) color-dark @endif"><i
                                                                class="ico-save-money"></i>{{ $typeContent?->min_price }}
                                                            @if (!empty($typeContent->max_price))
                                                                {{ ' - ' . $typeContent->max_price }}
                                                            @endif
                                                        </span>
                                                    </li>
                                                    <li class="d-flex align-items-center">
                                                        <span
                                                            class="font-lg color-dark">{{ $keywords['Unit'] ?? __('Unit') }}</span>
                                                        <span
                                                            class="icon-start  @if (in_array($userBs->theme, ['home_five'])) color-dark @endif"><i
                                                                class="ico-home"></i>{{ $typeContent?->unit }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    {{-- @if (!empty(showAd(3)))
                        <div class="text-center mb-3 mt-3">
                            {!! showAd(3) !!}
                        </div>
                    @endif --}}
                    <div class="pb-20"></div><!-- Space -->

                    <div class="project-gallery">
                        <h3 class="mb-20"> {{ $keywords['Project Gallery Images'] ?? __('Project Gallery Images') }}
                        </h3>
                        <div class="row masonry-gallery grid gallery-popup">
                            <div class="col-lg-4 col-md-6 grid-sizer"></div>
                            @foreach ($galleryImages as $gallery)
                                <div class="col-lg-4 col-md-6 grid-item mb-30">
                                    <div class="card radius-md">
                                        <a href="{{ asset('assets/img/project/gallery-images/' . $gallery->image) }}"
                                            class="card-img">
                                            <img
                                                src="{{ asset('assets/img/project/gallery-images/' . $gallery->image) }}">
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
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
                    <div class="">
                        <h5 class="modal-title" id="exampleModalLongTitle"> {{ $keywords['Share On'] ?? __('Share On') }}
                        </h5>
                    </div>
                    <div class="">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
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
                            <a class="twitter btn" href="https://twitter.com/intent/tweet?text={{ url()->current() }}"><i
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
