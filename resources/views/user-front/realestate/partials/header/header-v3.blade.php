@php
$general_settingsData = json_decode($userApi_general_settingsData, true);
$logo = $general_settingsData['logo'] ?? [];
$favicon = $general_settingsData['favicon'] ?? [];

@endphp
<div class="request-loader">
    <img src="{{ asset('assets/img/loaders.gif') }}">
</div>

<header class="header-area header-2 @if (!request()->routeIs('front.user.detail.view', getParam())) header-static @endif" data-aos="slide-down">

@if (!empty($api_menu_settingsData) && $api_menu_settingsData->status !== false)
    <!-- Start mobile menu -->
    <div class="mobile-menu ">
        <div class="container">
            <div class="mobile-menu-wrapper "></div>
        </div>
    </div>
    <!-- End mobile menu -->

    <div class="main-responsive-nav ">
        <div class="container ">
            <!-- Mobile Logo -->
            <div class="logo">
                @if (!empty($userBs->logo))
                <a href="{{ route('front.user.detail.view', getParam()) }}">
                    <img style="max-height: 50px; width: auto;" src="{{ asset($userBs->logo) }}">
                </a>
                @endif
            </div>
            <!-- Menu toggle button -->
            <button class="menu-toggler" type="button">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <div class="main-navbar ">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <!-- Logo -->
                @if (!empty($userBs->logo))
                <a href="{{ route('front.user.detail.view', getParam()) }}" class="navbar-brand">
                    <img style="max-height: 50px; width: auto;" src="{{ asset($userBs->logo) }}">
                </a>
                @endif

                <!-- Navigation items -->
                <div class="collapse navbar-collapse">
                    <ul id="mainMenu" class="navbar-nav mobile-item mx-auto">
                        @foreach ($userMenus as $menu)
                        @php
                        $href = $menu->is_external ? $menu->url : url($menu->url);
                        @endphp

                        @if ($menu->children->isEmpty())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $href }}" target="{{ $menu->is_external ? '_blank' : '_self' }}">
                                {{ $menu->label }}
                            </a>
                        </li>
                        @else
                        <li class="nav-item">
                            <a class="nav-link toggle" href="{{ $href }}" target="{{ $menu->is_external ? '_blank' : '_self' }}">
                                {{ $menu->label }}
                            </a>
                            <ul class="menu-dropdown">
                                @foreach ($menu->children as $child)
                                @php
                                $childHref = $child->is_external ? $child->url : url($child->url);
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ $childHref }}" target="{{ $child->is_external ? '_blank' : '_self' }}">
                                        {{ $child->label }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endif
                        @endforeach
                    </ul>
                </div>

                <div class="more-option mobile-item ">
                    <div class="item d-none">
                        <div class="language">
                            <form action="{{ route('changeUserLanguage', getParam()) }}" id="userLangForms">
                                @csrf
                                <input type="hidden" name="username" value="{{ $user->username }}">
                                <select class="nice-select" name="code" id="lang_code" onchange="this.form.submit()">
                                    @foreach ($userLangs as $userLang)
                                    <option {{ $userCurrentLang->id == $userLang->id ? 'selected' : '' }}
                                        value="{{ $userLang->code }}">
                                        {{ convertUtf8($userLang->name) }}
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>


                    @include('user-front.realestate.partials.customer-dropdown')
                </div>
            </nav>
        </div>
    </div>
@endif
</header>

<!-- Header-area end -->
