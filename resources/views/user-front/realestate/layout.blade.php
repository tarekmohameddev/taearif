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
    <title>{{ convertUtf8($userApi_general_settingsData->site_name ?? '') }} - @yield('pageHeading')</title>

    <meta name="keywords" content="@yield('metaKeywords')">
    <meta name="description" content="@yield('metaDescription')">

    @yield('og:tag')

    {{-- fav icon --}}
    <link rel="shortcut icon" href="{{ asset($userBs->favicon) }}" type="img/png" />

        @php
            $primaryColor = $userBs->base_color;
            $secoundaryColor = $userBs->secondary_color;

            // Check whether color has '#' or not, will return 0 or 1
            if (!function_exists('checkColorCode')) {
                function checkColorCode($color)
                {
                    return preg_match('/^#[a-f0-9]{6}/i', $color);
                }
            }

            // Ensure the color has a '#' prefix
            if (isset($primaryColor) && checkColorCode($primaryColor) == 0 && checkColorCode($secoundaryColor) == 0) {
                $primaryColor = '#' . $primaryColor;
                $secoundaryColor = '#' . $secoundaryColor;
            }

            // Convert Hex to RGB
            if (!function_exists('rgb')) {
                function rgb($color = null)
                {
                    if (!$color) {
                        return '';
                    }
                    $hex = htmlspecialchars($color);
                    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
                    return "$r, $g, $b";
                }
            }
        @endphp
    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-primary-rgb: {{ rgb(htmlspecialchars($primaryColor)) }};
            --color-secondary: {{ $secoundaryColor }};
            --color-secondary-rgb: {{ rgb(htmlspecialchars($secoundaryColor)) }};
        }

        html, body {
        height: 100%;
        margin: 0;
        }

        body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        }

        main {
        flex: 1;
        }

    </style>

    {{-- @dd($primaryColor); --}}
    {{-- include styles --}}
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.styles.styles-v1')
        @includeIf('user-front.realestate.partials.styles.styles-v1')
    @elseif($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.styles.styles-v2')
        @includeIf('user-front.realestate.partials.styles.styles-v1')
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

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KS62NNTG');</script>
<!-- End Google Tag Manager -->

@php
    $fullHost = request()->getHost();
    $tenantId = explode('.', $fullHost)[0];
@endphp
<!-- Google tag (gtag.js) -->

<script async src="https://www.googletagmanager.com/gtag/js?id=G-RVFKM2F9ZN"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-RVFKM2F9ZN', {
    'custom_map': {
      'dimension1': 'tenant_id'
    },
    'tenant_id': '{{$user->username}}'
  });
  console.log('GA4 tenant_id = {{ $user->username }}');
</script>

</head>

<body>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KS62NNTG"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <main>

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

    </main>

</body>

</html>
