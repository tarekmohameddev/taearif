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
            <ul class="nav nav-primary" style="margin-top: 10px !important;">

                <li class="nav-item px-3 mb-1">
                    <form action="" class="sidebar-search-container">
                        <div class="position-relative">
                            <span class="position-absolute" style="{{ !empty($admin_rtl) ? 'right' : 'left' }}: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                                <i data-lucide="search" style="width: 16px;"></i>
                            </span>
                            <input name="term" type="text" class="form-control sidebar-search ltr w-100"
                                value="" placeholder="{{ __('Search Menu') }}...">
                        </div>
                    </form>
                </li>

                {{-- ========== NO DROPDOWN (top) ========== --}}
                {{-- Dashboard --}}
                <li class="nav-item @if (request()->path() == 'admin/dashboard') active @endif">
                    <a href="{{ route('admin.dashboard') }}">
                        <i data-lucide="layout-dashboard"></i>
                        <p>{{ __('Dashboard') }}</p>
                    </a>
                </li>
                <!-- //admin.isthara.index -->
                @if (empty($admin->role) || (!empty($permissions) && in_array('Isthara Consultations', $permissions)))
                    <li class="nav-item @if (request()->path() == 'admin/isthara') active @endif">
                        <a href="{{ route('admin.isthara.index') }}">
                            <i data-lucide="calendar-check"></i>
                            <p>{{ __('Consultation Bookings') }}</p>
                        </a>
                    </li>
                @endif

                {{-- Themes Management --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Packages', $permissions)))
                    <li class="nav-item @if (request()->is('admin/themes*')) active @endif">
                        <a href="{{ route('admin.themes.index') }}">
                            <i data-lucide="palette"></i>
                            <p>{{ __('Themes Management') }}</p>
                        </a>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('Payment Log', $permissions)))
                    <li class="nav-item
                    @if (request()->path() == 'admin/payment-log') active @endif">
                        <a href="{{ route('admin.payment-log.index') }}">
                            <i data-lucide="file-text"></i>
                            <p>{{ __('Payment Log') }}</p>
                        </a>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('app Request', $permissions)))
                    <li class="nav-item @if (request()->path() == 'admin/app-request') active @endif">
                        <a href="{{ route('admin.app.request.index') }}">
                            <i data-lucide="smartphone"></i>
                            <p>{{ __('App Requests') }}</p>
                        </a>
                    </li>
                @endif

                @if (empty($admin->role) || (!empty($permissions) && in_array('app Request', $permissions)))
                    <li class="nav-item @if (request()->is('admin/marketplace-apps*')) active @endif">
                        <a href="{{ route('admin.marketplace-apps.index') }}">
                            <i data-lucide="shopping-bag"></i>
                            <p>{{ __('Marketplace Apps') }}</p>
                        </a>
                    </li>
                @endif

                {{-- Settings / Sidebar Items --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Settings', $permissions)))
                    <li class="nav-item @if (request()->is('admin/sidebar-items*') || request()->is('admin/sidebar-item/*')) active @endif">
                        <a href="{{ route('admin.sidebar-item.index') }}">
                            <i data-lucide="menu"></i>
                            <p>{{ __('Sidebar Items') }}</p>
                        </a>
                    </li>
                @endif

                {{-- ========== WITH DROPDOWN (bottom) ========== --}}
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
                            <i data-lucide="package"></i>
                            <p>{{ __('Package Management') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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
                                        <span class="sub-item">{{ __('Coupons') }}</span>
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

                @if (empty($admin->role) || (!empty($permissions) && in_array('Credit Management', $permissions)))
                    <li class="nav-item @if (request()->is('admin/credit-transactions*') || request()->is('admin/credit-management*') || request()->is('admin/whatsapp-addons*') || request()->is('whatsapp-addons*') || request()->is('admin/whatsapp-addon-plans*') || request()->is('admin/employee-addon-plans*')) active @endif">
                        <a data-toggle="collapse" href="#creditManagement">
                            <i data-lucide="coins"></i>
                            <p>{{ __('Credit Management') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
                        </a>
                        <div class="collapse @if (request()->is('admin/credit-transactions*') || request()->is('admin/credit-management*') || request()->is('admin/whatsapp-addons*') || request()->is('whatsapp-addons*') || request()->is('admin/whatsapp-addon-plans*') || request()->is('admin/employee-addon-plans*')) show @endif" id="creditManagement">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'admin/credit-transactions') active @endif">
                                    <a href="{{ route('admin.credit.transactions.index') }}">
                                        <span class="sub-item">{{ __('Credit Transactions') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/credit-management*') && !request()->is('admin/credit-management/providers*')) active @endif">
                                    <a href="{{ route('admin.credit-management.index') }}">
                                        <span class="sub-item">{{ __('Credit Management') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/credit-management/providers*')) active @endif">
                                    <a href="{{ route('admin.credit.providers.index') }}">
                                        <span class="sub-item">{{ __('Communication Providers') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/whatsapp-addons*') || request()->is('whatsapp-addons*')) active @endif">
                                    <a href="{{ url('admin/whatsapp-addons') }}">
                                        <span class="sub-item">{{ __('WhatsApp Add-ons') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/whatsapp-addon-plans*')) active @endif">
                                    <a href="{{ route('admin.whatsapp-addon-plans.index') }}">
                                        <span class="sub-item">{{ __('WhatsApp Addon Plans') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/employee-addon-plans*')) active @endif">
                                    <a href="{{ route('admin.employee-addon-plans.index') }}">
                                        <span class="sub-item">{{ __('Employee Addon Plans') }}</span>
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
                            <i data-lucide="users"></i>
                            <p>{{ __('Affiliate Management') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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

                @if (empty($admin->role) || (!empty($permissions) && in_array('Custom Domains', $permissions)))
                    <li
                        class="nav-item
                        @if (request()->path() == 'admin/domains') active
                        @elseif(request()->path() == 'admin/domain/texts') active @endif">
                        <a data-toggle="collapse" href="#customDomains">
                            <i data-lucide="globe"></i>
                            <p>{{ __('Custom Domains') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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
                            <i data-lucide="link"></i>
                            <p>{{ __('Subdomains') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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
                            <i data-lucide="users"></i>
                            <p>{{ __('User Management') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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

                {{-- Admin Articles --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Admin Articles', $permissions)))
                    <li class="nav-item
                        @if (request()->is('admin/admin-articles*')) active @endif">
                        <a data-toggle="collapse" href="#adminArticles">
                            <i data-lucide="file-plus"></i>
                            <p>{{ __('Admin Articles') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
                        </a>
                        <div class="collapse @if (request()->is('admin/admin-articles*')) show @endif" id="adminArticles">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->routeIs('admin.articles.index') || request()->routeIs('admin.articles.create') || request()->routeIs('admin.articles.edit') || request()->routeIs('admin.articles.show')) active @endif">
                                    <a href="{{ route('admin.articles.index') }}">
                                        <span class="sub-item">{{ __('Articles') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->routeIs('admin.articles.categories.*')) active @endif">
                                    <a href="{{ route('admin.articles.categories.index') }}">
                                        <span class="sub-item">{{ __('Categories') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                {{-- Center of Support --}}
                @if (empty($admin->role) || (!empty($permissions) && in_array('Center of Support', $permissions)))
                    <li class="nav-item @if (request()->is('admin/support-center*')) active @endif">
                        <a data-toggle="collapse" href="#supportCenter">
                            <i data-lucide="help-circle"></i>
                            <p>{{ __('Center of Support') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
                        </a>
                        <div class="collapse @if (request()->is('admin/support-center*')) show @endif" id="supportCenter">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->routeIs('admin.support_center.articles.*')) active @endif">
                                    <a href="{{ route('admin.support_center.articles.index') }}">
                                        <span class="sub-item">{{ __('Support Center Articles') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->routeIs('admin.support_center.categories.*')) active @endif">
                                    <a href="{{ route('admin.support_center.categories.index') }}">
                                        <span class="sub-item">{{ __('Support Center Categories') }}</span>
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
                            <i data-lucide="message-square"></i>
                            <p>{{ __('Communication') }}</p>
                            <i data-lucide="chevron-down" class="caret"></i>
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
                                        <span class="sub-item">{{ __('WhatsApp Templates') }}</span>
                                    </a>
                                </li>

                                <li class="@if (request()->is('admin/communication/email')) active @endif">
                                    <a href="{{ route('admin.communication.email') }}">
                                        <span class="sub-item">{{ __('Email') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->is('admin/communication/email-templates*')) active @endif">
                                    <a href="{{ route('admin.email-templates.index') }}">
                                        <span class="sub-item">{{ __('Email Templates') }}</span>
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
