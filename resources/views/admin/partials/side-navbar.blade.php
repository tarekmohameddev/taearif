@php
    $default = \App\Models\Language::where('is_default', 1)->first();
    $admin = Auth::guard('admin')->user();
    if (!empty($admin->role)) {
        $permissions = $admin->role->permissions;
        $permissions = is_array($permissions) ? $permissions : (json_decode($permissions, true) ?: []);
    }
@endphp

<div class="sidebar sidebar-style-2" @if (request()->cookie('admin-theme') == 'dark') data-background-color="dark2" @endif>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <div class="user">
                <div class="avatar-sm float-left mr-2">
                    @if (!empty(Auth::guard('admin')->user()->image))
                        <img src="{{ asset('assets/admin/img/propics/' . Auth::guard('admin')->user()->image) }}"
                            alt="..." class="avatar-img rounded">
                    @else
                        <img src="{{ asset('assets/admin/img/propics/blank_user.jpg') }}" alt="..."
                            class="avatar-img rounded">
                    @endif
                </div>
                <div class="info">
                    <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                        <span>
                            {{ Auth::guard('admin')->user()->first_name }}
                             @php
                                    $filePath = base_path('version.json');
                                    if (File::exists($filePath)) {
                                        // Get the contents of the file
                                        $content = File::get($filePath);
                                        $versionData = json_decode($content, true);
                                        $version = $versionData['version'] ?? null;
                                    }
                                @endphp
                            <span class="user-level">

                               @isset($version)
                                {{ __('Version') }} -  {{$version}}
                               @else
                               {{ __('Admin') }}
                               @endisset
                            </span>
                            <span class="caret"></span>
                        </span>
                    </a>
                    <div class="clearfix"></div>

                    <div class="collapse in" id="collapseExample">
                        <ul class="nav">
                            <li>
                                <a href="{{ route('admin.editProfile') }}">
                                    <span class="link-collapse">{{ __('Edit Profile') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.changePass') }}">
                                    <span class="link-collapse">{{ __('Change Password') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.logout') }}">
                                    <span class="link-collapse">{{ __('Logout') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <ul class="nav nav-primary">

                <div class="row mb-2">
                    <div class="col-12">
                        <form action="">
                            <div class="form-group py-0">
                                <input name="term" type="text" class="form-control sidebar-search ltr"
                                    value="" placeholder="{{ __('Search Menu Here') }}...">
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Dashboard --}}
                <li class="nav-item @if (request()->path() == 'admin/dashboard') active @endif">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="la flaticon-paint-palette"></i>
                        <p>{{ __('Dashboard') }}</p>
                    </a>
                </li>
                <!-- //admin.isthara.index -->
                @if (empty($admin->role) || (!empty($permissions) && in_array('Isthara Consultations', $permissions)))
                    <li class="nav-item @if (request()->path() == 'admin/isthara') active @endif">
                        <a href="{{ route('admin.isthara.index') }}">
                            <i class="la flaticon-paint-palette"></i>
                            <p>{{ __('Consultation Bookings') }}</p>
                        </a>
                    </li>
                @endif

                {{-- Package --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Packages', $permissions)))
                    <li
                        class="nav-item
                    @if (request()->path() == 'admin/package/settings') active
                    @elseif(request()->path() == 'admin/packages') active
                    @elseif(request()->path() == 'admin/package/features') active
                    @elseif(request()->is('admin/package/*/edit')) active
                    @elseif(request()->path() == 'admin/coupon') active
                    @elseif(request()->routeIs('admin.coupon.edit')) active @endif">
                        <a data-toggle="collapse" href="#packageManagement">
                            <i class="fas fa-receipt"></i>
                            <p>{{ __('Package Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'admin/package/settings') show
                        @elseif(request()->path() == 'admin/packages') show
                        @elseif(request()->path() == 'admin/package/features') show
                        @elseif(request()->is('admin/package/*/edit')) show
                        @elseif(request()->path() == 'admin/coupon') show
                        @elseif(request()->routeIs('admin.coupon.edit')) show @endif"
                            id="packageManagement">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'admin/package/settings') active @endif">
                                    <a href="{{ route('admin.package.settings') }}">
                                        <span class="sub-item">{{ __('Settings') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="@if (request()->path() == 'admin/coupon') active
                            @elseif(request()->routeIs('admin.coupon.edit')) active @endif">
                                    <a href="{{ route('admin.coupon.index') }}">
                                        <span class="sub-item">Coupons</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/package/features') active @endif">
                                    <a href="{{ route('admin.package.features') }}">
                                        <span class="sub-item">{{ __('Package Features') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="@if (request()->path() == 'admin/packages') active
                                @elseif(request()->is('admin/package/*/edit')) active @endif">
                                    <a href="{{ route('admin.package.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Packages') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif


                @if (empty($admin->role) || (!empty($permissions) && in_array('Payment Log', $permissions)))
                    <li class="nav-item
                    @if (request()->path() == 'admin/payment-log') active @endif">
                        <a href="{{ route('admin.payment-log.index') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <p>{{ __('Payment Log') }}</p>
                        </a>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('app Request', $permissions)))
                    <li class="nav-item @if (request()->path() == 'admin/app-request') active @endif">
                        <a href="{{ route('admin.app.request.index') }}">
                            <i class="fab fa-app"></i>
                            <p>{{ __('App Requests') }}</p>
                        </a>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('Credit Management', $permissions)))
                    <li class="nav-item submenu @if (request()->is('admin/credit-transactions*') || request()->is('admin/credit-management*')) active @endif">
                        <a data-toggle="collapse" href="#creditManagement">
                            <i class="fas fa-coins"></i>
                            <p>{{ __('Credit Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse @if (request()->is('admin/credit-transactions*') || request()->is('admin/credit-management*')) show @endif" id="creditManagement">
                            <ul class="nav nav-collapse">
                                <li class="nav-item @if (request()->path() == 'admin/credit-transactions') active @endif">
                                    <a href="{{ route('admin.credit.transactions.index') }}">
                                        <span class="sub-item">{{ __('Credit Transactions') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item @if (request()->is('admin/credit-management*')) active @endif">
                                    <a href="{{ route('admin.credit-management.index') }}">
                                        <span class="sub-item">{{ __('Credit Management') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                <!-- Affiliate -->

                @if (empty($admin->role) || (!empty($permissions) && in_array('Affiliate Management', $permissions)))
                    <li class="nav-item
                        @if (request()->path() == 'admin/affiliates') active
                        @elseif(request()->is('admin/affiliates/*/edit')) active
                        @elseif(request()->is('admin/affiliates/payments')) active
                        @elseif(request()->is('admin/affiliates/payment-history/*')) active
                        @endif">
                        <a data-toggle="collapse" href="#affiliateManagement">
                            <i class="la flaticon-users"></i>
                            <p>{{ __('Affiliate Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                            @if (request()->path() == 'admin/affiliates') show
                            @elseif(request()->is('admin/affiliates/*/edit')) show
                            @elseif(request()->is('admin/affiliates/payments')) show
                            @elseif(request()->is('admin/affiliates/payment-history/*')) show
                            @endif"
                            id="affiliateManagement">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'admin/affiliates') active @endif">
                                    <a href="{{ route('admin.affiliates.index') }}">
                                        <span class="sub-item">{{ __('All Affiliates') }}</span>
                                    </a>
                                </li>
                            </ul>

                        </div>
                    </li>
                @endif

                {{-- User Management --}}

                {{-- App Management --}}


                {{-- Settings --}}


                @if (empty($admin->role) || (!empty($permissions) && in_array('Custom Domains', $permissions)))
                    <li
                        class="nav-item
                        @if (request()->path() == 'admin/domains') active
                        @elseif(request()->path() == 'admin/domain/texts') active @endif">
                        <a data-toggle="collapse" href="#customDomains">
                            <i class="fas fa-link"></i>
                            <p>{{ __('Custom Domains') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                            @if (request()->path() == 'admin/domains') show
                            @elseif(request()->path() == 'admin/domain/texts') show @endif"
                            id="customDomains">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'admin/domain/texts') active @endif">
                                    <a href="{{ route('admin.custom-domain.texts') }}">
                                        <span class="sub-item">{{ __('Request Page Texts') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/domains' && empty(request()->input('type'))) active @endif">
                                    <a href="{{ route('admin.custom-domain.index') }}">
                                        <span class="sub-item">{{ __('All Requests') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/domains' && request()->input('type') == 'pending') active @endif">
                                    <a href="{{ route('admin.custom-domain.index', ['type' => 'pending']) }}">
                                        <span class="sub-item">{{ __('Pending Requests') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/domains' && request()->input('type') == 'connected') active @endif">
                                    <a href="{{ route('admin.custom-domain.index', ['type' => 'connected']) }}">
                                        <span class="sub-item">{{ __('Connected Requests') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/domains' && request()->input('type') == 'rejected') active @endif">
                                    <a href="{{ route('admin.custom-domain.index', ['type' => 'rejected']) }}">
                                        <span class="sub-item">{{ __('Rejected Requests') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('Subdomains', $permissions)))
                    <li class="nav-item
                        @if (request()->path() == 'admin/subdomains') active @endif">
                        <a data-toggle="collapse" href="#subDomains">
                            <i class="fas fa-link"></i>
                            <p>{{ __('Subdomains') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                            @if (request()->path() == 'admin/subdomains') show @endif"
                            id="subDomains">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'admin/subdomains' && empty(request()->input('type'))) active @endif">
                                    <a href="{{ route('admin.subdomain.index') }}">
                                        <span class="sub-item">{{ __('All Subdomains') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/subdomains' && request()->input('type') == 'pending') active @endif">
                                    <a href="{{ route('admin.subdomain.index', ['type' => 'pending']) }}">
                                        <span class="sub-item">{{ __('Pending Subdomains') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'admin/subdomains' && request()->input('type') == 'connected') active @endif">
                                    <a href="{{ route('admin.subdomain.index', ['type' => 'connected']) }}">
                                        <span class="sub-item">{{ __('Connected Subdomains') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif


                {{-- Registered Users --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Registered Users', $permissions)))
                    <li
                        class="nav-item
                    @if (request()->path() == 'admin/register/users') active
                    @elseif(request()->is('admin/register/user/details/*')) active
                    @elseif(request()->routeIs('admin.register.user.vcards')) active
                    @elseif (request()->routeIs('admin.register.user.changePass')) active @endif">

                        <a data-toggle="collapse" href="#users">
                            <i class="la flaticon-users"></i>
                            <p>{{ __('User Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse @if (request()->path() == 'admin/register/users') show
                    @elseif(request()->is('admin/register/user/details/*')) show
                    @elseif(request()->routeIs('admin.register.user.vcards')) show
                    @elseif (request()->routeIs('admin.register.user.changePass')) show @endif"
                            id="users">
                            <ul class="nav nav-collapse">
                                <li class=" @if (request()->routeIs('admin.register.user')) active @endif">
                                    <a href="{{ route('admin.register.user') }}">
                                        <span class="sub-item">{{ __('Registered Users') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                {{-- communication --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Communication', $permissions)))
                    <li class="nav-item
                        @if (request()->is('admin/communication/whatsapp')) active
                        @elseif(request()->is('admin/communication/email')) active
                        @elseif(request()->is('admin/communication/*')) active
                        @elseif(request()->is('admin/communication/whatsapp-templates*')) active
                        @elseif(request()->is('admin/communication/email-templates*')) active @endif">
                        <a data-toggle="collapse" href="#communication">
                            <i class="fas fa-comments"></i>
                            <p>التواصل</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                            @if (request()->is('admin/communication/whatsapp')) show
                            @elseif(request()->is('admin/communication/email')) show
                            @elseif(request()->is('admin/communication/*')) show
                            @elseif(request()->is('admin/communication/whatsapp-templates*')) show
                            @elseif(request()->is('admin/communication/email-templates*')) show @endif"
                            id="communication">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->is('admin/communication/whatsapp')) active @endif">
                                    <a href="{{ route('admin.communication.whatsapp') }}">
                                        <span class="sub-item">واتس اب</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/communication/whatsapp-templates*')) active @endif">
                                    <a href="{{ route('admin.whatsapp-templates.index') }}">
                                        <span class="sub-item">قوالب الواتس اب</span>
                                    </a>
                                </li>      

                                <li class="@if (request()->is('admin/communication/email')) active @endif">
                                    <a href="{{ route('admin.communication.email') }}">
                                        <span class="sub-item">البريد الإلكتروني</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/communication/email-templates*')) active @endif">
                                    <a href="{{ route('admin.email-templates.index') }}">
                                        <span class="sub-item">قوالب البريد الإلكتروني</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif


            </ul>
        </div>
    </div>
</div>
