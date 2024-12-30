<!--====== Header part start ======-->
<header class="header-two sticky-header">
    <!-- Header Menu  -->
    <div class="header-nav sticky-nav">
        <div class="container-fluid container-1600">
            <div class="nav-container mobile-rs-nav">
                <!-- Site Logo -->
                @if (isset($userBs->logo))
                <div class="site-logo">
                    <a href="{{route('front.user.detail.view',getParam())}}">
                        <img src="{{asset('assets/front/img/user/'.$userBs->logo)}}" alt="Logo"></a>
                </div>
                @endif

                <!-- Main Menu -->
                <div class="nav-menu d-lg-flex align-items-center">

                    <!-- Navbar Close Icon -->
                    <div class="navbar-close">
                        <div class="cross-wrap"><span></span><span></span></div>
                    </div>

                    <!-- Pushed Item -->
                    <div class="nav-pushed-item"></div>

                    <!-- Mneu Items -->
                    <div class="menu-items">
                        <ul>
                            @php
                            $links = json_decode($userMenus, true);
                            @endphp
                            @foreach ($links as $link)
                                @php
                                    $href = getUserHref($link);
                                @endphp
                                @if (!array_key_exists("children",$link))
                                    <li><a href="{{$href}}" target="{{$link["target"]}}">{{$link["text"]}}</a></li>
                                @else
                                    <li class="has-submemu">
                                        <a href="{{$href}}" target="{{$link["target"]}}">{{$link["text"]}}</a>
                                        <ul class="submenu">
                                            @foreach ($link["children"] as $level2)
                                                @php
                                                    $l2Href = getUserHref($level2);
                                                @endphp
                                                <li><a href="{{$l2Href}}" target="{{$level2["target"]}}">{{$level2["text"]}}</a></li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endif
                            @endforeach
                            @if (in_array('Request a Quote',$packagePermissions))
                                @if($userBs->is_quote)
                                <li class="d-block d-lg-none"><a href="{{route('front.user.quote', getParam())}}">{{$keywords["Request A Quote"] ?? "Request A Quotes"}}</a></li>
                                @endif
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Navbar Extra  -->
                <div class="navbar-extra d-flex align-items-center">
                    <!-- language selection -->
                    <form action="{{ route('changeUserLanguage', getParam()) }}" id="userLangForms">
    @csrf
    <input type="hidden" name="username" value="{{ $user->username }}">
    <input type="hidden" name="code" id="lang-code" value="">
    <div class="language-selection language-selection-two">
        @if ($userCurrentLang->id)
            <div class="current-language">
                <img 
                    src="{{ asset('assets/front/img/flags/' . $userCurrentLang->code . '.png') }}" 
                    alt="{{ $userCurrentLang->name }}" 
                    class="img-fluid" 
                    style="width: 25px; height: 25px;"> 
                <i class="far fa-angle-down"></i>
            </div>
        @endif
        <ul class="language-list" id="language-list">
            @foreach ($userLangs as $userLang)
                <li>
                    <a href="javascript:void(0)" 
                       data-value="{{ $userLang->code }}" 
                       onclick="changeLanguage('{{ $userLang->code }}')">
                        <img 
                            src="{{ asset('assets/front/img/flags/' . $userLang->code . '.png') }}" 
                            alt="{{ $userLang->name }}" 
                            title="{{ convertUtf8($userLang->name) }}" 
                            class="img-fluid" 
                            style="width: 25px; height: 25px;">
                            {{$userLang->name}}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</form>
                    @if (in_array('Request a Quote',$packagePermissions))
                        @if($userBs->is_quote)
                            <a href="{{route('front.user.quote', getParam())}}" class="main-btn main-btn-3 d-none d-lg-inline-block">{{$keywords['Request_A_Quote'] ?? 'Request A Quote'}}</a>
                        @endif
                    @endif
                    <!-- Navbar Toggler -->
                    <div class="navbar-toggler">
                        <span></span><span></span><span></span>
                    </div>

                    <div class="info nav-push-item">
                        @if (in_array('Ecommerce',$packagePermissions)||
                                in_array('Hotel Booking', $packagePermissions) ||
                                in_array('Course Management', $packagePermissions))
                            @guest('customer')
                                <a
                                    href="{{ route('customer.login', getParam()) }}">{{ $keywords['Login'] ?? __('Login') }}</a>
                                <a
                                    href="{{ route('customer.signup', getParam()) }}">{{ $keywords['Signup'] ?? __('Signup') }}</a>
                            @endguest
                            @auth('customer')
                                @php $authUserInfo = Auth::guard('customer')->user(); @endphp
                                <a
                                    href="{{ route('customer.dashboard', getParam()) }}">{{ $keywords['Dashboard'] ?? __('Dashboard') }}</a>
                                <a
                                    href="{{ route('customer.logout', getParam()) }}">{{ $keywords['Logout'] ?? __('Logout') }}</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    .language-flag img {
    border: 2px solid transparent;
    border-radius: 5px;
    transition: border-color 0.3s ease;
}

.language-flag input:checked + img {
    border-color: #007bff; /* Highlight color */
}

.language-selection-two {
    position: relative;
}

.current-language {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 10px;
    padding: 20px 0px;
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

.current-language:hover + .language-list,
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
<!--====== Header part end ======-->
