<div class="main-header">
    <!-- Logo Header -->
    <div class="logo-header" @if(request()->cookie('user-theme') == 'dark') data-background-color="dark2" @endif>
        <a href="{{route('front.index')}}" class="logo" target="_blank">
            @if(!empty($logo))
        <img src="{{!empty($logo) ? $logo : 'logo'}}" alt="LOGO" class="navbar-brand">
            @else
            {{__('logo')}}
            @endif
        </a>
        <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse"
            data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon">
        <i class="icon-menu"></i>
        </span>
        </button>
        <button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
        <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
            <i class="icon-menu"></i>
            </button>
        </div>
    </div>
    <!-- End Logo Header -->
    <!-- Navbar Header -->
    <nav style="float:left;" class="navbar navbar-header navbar-expand-lg" @if(request()->cookie('user-theme') == 'dark') data-background-color="dark" @endif>
        <div class="container-fluid">
            <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
            <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center gap-2 pe-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center">
                            @if (!empty(Auth::user()->photo))
                                <img src="{{asset('assets/front/img/user/'.Auth::user()->photo)}}" alt="Profile Picture"
                                    class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                                    style="width: 32px; height: 32px;">
                                    {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                                </div>
                            @endif
                            <i class="bi bi-chevron-down ms-2 text-muted small"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end border-0 shadow-sm"
                        style="width: 280px;">
                        <div class="p-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                @if (!empty(Auth::user()->photo))
                                    <img src="{{asset('assets/front/img/user/'.Auth::user()->photo)}}" alt="Profile Picture"
                                        class="rounded-circle" width="48" height="48" style="object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                                        style="width: 48px; height: 48px;">
                                        {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="overflow-hidden">
                                    <p class="mb-0 text-truncate fw-medium">{{Auth::user()->first_name}} {{Auth::user()->last_name}}</p>
                                    <p class="mb-0 text-truncate text-muted small">{{Auth::user()->email}}</p>
                                </div>
                            </div>
                        </div>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{route('user-profile-update')}}">
                            <i class="bi bi-gear"></i>
                            {{__('Account Settings')}}
                        </a>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{route('user.language.index')}}">
                        <i class="bi bi-globe"></i>
                            {{__('Languages manage')}}
                        </a>

                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="{{route('user-logout')}}">
                            <i class="bi bi-box-arrow-right"></i>
                            {{__('Logout')}}
                        </a>
                    </div>
                </li>

                <!-- Notifications -->
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-bell"></i>
                    </a>
                </li>

                <!-- Language Dropdown -->
@php
    use Illuminate\Support\Facades\Auth;
    use App\Models\User\Language;

    // Get default language from the database
    $defaultLanguage = Language::where('is_default', 1)->first();

    // Get all languages for the authenticated user
    $userLanguages = Language::where('user_id', Auth::id())->get();

    // Get the currently selected language from the query string or fallback to the default
    $selectedLanguage = Language::where('code', request()->get('language', $defaultLanguage->code ?? 'en'))->first();

    // Ensure a valid language ID is always available
    $selectedLanguageId = $selectedLanguage ? $selectedLanguage->id : ($defaultLanguage ? $defaultLanguage->id : null);
@endphp

<!-- Language Dropdown in Navbar -->
<li class="nav-item dropdown submenu">
    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-globe"></i>
        {{ $selectedLanguage->name ?? 'English' }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
        @foreach($userLanguages as $lang)
            <li>
                <a class="dropdown-item lang-switch"
                   data-lang="{{ $lang->code }}"
                   data-lang-id="{{ $lang->id }}"
                   href="{{ request()->fullUrlWithQuery(['language' => $lang->code]) }}">
                    {{ $lang->name }}
                </a>
            </li>
        @endforeach
    </ul>
</li>


                <!--// Language Dropdown -->

            </ul>
        </div>
    </nav>
    <!-- End Navbar -->
</div>
