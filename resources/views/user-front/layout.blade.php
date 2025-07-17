@php
$general_settingsData = json_decode($userApi_general_settingsData, true);
    $favicon = $general_settingsData['favicon'] ?? [];
    $site_name = $general_settingsData['site_name'] ?? [];

@endphp

<!DOCTYPE html>
<html lang="{{ $userCurrentLang->code }}" @if ($userCurrentLang->rtl == 1) dir="rtl" @endif>

<head>
    <!--====== Required meta tags ======-->
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="@yield('meta-description')">
    <meta name="keywords" content="@yield('meta-keywords')">

    @yield('og-meta')
    <!--====== Title ======-->
    <title> {{ convertUtf8($site_name) }} - @yield('tab-title') </title>
    @includeIf('user-front.partials.styles')
    @if ($userBs->whatsapp_status == 1)
        <style>
            .back-to-top {
                left: 10px;
            }
        </style>
    @endif
    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.styles.styles-v1')
    @elseif($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.styles.styles-v2')
    @elseif($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.styles.styles-v3')
    @endif
    @if (!is_null($userBs->adsense_publisher_id))
        <!------google adsense----------->
        <script async
            src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $userBs->adsense_publisher_id }}"
            crossorigin="anonymous"></script>
        <!------google adsense----------->
    @endif
</head>

<body class="@if ($userBs->theme == 'home_five') dark-version @endif ">
    <!--[if lte IE 9]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade
    your browser</a> to improve your experience and security.</p>
