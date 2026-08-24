<div class="main-header">
    <!-- Logo Header - Styled to match Sidebar perfectly -->
    <div class="logo-header" @if (request()->cookie('admin-theme') == 'dark') data-background-color="dark2" @endif>
        @php
            $homeUrl = '/';
            try {
                if (Route::has('front.index')) {
                    $homeUrl = route('front.index');
                }
            } catch (\Exception $e) {
                $homeUrl = '/';
            }
        @endphp
        
        {{-- Brand Logo --}}
        <a href="{{ $homeUrl }}" class="logo d-flex align-items-center" target="_blank">
            <img src="{{ asset('assets/front/img/' . $bs->logo) }}" alt="navbar brand" class="navbar-brand">
        </a>
        
        {{-- Sidebar Toggles - Refined Layout --}}
        <div class="header-toggles d-flex align-items-center">
            {{-- Primary Desktop Sidebar Toggle --}}
            <button class="btn btn-toggle toggle-sidebar d-none d-lg-flex" type="button">
                <i class="icon-menu"></i>
            </button>

            {{-- Mobile Sidebar Toggle --}}
            <button class="navbar-toggler sidenav-toggler d-lg-none" type="button" data-toggle="collapse"
                data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
                <i class="icon-menu"></i>
            </button>
            
            {{-- Mobile Topbar Toggle --}}
            <button class="topbar-toggler more d-lg-none ml-2" type="button">
                <i class="icon-options-vertical"></i>
            </button>
        </div>
    </div>
    <!-- End Logo Header -->

    <!-- Navbar Header -->
    <nav class="navbar navbar-header navbar-expand-lg"
        @if (request()->cookie('admin-theme') == 'dark') data-background-color="dark" @endif>

        <div class="container-fluid">
            <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">

                {{-- Admin notifications bell --}}
                @auth('admin')
                    @php
                        $adminNotificationItems = collect();
                        $adminUnreadCount = 0;
                        try {
                            $adminUser = Auth::guard('admin')->user();
                            if ($adminUser && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                                $adminUnreadCount = $adminUser->unreadNotifications()->count();
                                $adminNotificationItems = $adminUser->notifications()->latest()->limit(8)->get();
                            }
                        } catch (\Throwable $e) {
                            $adminNotificationItems = collect();
                            $adminUnreadCount = 0;
                        }
                    @endphp
                    <li class="nav-item dropdown hidden-caret">
                        <a class="nav-link dropdown-toggle position-relative" data-toggle="dropdown" href="#" aria-expanded="false" title="{{ __('Notifications') }}">
                            <i class="fas fa-bell"></i>
                            @if ($adminUnreadCount > 0)
                                <span class="badge badge-danger badge-counter position-absolute" style="top: 2px; right: 0; font-size: 0.65rem;">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-right animated fadeIn" style="min-width: 320px; max-width: 380px;">
                            <li class="dropdown-header px-3 py-2 font-weight-bold border-bottom">{{ __('Notifications') }}</li>
                            @forelse ($adminNotificationItems as $notification)
                                @php
                                    $data = is_array($notification->data) ? $notification->data : [];
                                    $itemTitle = $data['title'] ?? ($data['message'] ?? __('Notification'));
                                    $itemUrl = $data['url'] ?? (isset($data['batch_id']) ? route('admin.register.user.import-batch', $data['batch_id']) : '#');
                                @endphp
                                <li>
                                    <a class="dropdown-item py-2 {{ $notification->read_at ? '' : 'font-weight-bold' }}" href="{{ $itemUrl }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="pr-2">{{ $itemTitle }}</span>
                                            <small class="text-muted text-nowrap">{{ $notification->created_at?->diffForHumans() }}</small>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <li class="dropdown-item text-muted py-3 text-center">{{ __('No notifications') }}</li>
                            @endforelse
                            @if ($adminNotificationItems->isNotEmpty())
                                <li class="dropdown-divider mb-0"></li>
                                <li class="px-3 py-2">
                                    <form action="{{ route('admin.register.user.notifications.read-all') }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link btn-block p-0">{{ __('Mark all as read') }}</button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endauth
                
                {{-- Language Dropdown --}}
                @if(isset($adminLanguages) && $adminLanguages->isNotEmpty())
                <li class="nav-item dropdown hidden-caret">
                    <a class="dropdown-toggle nav-link" data-toggle="dropdown" href="#" aria-expanded="false" title="{{ __('Language') }}">
                        <i class="fas fa-globe"></i>
                        <span class="d-none d-md-inline ml-1 text-uppercase">{{ app()->getLocale() }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right animated fadeIn">
                        @foreach($adminLanguages as $lang)
                        <li>
                            <a class="dropdown-item {{ app()->getLocale() === $lang->code ? 'active font-weight-bold' : '' }}" href="{{ request()->fullUrlWithQuery(['language' => $lang->code]) }}">
                                {{ $lang->name }}
                                @if(app()->getLocale() === $lang->code)
                                <i class="fas fa-check float-right"></i>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </li>
                @endif

                {{-- Theme Switcher --}}
                <li class="nav-item px-2">
                    <form action="{{ route('admin.theme.change') }}" class="m-0 form-inline" id="adminThemeForm">
                        <div class="form-group p-0">
                            <div class="selectgroup selectgroup-secondary selectgroup-pills">
                                <label class="selectgroup-item mb-0">
                                    <input type="radio" name="theme" value="light" class="selectgroup-input"
                                        {{ empty(request()->cookie('admin-theme')) || request()->cookie('admin-theme') == 'light' ? 'checked' : '' }}
                                        onchange="document.getElementById('adminThemeForm').submit();">
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-sun"></i></span>
                                </label>
                                <label class="selectgroup-item mb-0">
                                    <input type="radio" name="theme" value="dark" class="selectgroup-input"
                                        {{ request()->cookie('admin-theme') == 'dark' ? 'checked' : '' }}
                                        onchange="document.getElementById('adminThemeForm').submit();">
                                    <span class="selectgroup-button selectgroup-button-icon"><i class="fa fa-moon"></i></span>
                                </label>
                            </div>
                        </div>
                    </form>
                </li>

                {{-- User Profile Dropdown --}}
                <li class="nav-item dropdown hidden-caret profile-dropdown-wrapper">
                    @php
                        $filePath = base_path('version.json');
                        $version = null;
                        if (File::exists($filePath)) {
                            $content = File::get($filePath);
                            $versionData = json_decode($content, true);
                            $version = $versionData['version'] ?? null;
                        }
                    @endphp
                    
                    <a class="dropdown-toggle nav-link p-0" data-toggle="dropdown" href="#" aria-expanded="false">
                        <div class="logo-header-user d-flex align-items-center">
                            <div class="avatar-sm">
                                @if (!empty(Auth::guard('admin')->user()->image))
                                    <img src="{{ asset('assets/admin/img/propics/' . Auth::guard('admin')->user()->image) }}"
                                        alt="..." class="avatar-img rounded-circle border border-2 border-white shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                                @else
                                    <img src="{{ asset('assets/admin/img/propics/blank_user.jpg') }}" alt="..."
                                        class="avatar-img rounded-circle border border-2 border-white shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                                @endif
                            </div>
                            <div class="info d-none d-md-block">
                                <span class="user-name font-weight-bold d-block">
                                    {{ Auth::guard('admin')->user()->first_name }}
                                </span>
                                <span class="user-level text-muted d-flex align-items-center" style="font-size: 0.65rem;">
                                    @isset($version)
                                        {{ __('Version') }} {{ $version }}
                                    @else
                                        {{ __('Admin') }}
                                    @endisset
                                    <i class="fas fa-caret-down ml-1" style="font-size: 0.5rem; opacity: 0.5;"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                    
                    <div class="dropdown-menu dropdown-menu-right shadow-sm border animated fadeIn" style="min-width: 200px;">
                        <div class="px-3 py-2 border-bottom d-md-none">
                            <span class="font-weight-bold d-block">{{ Auth::guard('admin')->user()->first_name }}</span>
                            <small class="text-muted">{{ Auth::guard('admin')->user()->email }}</small>
                        </div>
                        <a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('admin.editProfile') }}">
                            <i data-lucide="user-cog" class="mr-2" style="width: 14px; height: 14px;"></i>
                            {{ __('Edit Profile') }}
                        </a>
                        <a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('admin.changePass') }}">
                            <i data-lucide="key" class="mr-2" style="width: 14px; height: 14px;"></i>
                            {{ __('Change Password') }}
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item py-2 d-flex align-items-center text-danger" href="{{ route('admin.logout') }}">
                            <i data-lucide="log-out" class="mr-2" style="width: 14px; height: 14px;"></i>
                            {{ __('Logout') }}
                        </a>
                    </div>
                </li>
                
            </ul>
        </div>
    </nav>
    <!-- End Navbar -->
</div>
