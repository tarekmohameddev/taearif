@php
    $default = \App\Models\User\Language::where('is_default', 1)
        ->where('user_id', Auth::user()->id)
        ->first();
    $user = Auth::guard('web')->user();
    $package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
    if (!empty($user)) {
        $permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
        $userBs = \App\Models\User\BasicSetting::where('user_id', $user->id)->first();
    }
@endphp
<div class="sidebar" data-theme="{{ request()->cookie('user-theme') == 'dark' ? 'dark' : 'light' }}">
    <div class="sidebar-wrapper">
        <div class="sidebar-content">
            <div class="p-3 border-bottom text-center">
                <h1 class="mb-2 fw-bold" style="color:black !important">{{auth()->user()->username}}</h1>
                <a href="{{route('front.user.detail.view', Auth::user()->username)}}" 
                   target="_blank" 
                   class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye me-1"></i>
                    معاينة الموقع
                </a>
            </div>

            <div class="nav-wrapper">
                <ul class="nav flex-column">
                    <li class="nav-section-title small text-muted px-2 py-2">
                        موقعي
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('user-dashboard') }}" 
                           class="nav-link d-flex align-items-center {{ request()->path() == 'user/dashboard' ? 'active' : '' }}">
                            <i class="bi bi-house"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    </li>

                    <!-- Store Settings Dropdown -->
                    <li class="nav-item">
                        <a class="nav-link d-flex " 
                           data-bs-toggle="collapse" 
                           href="#storeSettings" 
                           role="button" 
                           aria-expanded="false">
                            <i class="bi bi-gear"></i>
                            <span>إعدادات الموقع</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('user.basic_settings.*') || request()->is('user/contact') || request()->is('user/gateways-soon') || request()->is('user/domains') || request()->routeIs('user.language.*') ? 'show' : '' }}" id="storeSettings">
                            <ul class="nav flex-column ms-3 border-start">
                                @if (!is_null($package))
                                    <li class="nav-item">
                                        <a href="{{ route('user.basic_settings.general-settings') }}" 
                                           class="nav-link {{ request()->routeIs('user.basic_settings.general-settings') ? 'active' : '' }}">
                                            <i class="bi bi-gear"></i>
                                            <span>الإعدادات العامة</span>
                                        </a>
                                    </li>
                                    
                                    <li class="nav-item">
                                        <a href="{{ route('user.contact', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->path() == 'user/contact' ? 'active' : '' }}">
                                            <i class="bi bi-envelope"></i>
                                            <span>صفحة الاتصال</span>
                                        </a>
                                    </li>
                                @endif

                                <li class="nav-item">
                                    <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}" 
                                       class="nav-link {{ request()->path() == 'user/gateways-soon' ? 'active' : '' }}">
                                        <i class="bi bi-credit-card"></i>
                                        <span>بوابات الدفع</span>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}" 
                                       class="nav-link">
                                        <i class="bi bi-credit-card-2-front"></i>
                                        <span>شراء خطة</span>
                                    </a>
                                </li>

                                @if (!is_null($package) && !empty($permissions) && in_array('Custom Domain', $permissions))
                                    <li class="nav-item">
                                        <a href="{{ route('user-domains') }}" 
                                           class="nav-link {{ request()->path() == 'user/domains' ? 'active' : '' }}">
                                            <i class="bi bi-globe"></i>
                                            <span>النطاق المخصص</span>
                                        </a>
                                    </li>
                                @endif

                                <li class="nav-item">
                                    <a href="{{ route('user.language.index') }}" 
                                       class="nav-link {{ request()->routeIs('user.language.*') ? 'active' : '' }}">
                                        <i class="bi bi-translate"></i>
                                        <span>إدارة اللغات</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Settings Dropdown -->
                    <li class="nav-item">
                        <a class="nav-link d-flex " 
                           data-bs-toggle="collapse" 
                           href="#settingsDropdown" 
                           role="button" 
                           aria-expanded="false">
                            <i class="bi bi-sliders"></i>
                            <span>الإعدادات</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ request()->is('user/favicon') || request()->is('user/theme/version') || request()->is('user/logo') || request()->is('user/preloader') || request()->routeIs('user.basic_settings.*') || request()->is('user/color') || request()->is('user/css') || request()->is('user/social') || request()->is('user/social/*') || request()->is('user/breadcrumb') ? 'show' : '' }}" id="settingsDropdown">
                            <ul class="nav flex-column ms-3 border-start">
                                <li class="nav-item">
                                    <a href="{{ route('user.theme.version') }}" 
                                       class="nav-link {{ request()->is('user/theme/version') ? 'active' : '' }}">
                                        <i class="bi bi-palette"></i>
                                        <span>القوالب</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.menu_builder.index') . '?language=' . $default->code }}" 
                                       class="nav-link {{ request()->is('user/menu-builder') ? 'active' : '' }}">
                                        <i class="bi bi-list-ul"></i>
                                        <span>منشئ القوائم</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.social.index') }}" 
                                       class="nav-link {{ request()->is('user/social') || request()->is('user/social/*') ? 'active' : '' }}">
                                        <i class="bi bi-facebook"></i>
                                        <span>روابط التواصل الاجتماعي</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Content Management -->
                    <li class="nav-section-title small text-muted px-2 py-2 mt-3">
                        إدارة المحتوى
                    </li>

                    <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#Homepage" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>الصفحة الرئيسية</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->is('user.home_page.hero.slider_version') || request()->is('user.home_page.hero.create_slider') || request()->is('user.home_page.hero.edit_slider') ? 'show' : '' }}" id="Homepage">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.home_page.hero.slider_version') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->is('user.home_page.hero.slider_version') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            <span>{{ __('Hero Section') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.home.page.text.edit', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user.home.page.text.edit') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{ __('Home Sections') }}</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('user.home.page.about', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user.home.page.about') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{ __('About Section') }}</span>
                                        </a>
                                    </li>


                                @if ((!empty($permissions) && in_array('Counter Information', $permissions)) || $userBs->theme == 'home_eleven')
                                    @if ($userBs->theme != 'home_eight')
                                        <li class="nav-item">
                                        <a href="{{ route('user.counter-information.index', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user.counter-information.index') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{ __('Counter Information') }}</span>
                                        </a>
                                    </li>
                                    @endif
                                @endif

                                @if (
                                    $userBs->theme != 'home_three' &&
                                        $userBs->theme != 'home_six' &&
                                        $userBs->theme != 'home_eight' &&
                                        $userBs->theme != 'home_eleven' &&
                                        $userBs->theme != 'home_twelve')
                                    <li class="nav-item">
                                        <a href="{{ route('user.home.page.video', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user.home.page.video') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{__('Video Section') }}</span>
                                        </a>
                                    </li>
                                @endif


                                @if (
                                    $userBs->theme == 'home_one' ||
                                        $userBs->theme == 'home_two' ||
                                        $userBs->theme == 'home_six' ||
                                        $userBs->theme == 'home_three' ||
                                        $userBs->theme == 'home_nine' ||
                                        $userBs->theme == 'home_eleven' ||
                                        $userBs->theme == 'home_eight')
                                    <li class="nav-item">
                                        <a href="{{ route('user.home_page.brand_section', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user/home_page/brand_section') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            @if ($userBs->theme == 'home_eleven')
                                                <span class="sub-item">{{ __('Donor Section') }}</span>
                                            @else
                                                <span class="sub-item">{{ __('Brand Section') }}</span>
                                            @endif
                                        </a>
                                    </li>  
                                @endif

                                @if ($userBs->theme == 'home_one' || $userBs->theme == 'home_three' || $userBs->theme == 'home_nine')
                                    <li class="nav-item">
                                        <a href="{{ route('user.home_page.why_choose_us_section', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user/home_page/why-choose-us') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{__('Why Choose Us Section') }}</span>
                                        </a>
                                    </li>
                                @endif
                                </ul>
                            </div>
                        </li>
                        @if (!empty($permissions) && in_array('Skill', $permissions))
                                    @if (
                                        $userBs->theme != 'home_three' &&
                                            $userBs->theme != 'home_two' &&
                                            $userBs->theme != 'home_ten' &&
                                            $userBs->theme != 'home_nine' &&
                                            $userBs->theme != 'home_eleven' &&
                                            $userBs->theme != 'home_seven' &&
                                            $userBs->theme != 'home_eight')
                                        <li class="nav-item">
                                        <a href="{{ route('user.skill.index', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user/skills') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{__('Skills') }}</span>
                                        </a>
                                    </li>
                                    @endif
                                @endif

                                @if (!empty($permissions) && in_array('Testimonial', $permissions))
                                    @if ($userBs->theme != 'home_eight')
                                        <li class="nav-item">
                                        <a href="{{ route('user.testimonials.index', ['language' => $default->code]) }}" 
                                           class="nav-link {{ request()->is('user.testimonials.index') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{__('Testimonial') }}</span>
                                        </a>
                                    </li>
                                    @endif
                                @endif

    
                    @if (!empty($permissions) && in_array('Team', $permissions))
                        <li class="nav-item">
                            <a href="{{ route('user.team_section') . '?language=' . $default->code }}" 
                               class="nav-link d-flex align-items-center {{ request()->routeIs('user.team_section') || request()->routeIs('user.team_section.create_member') || request()->routeIs('user.team_section.edit_member') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>الفريق</span>
                            </a>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('Service', $permissions))
                        <li class="nav-item">
                            <a href="{{ route('user.services.index') . '?language=' . $default->code }}" 
                               class="nav-link d-flex align-items-center {{ request()->is('user/services') || request()->routeIs('user.service.edit') ? 'active' : '' }}">
                                <i class="bi bi-briefcase"></i>
                                <span>الخدمات</span>
                            </a>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('Portfolio', $permissions))
                        <li class="nav-item">
                            <a href="{{ route('user.portfolio.index') . '?language=' . $default->code }}" 
                               class="nav-link d-flex align-items-center {{ request()->is('user/portfolios') || request()->is('user/portfolio/*/edit') ? 'active' : '' }}">
                                <i class="bi bi-images"></i>
                                <span>معرض الأعمال</span>
                            </a>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('Blog', $permissions))
                        <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#blogManagement" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-pencil-square"></i>
                                <span>المدونة</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('user.blog.*') ? 'show' : '' }}" id="blogManagement">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.blog.category.index') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->path() == 'user/blog-categories' ? 'active' : '' }}">
                                            <i class="bi bi-bookmarks"></i>
                                            <span>التصنيفات</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.blog.index') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->routeIs('user.blog.index') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>المقالات</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('FAQ', $permissions))
                        <li class="nav-item">
                            <a href="{{ route('user.faq_management') . '?language=' . $default->code }}" 
                               class="nav-link d-flex align-items-center {{ request()->routeIs('user.faq_management') ? 'active' : '' }}">
                                <i class="bi bi-question-circle"></i>
                                <span>إدارة الأسئلة الشائعة</span>
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#Footeredit" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-pencil-square"></i>
                                <span>{{ __('Footer') }}</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('user.footer.text') ? 'show' : '' }}" id="Footeredit">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.footer.text') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->path() == '/user/footer' ? 'active' : '' }}">
                                            <i class="bi bi-bookmarks"></i>
                                            <span>{{ __('Footer Logo & Text') }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.footer.quick_links') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->routeIs('user/footer/quick_links') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>{{ __('Quick Links') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @if (!empty($permissions) && in_array('Custom Page', $permissions))
                        <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#customPages" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>الصفحات المخصصة</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->is('user/page/create') || request()->is('user/pages') || request()->is('user/page/*/edit') ? 'show' : '' }}" id="customPages">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.page.create') }}" 
                                           class="nav-link {{ request()->is('user/page/create') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-plus"></i>
                                            <span>إنشاء صفحة</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.page.index') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->is('user/pages') || request()->is('user/page/*/edit') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-text"></i>
                                            <span>الصفحات</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    <!-- Store Management -->
                    <li class="nav-section-title small text-muted px-2 py-2 mt-3">
                        إدارة الموقع
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link d-flex align-items-center">
                            <i class="bi bi-box"></i>
                            <span>المنتجات</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link d-flex align-items-center">
                            <i class="bi bi-cart"></i>
                            <span>الطلبات</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link d-flex align-items-center">
                            <i class="bi bi-people"></i>
                            <span>العملاء</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link d-flex align-items-center">
                            <i class="bi bi-truck"></i>
                            <span>الشحن</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link d-flex align-items-center">
                            <i class="bi bi-tag"></i>
                            <span>الخصومات</span>
                        </a>
                    </li>

                    <!-- Additional Features -->
                    <li class="nav-section-title small text-muted px-2 py-2 mt-3">
                        مميزات اضافية
                        </li>

                    @if (!empty($permissions) && in_array('QR Builder', $permissions))
                        <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#qrCodeBuilder" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-qr-code"></i>
                                <span>منشئ رموز QR</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('user.qrcode') || request()->routeIs('user.qrcode.index') ? 'show' : '' }}" id="qrCodeBuilder">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.qrcode') }}" 
                                           class="nav-link {{ request()->routeIs('user.qrcode') ? 'active' : '' }}">
                                            <i class="bi bi-qr-code"></i>
                                            <span>إنشاء رمز QR</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.qrcode.index') }}" 
                                           class="nav-link {{ request()->routeIs('user.qrcode.index') ? 'active' : '' }}">
                                            <i class="bi bi-qr-code"></i>
                                            <span>رموز QR المحفوظة</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('vCard', $permissions))
                        <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#vCardManagement" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-person"></i>
                                <span>إدارة بطاقات vCard</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->is('user/vcard') || request()->is('user/vcard/create') || request()->is('user/vcard/*/edit') || request()->routeIs('user.vcard.*') ? 'show' : '' }}" id="vCardManagement">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.vcard') }}" 
                                           class="nav-link {{ request()->is('user/vcard') || request()->is('user/vcard/*/edit') || request()->routeIs('user.vcard.*') ? 'active' : '' }}">
                                            <i class="bi bi-person"></i>
                                            <span>بطاقات vCard</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.vcard.create') }}" 
                                           class="nav-link {{ request()->is('user/vcard/create') ? 'active' : '' }}">
                                            <i class="bi bi-person-plus"></i>
                                            <span>إضافة بطاقة vCard</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    @if (!empty($permissions) && in_array('Request a Quote', $permissions))
                        <li class="nav-item">
                            <a class="nav-link d-flex " 
                               data-bs-toggle="collapse" 
                               href="#quoteManagement" 
                               role="button" 
                               aria-expanded="false">
                                <i class="bi bi-chat-quote"></i>
                                <span>إدارة طلبات الأسعار</span>
                                <i class="bi bi-chevron-down small"></i>
                            </a>
                            <div class="collapse {{ request()->is('user/quote/*') || request()->is('user/all/quotes') || request()->is('user/pending/quotes') || request()->is('user/processing/quotes') || request()->is('user/completed/quotes') || request()->is('user/rejected/quotes') ? 'show' : '' }}" id="quoteManagement">
                                <ul class="nav flex-column ms-3 border-start">
                                    <li class="nav-item">
                                        <a href="{{ route('user.quote.visibility') }}" 
                                           class="nav-link {{ request()->is('user/quote/visibility') ? 'active' : '' }}">
                                            <i class="bi bi-eye"></i>
                                            <span>الظهور</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.quote.form') . '?language=' . $default->code }}" 
                                           class="nav-link {{ request()->is('user/quote/form') || request()->is('user/quote/*/inputEdit') ? 'active' : '' }}">
                                            <i class="bi bi-file-earmark-richtext"></i>
                                            <span>منشئ النماذج</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.all.quotes') }}" 
                                           class="nav-link {{ request()->is('user/all/quotes') ? 'active' : '' }}">
                                            <i class="bi bi-chat-dots"></i>
                                            <span>جميع الطلبات</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.pending.quotes') }}" 
                                           class="nav-link {{ request()->is('user/pending/quotes') ? 'active' : '' }}">
                                            <i class="bi bi-hourglass-top"></i>
                                            <span>الطلبات المعلقة</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.processing.quotes') }}" 
                                           class="nav-link {{ request()->is('user/processing/quotes') ? 'active' : '' }}">
                                            <i class="bi bi-hourglass-split"></i>
                                            <span>الطلبات قيد المعالجة</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.completed.quotes') }}" 
                                           class="nav-link {{ request()->is('user/completed/quotes') ? 'active' : '' }}">
                                            <i class="bi bi-check-circle"></i>
                                            <span>طلبات المكتملة</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user.rejected.quotes') }}" 
                                           class="nav-link {{ request()->is('user/rejected/quotes') ? 'active' : '' }}">
                                            <i class="bi bi-x-circle"></i>
                                            <span>الطلبات المرفوضة</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    <!-- Additional Settings -->
                    <li class="nav-section-title small text-muted px-2 py-2 mt-3">
                        إعدادات إضافية
                    </li>

                    @if (!empty($permissions) && in_array('Plugins', $permissions))
                        <li class="nav-item">
                            <a href="{{ route('user.plugins') }}" 
                               class="nav-link d-flex align-items-center {{ request()->routeIs('user.plugins') ? 'active' : '' }}">
                                <i class="bi bi-puzzle"></i>
                                <span>الإضافات</span>
                            </a>
                        </li>
                    @endif

                    <li class="nav-item">
                        <a href="{{ route('user.basic_settings.seo', ['language' => $default->code]) }}" 
                           class="nav-link d-flex align-items-center {{ request()->path() == 'user/basic_settings/seo' ? 'active' : '' }}">
                            <i class="bi bi-search"></i>
                            <span>تحسين محركات البحث</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('user.cookie.alert') . '?language=' . $default->code }}" 
                           class="nav-link d-flex align-items-center {{ request()->path() == 'user/cookie-alert' ? 'active' : '' }}">
                            <i class="bi bi-shield-check"></i>
                            <span>تنبيه ملفات تعريف الارتباط</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    height: 100vh;
    background: var(--bs-light);
    border-start: 1px solid var(--bs-border-color);
    transition: all 0.3s ease;
}

