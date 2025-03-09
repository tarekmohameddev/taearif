 <header class="header-area header-2 @if (!request()->routeIs('front.user.detail.view', getParam())) header-static @endif" data-aos="slide-down">
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
                         <img style="max-height: 50px; width: auto;" src="{{ asset('assets/front/img/user/' . $userBs->logo) }}">
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
                         <img style="max-height: 50px; width: auto;" src="{{ asset('assets/front/img/user/' . $userBs->logo) }}">
                     </a>
                 @endif
                 <!-- Navigation items -->
                 <div class="collapse navbar-collapse">
                     <ul id="mainMenu" class="navbar-nav mobile-item text-white">
                         @php
                             $links = json_decode($userMenus, true);
                         @endphp
                         @foreach ($links as $link)
                             @php
                                 $href = getUserHref($link);
                             @endphp

                             @if (!array_key_exists('children', $link))
                                 <li class="nav-item"> <a class="nav-link" href="{{ $href }}"
                                         target="{{ $link['target'] }}"> {{ $link['text'] }} </a>
                                 </li>
                             @else
                                 <li class="nav-item">
                                     <a class="nav-link toggle" href="{{ $href }}"
                                         target="{{ $link['target'] }}">{{ $link['text'] }}</a>
                                     <ul class="menu-dropdown">
                                         @foreach ($link['children'] as $level2)
                                             @php
                                                 $l2Href = getUserHref($level2);
                                             @endphp
                                             <li class="nav-item">
                                                 <a class="nav-link" href="{{ $l2Href }}"
                                                     target="{{ $level2['target'] }}"> {{ $level2['text'] }} </a>
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
                                 <select class="nice-select" name="code" id="lang_code"
                                     onchange="this.form.submit()">
                                     @foreach ($userLangs as $userLang)
                                         <option {{ $userCurrentLang->id == $userLang->id ? 'selected' : '' }}
                                             value="{{ $userLang->code }}">
                                             {{ convertUtf8($userLang->name) }}</option>
                                     @endforeach
                                 </select>
                             </form>


                         </div>
                     </div>
                     <div class="item">
                         <div class="dropdown">
                             <button class="btn btn-primary btn-sm dropdown-toggle" type="button"
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

                                                 {{ $keywords['Signup'] ?? __('Singup') }}
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
 </header>
 <!-- Header-area end -->