<![endif]-->

    <!--====== Preloader ======-->
    <!-- LOADER -->
    @if (!empty($userBs->preloader))
        <div id="preloader">
            <div class="loader-cubes">
                <img src="{{ asset('assets/front/img/user/' . $userBs->preloader) }}" alt="" class="img-fluid">
            </div>
        </div>
    @endif
    <!-- END LOADER -->
    @if ($userBs->theme === 'home_two')
        @includeIf('user-front.partials.header_two')
    @elseif($userBs->theme === 'home_three')
        @includeIf('user-front.partials.header_three')
    @elseif($userBs->theme === 'home_four')
        @includeIf('user-front.partials.header_four')
    @elseif($userBs->theme === 'home_five')
        @includeIf('user-front.partials.header_five')
    @elseif($userBs->theme === 'home_six')
        @includeIf('user-front.partials.header_six')
    @elseif($userBs->theme === 'home_seven')
        @includeIf('user-front.partials.header_seven')
    @elseif($userBs->theme === 'home_eight')
        @includeIf('user-front.partials.header_eight')
    @elseif($userBs->theme === 'home_nine')
        @includeIf('user-front.partials.header_nine')
    @elseif($userBs->theme === 'home_ten')
        @includeIf('user-front.partials.header_ten')
    @elseif($userBs->theme === 'home_eleven')
        @includeIf('user-front.partials.header_eleven')
    @elseif($userBs->theme === 'home_twelve')
        @includeIf('user-front.partials.header_twelve')
    @elseif ($userBs->theme === 'home13')
      @includeIf('user-front.realestate.partials.header.header-v1')
    @elseif ($userBs->theme === 'home14')
      @includeIf('user-front.realestate.partials.header.header-v2')
    @elseif ($userBs->theme === 'home15')
      @includeIf('user-front.realestate.partials.header.header-v3')
    @else
        @includeIf('user-front.partials.header')
    @endif
    @if (
        !request()->routeIs('front.user.detail.view') &&
            !request()->routeIs('front.user.course.details') &&
            // !request()->routeIs('front.user.projects') &&
            !request()->routeIs('front.user.project.details') &&

            !request()->routeIs('front.user.property.details') &&
            !request()->routeIs('front.user.properties'))
        @php
            $brBg = $userBs->breadcrumb ?? 'breadcrumb.jpg';
        @endphp
        <!--====== Breadcrumb part Start ======-->

        <!--====== Breadcrumb part End ======-->
    @endif
    @yield('content')
    @if ($userBs->theme == 'home_two')
        @includeIf('user-front.partials.footer_two')
    @elseif($userBs->theme == 'home_three')
        @includeIf('user-front.partials.footer_three')
    @elseif($userBs->theme == 'home_four')
        @includeIf('user-front.partials.footer_four')
    @elseif($userBs->theme == 'home_five')
        @includeIf('user-front.partials.footer_five')
    @elseif($userBs->theme == 'home_six')
        @includeIf('user-front.partials.footer_six')
    @elseif($userBs->theme == 'home_seven')
        @includeIf('user-front.partials.footer_seven')
    @elseif($userBs->theme == 'home_eight')
        @includeIf('user-front.partials.footer_eight')
    @elseif($userBs->theme == 'home_nine')
        @includeIf('user-front.partials.footer_nine')
    @elseif($userBs->theme == 'home_ten')
        @includeIf('user-front.partials.footer_ten')
    @elseif($userBs->theme == 'home_eleven')
        @includeIf('user-front.partials.footer_eleven')
    @elseif($userBs->theme == 'home_twelve')
        @includeIf('user-front.partials.footer_twelve')
    @elseif($userBs->theme == 'home13')
      @includeIf('user-front.realestate.partials.footer.footer-v1')
    @elseif ($userBs->theme === 'home14')
      @includeIf('user-front.realestate.partials.footer.footer-v2')
    @elseif ($userBs->theme === 'home15')
      @includeIf('user-front.realestate.partials.footer.footer-v3')
    @else
        @includeIf('user-front.partials.footer')
    @endif
    @php
        $userShop = App\Models\User\UserShopSetting::where('user_id', $user->id)->first();
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
    @endphp
    @if (!empty($permissions) && in_array('Ecommerce', $permissions))
        @if (!empty($userShop))
            @if ($userBs->theme != 'home_eight')
                @if ($userShop->is_shop == 1 && $userBs->catalog_mode == 0)
                    <div id="cartIconWrapper" class=" d-none">
                        <a class="d-block" id="cartIcon" href="{{ route('front.user.cart', getParam()) }}">
                            <div class="cart-length">
                                <i class="fal fa-shopping-bag"></i>
                                <span class="length">{{ cartLength() }}

                                    {{ cartLength() > 1 ? $keywords['ITEMS'] ?? 'ITEMS' : $keywords['ITEM'] ?? 'ITEM' }}</span>
                            </div>
                            <div class="cart-total">
                                {{ $userBs->base_currency_symbol_position == 'left' ? $userBs->base_currency_symbol : '' }}
                                {{ cartTotal() }}
                                {{ $userBs->base_currency_symbol_position == 'right' ? $userBs->base_currency_symbol : '' }}
                            </div>
                        </a>
                    </div>
                @endif
            @endif
        @endif
    @endif


    @if ($userBs->cookie_alert_status == 1)
        {{-- Cookie alert dialog start --}}
        <div class="cookie">
            @include('cookie-consent::index')
        </div>
        {{-- Cookie alert dialog end --}}
    @endif
    @if ($userBs->whatsapp_status == 1)
        {{-- WhatsApp Chat Button --}}
        <div id="WAButton"></div>
    @endif
    @includeIf('user-front.partials.scripts')
    {{-- Loader --}}
    <div class="request-loader">
        <img src="{{ asset('assets/front/img/loader.svg') }}" alt="Loader GIF" title="A Loader GIF Image">
    </div>
    {{-- Loader --}}
    <script>
        //  image (id) preview js/
        $(document).on('change', '#image', function(event) {
            var file = event.target.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.showImage img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        })
    </script>

<script>

const tokenInput = document.querySelector('input[name="_token"]');
    csrfToken = tokenInput ? tokenInput.value : null;
    user_id_value = {{ $user->id }};
  fetch('track-visitor', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken // Include the CSRF token here
    },
      body: JSON.stringify({
          user_agent: navigator.userAgent,
          user_id: user_id_value,
          device_type: /Mobile|Android|iP(hone|ad)/.test(navigator.userAgent) ? 'mobile' : 'web'
      })
  });
</script>

    @if ($userBs->theme == 'home13')
        @includeIf('user-front.realestate.partials.scripts.scripts-v1')
    @elseif ($userBs->theme == 'home14')
        @includeIf('user-front.realestate.partials.scripts.scripts-v2')
    @elseif ($userBs->theme == 'home15')
        @includeIf('user-front.realestate.partials.scripts.scripts-v3')
    @endif

</body>

</html>
