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
    <div class="projects-area pt-100 pb-70">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="product-sort-area mb-20" data-aos="fade-up">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <form action="{{ route('front.user.projects', getParam()) }}" method="GET">
                                    <div class="project-filter-form radius-md pb-10">
                                        <div class="row">
                                            <div class="col-lg-4 mb-10">
                                                <input type="search" name="title" class="form-control"
                                                    placeholder="{{ $keywords['Search By Title'] ?? __('Search By Title') }}"
                                                    value="{{ request()->input('title') }}">
                                            </div>
                                            <div class="col-lg-4 mb-10">
                                                <input type="search" name="location" class="form-control"
                                                    placeholder="{{ $keywords['Search By Location'] ?? __('Search By Location') }}"
                                                    value="{{ request()->input('location') }}">
                                            </div>
                                            <div class="col-lg-3 mb-10">
                                                <button class="btn btn-lg btn-primary w-100 filled-btn" type="submit">
                                                    <i class="far fa-search"></i> {{ $keywords['Search'] ?? __('Search') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-4 mb-20">
                                <ul class="product-sort-list text-lg-end list-unstyled">
                                    <li class="item">
                                        <form action="{{ route('front.user.projects', getParam()) }}" method="GET"
                                            onchange="submit();">
                                            <div class="sort-item d-flex align-items-center">

                                                <label class="@if ($userBs->theme != 'home_five') color-dark @endif me-2">
                                                    {{ $keywords['Sort By'] ?? __('Sort By') }}:</label>
                                                <select
                                                    class="@if (!in_array($userBs->theme, ['home_five', 'home_nine'])) nice-select @else form-control @endif "
                                                    name="sort">
                                                    <option value="new">{{ $keywords['Newest'] ?? __('Newest') }}
                                                    </option>
                                                    <option value="old">{{ $keywords['Oldest'] ?? __('Oldest') }}
                                                    </option>
                                                    <option value="high-to-low">
                                                        {{ $keywords['High to Low'] ?? __('High to Low') }}</option>
                                                    <option value="low-to-high">
                                                        {{ $keywords['Low to High'] ?? __('Low to High') }}</option>

                                                </select>
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @forelse ($projects as $project)
                            <div class="col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                                <a
                                    href="{{ route('front.user.project.details', [getParam(), 'slug' => $project->slug]) }}">
                                    <div class="card mb-30 product-default">
                                        <div class="card-img">
                                            <div class="lazy-container ratio ratio-1-3">
                                                <img class="lazyload"
                                                    data-src="{{ asset('assets/img/project/featured/' . $project->featured_image) }}"
                                                    src="{{ asset('assets/img/project/featured/' . $project->featured_image) }}">
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
                                            <span class="price"> {{ $project->min_price }}
                                                {{ !empty($project->max_price) ? ' - ' . $project->max_price : '' }}

                                            </span>


                                            @if ($project->user)
                                                <a class="color-medium" {{-- href="{{ route('frontend.agent.details', [getParam(), 'agentusername' => $project->user->username]) }}" --}} target="_self">
                                                    <div class="user rounded-pill mt-10">
                                                        <div class="user-img lazy-container ratio ratio-1-1 rounded-pill">
                                                            <img class="lazyload"
                                                                data-src="{{ $project->user->photo ? asset('assets/front/img/user/' . $project->user->photo) : asset('assets/img/user-profile.jpg') }}"
                                                                src="{{ $project->user->photo ? asset('assets/front/img/user/' . $project->user->photo) : asset('assets/img/user-profile.jpg') }}">
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
