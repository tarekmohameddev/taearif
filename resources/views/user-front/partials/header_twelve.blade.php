@php
    $user = getUser();
@endphp
<!--====== Start nav-toggle ======-->
<div class="nav-toggoler">
    <span></span>
    <span></span>
    <span></span>
</div>
<!--====== End nav-toggle ======-->

<!--====== Start Header Section ======-->
<header class="header-area">
    <div class="navigation-wrapper">
        <div class="user-box text-center">
            <div class="user-img">
                <a href="{{ route('front.user.detail.view', getParam()) }}" class="d-flex">
                    <img class="lazy"
                        data-src="{{ $user->photo ? asset('assets/front/img/user/' . $user->photo) : asset('assets/admin/img/noimage.jpg') }}"
                        alt="">
                </a>
            </div>
            <h4>{{ $userBs->website_title }}</h4>
            <span class="position">{{ $user->username }}</span>
        </div>
        <div class="primary-menu">
            <nav class="main-menu">
                <ul>
                    @php
                        $links = json_decode($userMenus, true);
                    @endphp
                    {{-- @dd($links) --}}
                    @foreach ($links as $link)
                        @php
                            $href = getUserHref($link);
                        @endphp
                        @if (!array_key_exists('children', $link))
                            <li><a href="{{ $href }}">

                                    @if (!empty($link['icon']) && $link['icon'] != 'empty')
                                        <i class="{{ $link['icon'] }}"></i>
                                    @endif
                                    {{ $link['text'] }}
                                </a>
                            </li>
                        @else
                            <li class="menu-item menu-item-has-children">
                                <a href="{{ $href }}" target="{{ $link['target'] }}">
                                    @if (!empty($link['icon']) && $link['icon'] != 'empty')
                                        <i class="{{ $link['icon'] }}"></i>
                                    @endif
                                    {{ $link['text'] }}
                                </a>
                                <ul class="sub-menu">
                                    @foreach ($link['children'] as $level2)
                                        @php
                                            $l2Href = getUserHref($level2);
                                        @endphp
                                        <li>
                                            <a href="{{ $l2Href }}"
                                                target="{{ $level2['target'] }}">{{ $level2['text'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>

                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>
        </div>
        <div class="nav-social">
            <ul class="social-link">
                @if (isset($social_medias))
                    @foreach ($social_medias as $social_media)
                        <li>
                            <a href="{{ $social_media->url }}" target="_blank">
                                <i class="{{ $social_media->icon }}"></i>
                            </a>
                        </li>
                    @endforeach
                @endif

            </ul>
        </div>
    </div>
    <div class="nav-right">
        {{-- <a href="#" class="main-btn filled-btn">Login</a> --}}
        @if (in_array('Ecommerce', $packagePermissions) ||
                in_array('Hotel Booking', $packagePermissions) ||
                in_array('Donation Management', $packagePermissions) ||
                in_array('Course Management', $packagePermissions))
            @guest('customer')
                <a href="{{ route('customer.login', getParam()) }}" class="main-btn filled-btn"> <i
                        class="fal fa-sign-in-alt">
                    </i> {{ $keywords['Login'] ?? __('Login') }}</a>


                <a href="{{ route('customer.signup', getParam()) }}" class="main-btn filled-btn"> <i
                        class="fal fa-user-plus">
                    </i> {{ $keywords['Signup'] ?? __('Signup') }}</a>
            @endguest
            @auth('customer')
                <a href="{{ route('customer.dashboard', getParam()) }}" class="main-btn filled-btn">
                    <i class="far fa-tachometer-fast"></i>
                    {{ $keywords['Dashboard'] ?? __('Dashboard') }} </a>

                <a href="{{ route('customer.logout', getParam()) }}" class="main-btn filled-btn"><i
                        class="fal fa-sign-out-alt"></i>
                    {{ $keywords['Logout'] ?? __('Logout') }}</a>
            @endauth
        @endif

        <div class="language-selector bordered-style d-flex">
            <form action="{{ route('changeUserLanguage', getParam()) }}" id="userLangForms">
                @csrf
                <input type="hidden" name="username" value="{{ $user->username }}">
                <input type="hidden" name="code" id="lang-code" value="">
                <div class="language-selection language-selection-two">
                    @if ($userCurrentLang->id)
                        <div class="current-language">
                            <img src="{{ asset('assets/front/img/flags/' . $userCurrentLang->code . '.png') }}"
                                alt="{{ $userCurrentLang->name }}" class="img-fluid mx-2" style="   height: 25px;">
                            <i class="far fa-angle-down"></i>
                        </div>
                    @endif
                    <ul class="language-list" id="language-list">
                        @foreach ($userLangs as $userLang)
                            <li>
                                <a href="javascript:void(0)" data-value="{{ $userLang->code }}"
                                    onclick="changeLanguage('{{ $userLang->code }}')">
                                    <img src="{{ asset('assets/front/img/flags/' . $userLang->code . '.png') }}"
                                        alt="{{ $userLang->name }}" title="{{ convertUtf8($userLang->name) }}"
                                        class="img-fluid mx-1" style=" height: 20px;">
                                    {{ $userLang->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </form>
        </div>


    </div>
</header>

<style>
    .language-flag img {
        border: 2px solid transparent;
        border-radius: 5px;
        transition: border-color 0.3s ease;
    }

    .language-flag input:checked+img {
        border-color: #007bff;
        /* Highlight color */
    }

    .language-selection-two {
        position: relative;
    }

    .current-language {
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .language-list {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        list-style: none;
        padding: 10px;
        min-width: 130px;
    }

    .language-list li {
        margin: 5px 0;
    }

    .language-list li a {
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .language-list li a img {
        margin-right: 5px;
        border-radius: 3px;
    }

    .current-language:hover+.language-list,
    .language-list:hover {
        display: block;
    }
</style>
<script>
    function changeLanguage(code) {
        const langCodeInput = document.getElementById('lang-code');
        langCodeInput.value = code;
        document.getElementById('userLangForms').submit();
    }
</script>
<!--====== End Header Section ======-->