.sidebar[data-theme="dark"] {
    background: var(--bs-dark);
    color: var(--bs-light);
}

.nav-section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.nav-link {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 0.75rem;
    color: var(--bs-body-color);
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.nav-link.active {
    background: var(--bs-primary);
    color: white;
}

.sidebar[data-theme="dark"] .nav-link {
    color: var(--bs-light);
}

.sidebar[data-theme="dark"] .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
}

.nav-item {
    padding: 0;
}

.nav .nav {
    padding-right: 0.75rem;
}

.nav-item .collapse {
    transition: all 0.2s ease;
}

.border-start {
    border-left: 2px solid var(--bs-border-color) !important;
}

[dir="rtl"] .border-start {
    border-left: none !important;
    border-right: 2px solid var(--bs-border-color) !important;
}

[dir="rtl"] .ms-3 {
    margin-right: 1rem !important;
    margin-left: 0 !important;
}

[dir="rtl"] .me-2 {
    margin-left: 0.5rem !important;
    margin-right: 0 !important;
}

[dir="rtl"] .nav-link {
    display: flex;
    align-items: center;
}

[dir="rtl"] .nav-link i:not(.bi-chevron-down) {
    margin-left: 0.75rem;
    margin-right: 0;
    order: -1;
}

[dir="rtl"] .nav-link span {
    flex: 1;
    text-align: right;
}

[dir="rtl"] .nav-link .bi-chevron-down {
    margin-right: auto;
    margin-left: 0;
}
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebar');
        var sidebarOverlay = document.getElementById('sidebar-overlay');
        var sidebarToggle = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#sidebar"]');
        
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // إغلاق الشريط الجانبي عند النقر على عنصر القائمة في الجوال
        var sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    toggleSidebar();
                }
            });
        });
    });
</script>