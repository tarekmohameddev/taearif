<!DOCTYPE html>

<html lang="{{ $userCurrentLang->code }}" @if ($userCurrentLang->rtl == 1) dir="rtl" @endif>

<head>
    {{-- required meta tags --}}
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    {{-- csrf-token for ajax request --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- title --}}
    <title>{{ convertUtf8($userBs->website_title) }} - @yield('pageHeading')</title>

    <meta name="keywords" content="@yield('metaKeywords')">
    <meta name="description" content="@yield('metaDescription')">
    @yield('og:tag')
    {{-- fav icon --}}
    <link rel="shortcut icon"
        href="{{ !empty($userBs->favicon) ? asset('assets/front/img/user/' . $userBs->favicon) : '' }}"
        type="img/png" />

    @php
        $primaryColor = $userBs->base_color;
        $secoundaryColor = $userBs->secondary_color;
        // check, whether color has '#' or not, will return 0 or 1
        function checkColorCode($color)
        {
            return preg_match('/^#[a-f0-9]{6}/i', $color);
        }

        // if, primary color value does not contain '#', then add '#' before color value
        if (isset($primaryColor) && checkColorCode($primaryColor) == 0 && checkColorCode($secoundaryColor) == 0) {
            $primaryColor = '#' . $primaryColor;
            $secoundaryColor = '#' . $secoundaryColor;
        }

        // change decimal point into hex value for opacity
        function rgb($color = null)
        {
            if (!$color) {
                echo '';
            }
            $hex = htmlspecialchars($color);
            [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
            echo "$r, $g, $b";
        }
    @endphp
    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-primary-rgb: {{ rgb(htmlspecialchars($primaryColor)) }};
            --color-secondary: {{ $secoundaryColor }};
            --color-secondary-rgb: {{ rgb(htmlspecialchars($secoundaryColor)) }};
        }
    </style>

    {{-- @dd($primaryColor); --}}
    {{-- include styles --}}
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.styles.styles-v1')
    @elseif($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.styles.styles-v2')
    @elseif($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.styles.styles-v3')
    @endif
    {{-- additional style --}}
    @yield('style')

    @if (!is_null($userBs->adsense_publisher_id))
        <!------google adsense----------->
        <script async
            src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $userBs->adsense_publisher_id }}"
            crossorigin="anonymous"></script>
        <!------google adsense----------->
    @endif

</head>

<body>
    {{-- preloader start --}}
    @if ($userBs->preloader == 1)
        <div id="preLoader">
            <div class="loader">
                <svg viewBox="0 0 80 80">
                    <rect x="8" y="8" width="64" height="64"></rect>
                </svg>
                <div class="icon">
                    <img src="{{ asset('assets/front/img/user/' . $userBs->preloader) }}" alt=""
                        class="img-fluid">
                </div>
            </div>
        </div>
    @endif
    <div class="request-loader">
        <img src="{{ asset('assets\front\user\realestate\images\loaders.gif') }}">
    </div>
    {{-- preloader end --}}
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.header.header-v1')
    @elseif ($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.header.header-v2')
    @elseif ($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.header.header-v3')
    @endif
    {{-- header end --}}


    @yield('content')
    {{-- back to top --}}
    {{-- <a href="#" class="back-to-top"><i class="fas fa-angle-up"></i></a> --}}

    {{-- floating whatsapp button --}}
    @if ($userBs->whatsapp_status == 1)
        <div class="whatsapp-btn"></div>
    @endif

    {{-- announcement popup --}}
    @includeIf('frontend.partials.popups')

    {{-- cookie alert --}}
    @if (!is_null($cookieAlertInfo) && $cookieAlertInfo->cookie_alert_status == 1)
        @include('frontend.cookie-alert.index')
    @endif

    {{-- include footer --}}
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.footer.footer-v1')
    @elseif ($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.footer.footer-v2')
    @elseif ($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.footer.footer-v3')
    @endif
    {{-- </div> --}}
    {{-- end main-wrapper --}}

    {{-- include scripts --}}
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.scripts.scripts-v1')
    @elseif ($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.scripts.scripts-v2')
    @elseif ($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.scripts.scripts-v3')
    @endif
    {{-- @includeIf('frontend.partials.scripts') --}}

    {{-- additional script --}}
    {{-- @yield('script') --}}
</body>

</html>
