@php
$general_settingsData = json_decode($userApi_general_settingsData, true);
$logo = $general_settingsData['logo'] ?? [];
$favicon = $general_settingsData['favicon'] ?? [];
@endphp
<!-- Header Start -->
<header class="header-area header-1 @if (!request()->routeIs('front.user.detail.view')) header-static @endif" data-aos="slide-down">


    <div class="mobile-menu">
        <div class="container">
            <div class="mobile-menu-wrapper"></div>
        </div>
    </div>

    <div class="main-responsive-nav">
        <div class="container">
            <div class="logo">
                @if (!empty($logo))
                <a href="{{ route('front.user.detail.view', getParam()) }}">
                    <img style="max-height: 50px; width: auto;" src="{{ asset($logo) }}">
                </a>
                @endif
            </div>
            <button class="menu-toggler" type="button">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
    <div class="main-navbar">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <!-- Logo -->
                @if (!empty($logo))
                <a href="{{ route('front.user.detail.view', getParam()) }}" class="navbar-brand">
                    <img style="max-height: 50px; width: auto;" src="{{ asset($logo) }}">
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

                <div class="more-option mobile-item">
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


</header>
<style>
    .swiper-wrapper {
    position: relative;
    width: 100%;
    height: 17% !important;
    z-index: 1;
    display: flex;
    transition-property: transform;
    box-sizing: content-box;
}
</style>
