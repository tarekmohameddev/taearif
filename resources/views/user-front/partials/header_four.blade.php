 <!--====== Start Header ======-->
 <header class="template-header absolute-header sticky-header @if (!request()->routeIs('front.user.detail.view')) inner @endif">
     <div class="container-fluid container-1550">
         <div class="header-inner mobile-rs-nav">
             <div class="header-left">
                 <div class="site-logo">
                     <a href="{{ route('front.user.detail.view', getParam()) }}">
                         <img class="lazy" data-src="{{ asset('assets/front/img/user/' . $userBs->logo) }}"
                             alt="Tilke">
                     </a>
                 </div>
             </div>
             <div class="header-center">
                 <nav class="nav-menu d-none d-xl-block">
                     <ul class="primary-menu">
                         @php
                             $links = json_decode($userMenus, true);
                         @endphp
                         @foreach ($links as $link)
                             @php
                                 $href = getUserHref($link);
                             @endphp
                             @if (!array_key_exists('children', $link))
                                 <li><a href="{{ $href }}"
                                         target="{{ $link['target'] }}">{{ $link['text'] }}</a></li>
                             @else
                                 <li>
                                     <a href="{{ $href }}"
                                         target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                                     <ul class="submenu">
                                         @foreach ($link['children'] as $level2)
                                             @php
                                                 $l2Href = getUserHref($level2);
                                             @endphp
                                             <li><a href="{{ $l2Href }}"
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
             <div class="header-right">
                 <ul class="header-extra">
                     @if (in_array('Request a Quote', $packagePermissions))
                         @if ($userBs->is_quote)
                             <li class="header-btns d-none d-md-block">
                                 <a href="{{ route('front.user.quote', getParam()) }}" class="template-btn">
                                     {{ $keywords['Request_A_Quote'] ?? 'Request A Quote' }}
                                     <i class="far fa-long-arrow-right"></i>
                                 </a>
                             </li>
                         @endif
                     @endif
                     <li class=" d-xl-block">
                         <form action="{{ route('changeUserLanguage', getParam()) }}" id="userLangForms">
                             @csrf
                             <input type="hidden" name="username" value="{{ $user->username }}">
                             <input type="hidden" name="code" id="lang-code" value="">
                             <div class="language-selection language-selection-two">
                                 @if ($userCurrentLang->id)
                                     <div class="current-language">
                                         <img src="{{ asset('assets/front/img/flags/' . $userCurrentLang->code . '.png') }}"
                                             alt="{{ $userCurrentLang->name }}" class="img-fluid mx-2"
                                             style="height: 25px;">
                                         <i class="far fa-angle-down"></i>
                                     </div>
                                 @endif
                                 <ul class="language-list" id="language-list">
                                     @foreach ($userLangs as $userLang)
                                         <li>
                                             <a href="javascript:void(0)" data-value="{{ $userLang->code }}"
                                                 onclick="changeLanguage('{{ $userLang->code }}')">
                                                 <img src="{{ asset('assets/front/img/flags/' . $userLang->code . '.png') }}"
                                                     alt="{{ $userLang->name }}"
                                                     title="{{ convertUtf8($userLang->name) }}" class="img-fluid mx-1"
                                                     style=" height: 20px;">
                                                 {{ $userLang->name }}
                                             </a>
                                         </li>
                                     @endforeach
                                 </ul>
                             </div>
                         </form>
                     </li>
                     <!-- language selection -->

                     <li class="d-xl-none">
                         <div class="navbar-toggler">
                             <span></span>
                             <span></span>
                             <span></span>
                         </div>
                     </li>

                     <div class="info nav-push-item">
                         @if (in_array('Ecommerce', $packagePermissions) ||
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
                 </ul>
             </div>
         </div>
     </div>

     <!-- Mobile Menu -->
     <div class="slide-panel mobile-slide-panel">
         <div class="panel-overlay"></div>
         <div class="panel-inner">
             <!-- Pushed Item -->
             <div class="nav-pushed-item"></div>

             <nav class="mobile-menu">
                 <ul class="primary-menu">
                     @php
                         $links = json_decode($userMenus, true);
                     @endphp
                     @foreach ($links as $link)
                         @php
                             $href = getUserHref($link);
                         @endphp
                         @if (!array_key_exists('children', $link))
                             <li><a href="{{ $href }}" target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                             </li>
                         @else
                             <li>
                                 <a href="{{ $href }}" target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                                 <ul class="submenu">
                                     @foreach ($link['children'] as $level2)
                                         @php
                                             $l2Href = getUserHref($level2);
                                         @endphp
                                         <li><a href="{{ $l2Href }}"
                                                 target="{{ $level2['target'] }}">{{ $level2['text'] }}</a>
                                         </li>
                                     @endforeach
                                 </ul>
                             </li>
                         @endif
                     @endforeach
                     @if (in_array('Request a Quote', $packagePermissions))
                         @if ($userBs->is_quote)
                             <li class=" d-block d-md-none"><a href="{{ route('front.user.quote', getParam()) }}"
                                     target="{{ $link['target'] }}">{{ $keywords['Request_A_Quote'] ?? 'Request A Quote' }}</a>
                             </li>
                         @endif
                     @endif
                 </ul>
             </nav>
             <a href="#" class="panel-close">
                 <i class="fal fa-times"></i>
             </a>
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
         z-index: 99;
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
 <!--====== End Header ======-->
