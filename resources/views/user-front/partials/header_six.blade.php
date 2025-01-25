<style>
    .header-navigation
    .main-menu ul li > a:hover {
        color: #64748b !important;
        /* transform: scale(1.2); */
        /* transition: all 0.5s ease; */
    }
    .header-navigation .main-menu ul li:hover > a {
        color: #64748b !important;

    }

</style>
<!--====== Start Header ======-->
<header class="header-area-one">
    <!-- Header Logo Area -->
    <div class="header-logo-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-3">
                    @if ($userBs->logo)
                    <div class="site-branding">
                        <a href="{{ route('front.user.detail.view', getParam()) }}" class="brand-logo">
                            <img data-src="{{ asset('assets/front/img/user/' . $userBs->logo) }}" class="lazy" alt="Lawgne"></a>
                    </div>
                    @endif
                </div>
                <div class="col-lg-8 col-md-9">
                    <div class="site-info">
                        @php
                        $phone_numbers = !empty($userContact->contact_numbers) ? explode(',', $userContact->contact_numbers) : [];
                        $emails = !empty($userContact->contact_mails) ? explode(',', $userContact->contact_mails) : [];
                        $addresses = !empty($userContact->contact_addresses) ? explode(PHP_EOL, $userContact->contact_addresses) : [];
                        @endphp

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header Navigation -->
    <div class="header-navigation mobile-rs-nav">
        <div class="container">
            <div class="navigation-wrapper">
                <div class="navbar-toggler">
                    <span></span><span></span><span></span>
                </div>
                <div class="row align-items-center">
                    <div class="col-lg-8 col-4">
                        <!-- Primary Menu -->
                        <div class="primary-menu">
                            <div class="nav-menu">
                                <div class="navbar-close"><i class="far fa-times"></i></div>

                                <!-- Pushed Item -->
                                <div class="nav-pushed-item"></div>

                                <nav class="main-menu">
                                    <ul>
                                        @php
                                        $links = json_decode($userMenus, true);
                                        @endphp
                                        @if ($links)
                                        @foreach ($links as $link)
                                        @php
                                        $href = getUserHref($link);
                                        @endphp
                                        @if (!array_key_exists('children', $link))
                                        <li class="menu-item"><a href="{{ $href }}" target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                                        </li>
                                        @else
                                        <li class="menu-item has-children">
                                            <a href="{{ $href }}" target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                                            <ul class="sub-menu">
                                                @foreach ($link['children'] as $level2)
                                                @php
                                                $l2Href = getUserHref($level2);
                                                @endphp
                                                <li class="menu-item"><a href="{{ $l2Href }}" target="{{ $level2['target'] }}">{{ $level2['text'] }}</a>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                        @endif
                                        @endforeach

                                        @if (in_array('Request a Quote', $packagePermissions))
                                        @if ($userBs->is_quote)
                                        <li class="menu-item d-block d-xl-none"><a href="{{ route('front.user.quote', getParam()) }}" target="{{ $link['target'] }}">{{ $keywords['Request_A_Quote'] ?? 'Request A Quote' }}</a>
                                        </li>
                                        @endif
                                        @endif
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-8">
                        <!-- Header Nav -->
                        <div class="header-right-nav d-flex align-items-center">
                            <ul>
                                @if (in_array('Request a Quote', $packagePermissions))
                                @if ($userBs->is_quote)
                                <li class="d-xl-block d-none"><a href="{{ route('front.user.quote', getParam()) }}" class="main-btn float-right m-0">{{ $keywords['Request_A_Quote'] ?? 'Request A Quote' }}</a>
                                </li>
                                @endif
                                @endif
                                <li>
                                    <form action="{{ route('changeUserLanguage', getParam()) }}" id="userLangForms">
                                        @csrf
                                        <input type="hidden" name="username" value="{{ $user->username }}">
                                        <input type="hidden" name="code" id="lang-code" value="">
                                        <div class="language-selection language-selection-two">
                                            @if ($userCurrentLang->id)
                                            <div class="current-language">
                                                <img src="{{ asset('assets/front/img/flags/' . $userCurrentLang->code . '.png') }}" alt="{{ $userCurrentLang->name }}" class="img-fluid" style="width: 25px; height: 25px;">
                                                <i class="far fa-angle-down"></i>
                                            </div>
                                            @endif
                                            <ul class="language-list" id="language-list">
                                                @foreach ($userLangs as $userLang)
                                                <li>
                                                    <a href="javascript:void(0)" data-value="{{ $userLang->code }}" onclick="changeLanguage('{{ $userLang->code }}')">
                                                        <img src="{{ asset('assets/front/img/flags/' . $userLang->code . '.png') }}" alt="{{ $userLang->name }}" title="{{ convertUtf8($userLang->name) }}" class="img-fluid" style="width: 25px; height: 25px;">
                                                        {{$userLang->name}}
                                                    </a>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </form>
                                </li>
                                <li>
                                    <div class="info nav-push-item">
                                        @if (in_array('Ecommerce', $packagePermissions) ||
                                        in_array('Hotel Booking', $packagePermissions) ||
                                        in_array('Course Management', $packagePermissions))
                                        @guest('customer')
                                        <a href="{{ route('customer.login', getParam()) }}">{{ $keywords['Login'] ?? __('Login') }}</a>
                                        <a href="{{ route('customer.signup', getParam()) }}">{{ $keywords['Signup'] ?? __('Signup') }}</a>
                                        @endguest
                                        @auth('customer')
                                        @php $authUserInfo = Auth::guard('customer')->user(); @endphp
                                        <a href="{{ route('customer.dashboard', getParam()) }}">{{ $keywords['Dashboard'] ?? __('Dashboard') }}</a>
                                        <a href="{{ route('customer.logout', getParam()) }}">{{ $keywords['Logout'] ?? __('Logout') }}</a>
                                        @endauth
                                        @endif
                                    </div>
                                </li>
                                <li class="d-xl-none off-nav-btn">
                                    <div class="off-menu">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<style>
 .language-selection {
    position: relative;
    min-width: auto;
}

.current-language {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.current-language:hover {
    background: #f8fafc;
}

.flag {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 2px;
}

.arrow-down {
    margin-left: auto;
    border: solid #64748b;
    border-width: 0 2px 2px 0;
    display: inline-block;
    padding: 3px;
    transform: rotate(45deg);
    transition: transform 0.2s ease;
}

.language-selection:hover .arrow-down {
    transform: rotate(-135deg);
}

.language-list {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 4px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1000;
}

.language-selection:hover .language-list {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.language-list li {
    margin: 0;
}

.language-list li a {
    display: block;
    padding: 8px 12px;
    color: #1e293b;
    text-decoration: none;
    transition: background 0.2s ease;
    width: 100%;
}

.language-list li a:hover {
    background: #f8fafc;
}

.language-list li:first-child a {
    border-radius: 6px 6px 0 0;
}

.language-list li:last-child a {
    border-radius: 0 0 6px 6px;
}

</style>
<script>
    function changeLanguage(code) {
        const langCodeInput = document.getElementById('lang-code');
        langCodeInput.value = code;
        document.getElementById('userLangForms').submit();
    }
</script>
<!--====== End Header ======-->
