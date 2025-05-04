<header class="header-area header-2 " data-aos="slide-down">

    <!-- Start Header -->
    <div class="header-top">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                    <div class="header-top-left">
                        <ul>
                            <li>
                                <a href="tel:{{ $userBs->phone }}">
                                    <i class="fas fa-phone-alt"></i> {{ $userBs->phone }}
                                </a>
                            </li>
                            <li>
                                <a href="mailto:{{ $userBs->email }}">
                                    <i class="fas fa-envelope"></i> {{ $userBs->email }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                    <div class="header-top
                        @if (!empty($userBs->social_links))
                            <ul class="social">
                                @foreach ($userBs->social_links as $social)
                                    <li>
                                        <a href="{{ $social->url }}" target="_blank">
                                            <i class="{{ $social->icon }}"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-3 col-sm-3 col-3">
                    <div class="logo">
                        @if (!empty($logo))
                            <a href="{{ route('front.user.detail.view', getParam()) }}">
                                <img style="max-height: 50px; width: auto;" src="{{ asset($logo) }}">
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-9 col-md-9 col-sm-9 col-9">
                    <nav class="navbar navbar-expand-lg">
                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                            data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                            aria-label="Toggle navigation">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNavDropdown">
                            <ul id="mainMenu" class="navbar-nav mobile-item mx-auto">
                                @foreach ($userMenus as $menu)
                                    @php
                                        $href = $menu->is_external ? $menu->url : url($menu->url);
                                    @endphp

                                    @if ($menu->children->isEmpty())
                                        <li class="nav-item">
                                            <a class="nav-link"
                                                href="{{ $href }}"
                                                target="{{ $menu->is_external ? '_blank' : '_self' }}">
                                                {{ $menu->label }}
                                            </a>
                                        </li>
                                    @else
                                        <li class="nav-item">
                                            <a class="nav-link toggle"
                                                href="{{ $href }}"
                                                target="{{ $menu->is_external ? '_blank' : '_self' }}">
                                                {{ $menu->label }}
                                            </a>
                                            <ul class="menu-dropdown">
                                                @foreach ($menu->children as $child)
                                                    @php
                                                        $childHref = $child->is_external ? $child->url : url($child->url);
                                                    @endphp
                                                    <li class="nav-item">
                                                        <a class="nav-link"
                                                            href="{{ $childHref }}"
                                                            target="{{ $child->is_external ? '_blank' : '_self' }}">
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
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Header -->
</header>
