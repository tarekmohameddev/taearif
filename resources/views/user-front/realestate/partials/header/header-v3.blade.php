@php
$general_settingsData = json_decode($userApi_general_settingsData, true);
$logo = $general_settingsData['logo'] ?? [];
$favicon = $general_settingsData['favicon'] ?? [];

@endphp
<div class="request-loader">
    <img src="{{ asset('assets/img/loaders.gif') }}">
</div>

<header class="header-area header-2 @if (!request()->routeIs('front.user.detail.view', getParam())) header-static @endif" data-aos="slide-down">

@if ($api_menu_settingsData->status !== false)
    <!-- Start mobile menu -->
    <div class="mobile-menu text-white">
        <div class="container">
            <div class="mobile-menu-wrapper text-white"></div>
        </div>
    </div>
    <!-- End mobile menu -->

    <div class="main-responsive-nav text-white">
        <div class="container text-white">
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

    <div class="main-navbar text-white">
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

                <div class="more-option mobile-item text-white">
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

                    <div class="item">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle d-none" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                @if (!Auth::guard('customer')->check())
                                {{ $keywords['Customer'] ?? __('Customer') }}
                                @else
                                {{ Auth::guard('customer')->user()->username }}
                                @endif
                            </button>
                            <ul class="dropdown-menu radius-0">
                                @if (in_array('Ecommerce', $packagePermissions) ||
                                in_array('Hotel Booking', $packagePermissions) ||
                                in_array('Course Management', $packagePermissions) ||
                                in_array('Real Estate Management', $packagePermissions) ||
                                in_array('Donation Management', $packagePermissions))
                                @guest('customer')
                                <li>
                                    <a class="dropdown-item" href="{{ route('customer.login', getParam()) }}">
                                        {{ $keywords['Login'] ?? __('Login') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('customer.signup', getParam()) }}">
                                        {{ $keywords['Signup'] ?? __('Signup') }}
                                    </a>
                                </li>
                                @endguest

                                @auth('customer')
                                <li>
                                    <a class="dropdown-item" href="{{ route('customer.dashboard', getParam()) }}">
                                        {{ $keywords['Dashboard'] ?? __('Dashboard') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('customer.logout', getParam()) }}">
                                        {{ $keywords['Logout'] ?? __('Logout') }}
                                    </a>
                                </li>
                                @endauth
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>
@endif
</header>

<!-- Header-area end -->
