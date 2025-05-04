@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')
@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))

    @section('pageHeading', $keywords['Projects'] ?? __('Projects'))

    @section('metaKeywords', !empty($userSeo) ? $userSeo->meta_keyword_projects : '')
    @section('metaDescription', !empty($userSeo) ? $userSeo->meta_description_projects : '')
@else
    @section('tab-title')
        {{ $keywords['Projects'] ?? 'Projects' }}
    @endsection

    @section('meta-description', !empty($userSeo) ? $userSeo->meta_description_projects : '')
    @section('meta-keywords', !empty($userSeo) ? $userSeo->meta_keyword_projects : '')

    @section('page-name')
        {{ $keywords['Projects'] ?? 'Projects' }}
    @endsection
    @section('br-name')
        {{ $keywords['Projects'] ?? 'Projects' }}
    @endsection

    @section('styles')

        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/vendors/aos.min.css') }} ">
        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/partials.css') }}">
        @if ($userCurrentLang->rtl == 1)
            <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/rtl.css') }}">
        @endif
        <link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/responsive.css') }}">

    @endsection
    @section('scripts')
        <script src="{{ asset('assets/front/user/realestate/js/vendors/aos.min.js') }}"></script>

        <script>
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
        </script>

    @endsection
@endif

@include('user-front.realestate.partials.header.header-pages')
<div class="mt-30"></div>

@section('content')
    {{-- @includeIf('user-front.realestate.partials.breadcrumb', [
        'breadcrumb' => $breadcrumb,
        'title' => !empty($pageHeading)
            ? $pageHeading->projects_page_title
            : $keywords['Projects'] ?? __('Projects'),
        'subtitle' => !empty($pageHeading)
            ? $pageHeading->projects_page_title
            : $keywords['Projects'] ?? __('Projects'),
    ]) --}}
    <div class="projects-area pt-100 pb-70 ">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        @forelse ($projects as $project)
                            <div class="col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                                <a
                                    href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                                    <div class="card mb-30 product-default">
                                        <div class="card-img">
                                            <div class="lazy-container ratio ratio-1-3">
                                                <img class="lazyload"
                                                    data-src="{{ asset($project->featured_image) }}"
                                                    src="{{ asset('assets/front/images/placeholder.png') }}">
                                            </div>
                                            <span class="label">
                                                @if ($project->status == 0)
                                                    {{ $keywords['Under Construction'] ?? __('Under Construction') }}
                                                @elseif($project->status == 1)
                                                    {{ $keywords['Complete'] ?? __('Complete') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="card-text text-center p-3">
                                            <h3 class="card-title product-title color-white mb-1">
                                                {{ $project->title }}

                                            </h3>
                                            <span class="location icon-start"><i
                                                    class="fal fa-map-marker-alt"></i>{{ $project->address }}</span>
                                            <br>

                                        </div>
                                    </div>
                                </a>
                            </div>

                        @empty
                            <div class="col-lg-12">
                                <h3 class="text-center mt-5">
                                    {{ $keywords['No Project Found'] ?? __('No Project Found') }}
                                </h3>
                            </div>
                        @endforelse

                    </div>

                    <div class="pagination mb-30 justify-content-center">
                        {{ $projects->links() }}

                    </div>
                    {{-- @if (!empty(showAd(3)))
                        <div class="text-center mt-4">
                            {!! showAd(3) !!}
                        </div>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
@endsection
