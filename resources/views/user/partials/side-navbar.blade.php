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
<div class="sidebar sidebar-style-2" @if (request()->cookie('user-theme') == 'dark') data-background-color="dark2" @endif>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <div class="user">
                <div class="avatar-sm float-left mr-2">
                    @if (!empty(Auth::user()->photo))
                        <img src="{{ asset('assets/front/img/user/' . Auth::user()->photo) }}" alt="..."
                            class="avatar-img rounded">
                    @else
                        <img src="{{ asset('assets/admin/img/propics/blank_user.jpg') }}" alt="..."
                            class="avatar-img rounded">
                    @endif
                </div>
                <div class="info">
                    <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                        <span>
                            {{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}
                            <span class="user-level">{{ auth()->user()->username }}</span>
                            <span class="caret"></span>
                        </span>
                    </a>
                    <div class="clearfix"></div>
                    <div class="collapse in" id="collapseExample">
                        <ul class="nav">
                            @if (!is_null($package))
                                <li>
                                    <a href="{{ route('user-profile-update') }}">
                                        <span class="link-collapse">{{ __('Edit Profile') }}</span>
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ route('user.changePass') }}">
                                    <span class="link-collapse">{{ __('Change Password') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user-logout') }}">
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
                <li class="nav-item
                @if (request()->path() == 'user/dashboard') active @endif">
                    <a href="{{ route('user-dashboard') }}">
                        <i class="la flaticon-paint-palette"></i>
                        <p>{{ __('Dashboard') }}</p>
                    </a>
                </li>




                @if (!is_null($package))
                    {{-- Menu Builder --}}
<!-- 
                    @if (!empty($permissions) && in_array('Ecommerce', $permissions))
                        {{-- START SHOP MANAGEMENT --}}
                        <li
                            class="nav-item
                            @if (request()->path() == 'user/category') active
                            @elseif(request()->path() == 'user/subcategory') active
                            @elseif(request()->is('user/subcategory/*/edit')) active
                            @elseif(request()->is('user/category/*/edit')) active
                            @elseif(request()->path() == 'user/item') active
                            @elseif(request()->routeIs('user.item.type')) active
                            @elseif(request()->is('user/item/*/edit')) active
                            @elseif(request()->path() == 'user/item/all/orders') active
                            @elseif(request()->path() == 'user/item/pending/orders') active
                            @elseif(request()->path() == 'user/item/processing/orders') active
                            @elseif(request()->path() == 'user/item/completed/orders') active
                            @elseif(request()->path() == 'user/item/rejected/orders') active
                            @elseif(request()->routeIs('user.item.variations')) active
                            @elseif(request()->routeIs('user.item.create')) active
                            @elseif(request()->routeIs('user.item.details')) active
                            @elseif(request()->path() == 'user/coupon') active
                            @elseif(request()->routeIs('user.coupon.edit')) active
                            @elseif(request()->path() == 'user/shipping') active
                            @elseif(request()->routeIs('user.shipping.edit')) active
                            @elseif(request()->routeIs('user.item.settings')) active
                            @elseif(request()->path() == 'user/item/orders/report') active @endif">
                            <a data-toggle="collapse" href="#category">
                                <i class="fas fa-store-alt"></i>
                                <p>Shop Management</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse
                            @if (request()->path() == 'user/category') show
                            @elseif(request()->path() == 'user/subcategory') show
                            @elseif(request()->is('user/subcategory/*/edit')) show
                            @elseif(request()->is('user/category/*/edit')) show
                            @elseif(request()->routeIs('user.item.type')) show
                            @elseif(request()->path() == 'user/item') show
                            @elseif(request()->is('user/item/*/edit')) show
                            @elseif(request()->path() == 'user/item/all/orders') show
                            @elseif(request()->path() == 'user/item/pending/orders') show
                            @elseif(request()->path() == 'user/item/processing/orders') show
                            @elseif(request()->path() == 'user/item/completed/orders') show
                            @elseif(request()->path() == 'user/item/rejected/orders') show
                            @elseif(request()->routeIs('user.item.create')) show
                            @elseif(request()->routeIs('user.item.details')) show
                            @elseif(request()->path() == 'user/coupon') show
                            @elseif(request()->routeIs('user.coupon.edit')) show
                            @elseif(request()->routeIs('user.item.variations')) show
                            @elseif(request()->path() == 'user/shipping') show
                            @elseif(request()->routeIs('user.shipping.edit')) show
                            @elseif(request()->routeIs('user.item.settings')) show
                            @elseif(request()->path() == 'user/item/orders/report') show @endif"
                                id="category">
                                <ul class="nav nav-collapse">
                                    <li class="@if (request()->routeIs('user.item.settings')) active @endif">
                                        <a href="{{ route('user.item.settings') . '?language=' . $default->code }}">
                                            <span class="sub-item">Settings</span>
                                        </a>
                                    </li>
                                    <li
                                        class="
                                @if (request()->path() == 'user/shipping') active
                                @elseif(request()->routeIs('user.shipping.edit')) active @endif">
                                        <a href="{{ route('user.shipping.index') . '?language=' . $default->code }}">
                                            <span class="sub-item">Shipping Charges</span>
                                        </a>
                                    </li>
                                    <li
                                        class="
                            @if (request()->path() == 'user/coupon') active
                            @elseif(request()->routeIs('user.coupon.edit')) active @endif">
                                        <a href="{{ route('user.coupon.index') }}">
                                            <span class="sub-item">{{ __('Coupons') }}</span>
                                        </a>
                                    </li>
                                    <li class="submenu">
                                        <a data-toggle="collapse" href="#productManagement"
                                            aria-expanded="{{ request()->path() == 'user/category' || request()->path() == 'user/subcategory' || request()->is('user/category/*/edit') || request()->is('user/subcategory/*/edit') || request()->routeIs('user.item.type') || request()->routeIs('user.item.variations') || request()->routeIs('user.item.create') || request()->routeIs('user.item.index') || request()->routeIs('user.item.edit') ? 'true' : 'false' }}">
                                            <span class="sub-item">{{ __('Manage Items') }}</span>
                                            <span class="caret"></span>
                                        </a>
                                        <div class="collapse
                                        @if (request()->path() == 'user/category') show
                                        @elseif(request()->is('user/category/*/edit')) show
                                        @elseif(request()->path() == 'user/subcategory') show
                                        @elseif(request()->is('user/subcategory/*/edit')) show
                                        @elseif(request()->routeIs('user.item.type')) show
                                        @elseif(request()->routeIs('user.item.variations')) show
                                        @elseif(request()->routeIs('user.item.create')) show
                                        @elseif(request()->routeIs('user.item.index')) show
                                        @elseif(request()->routeIs('user.item.edit')) show @endif"
                                            id="productManagement" style="">
                                            <ul class="nav nav-collapse subnav">
                                                <li
                                                    class="
                                                @if (request()->path() == 'user/category') active
                                                @elseif(request()->is('user/category/*/edit')) active @endif">
                                                    <a
                                                        href="{{ route('user.itemcategory.index') . '?language=' . $default->code }}">
                                                        <span class="sub-item">{{ __('Categories') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="
                                                @if (request()->path() == 'user/subcategory') active
                                                @elseif(request()->is('user/subcategory/*/edit')) active @endif">
                                                    <a
                                                        href="{{ route('user.itemsubcategory.index') . '?language=' . $default->code }}">
                                                        <span class="sub-item">{{ __('Subcategories') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="
                                                @if (request()->routeIs('user.item.type')) active
                                                @elseif(request()->routeIs('user.item.create')) active @endif">
                                                    <a href="{{ route('user.item.type') }}">
                                                        <span class="sub-item">Add Item</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="
                                                @if (request()->path() == 'user/item') active
                                                @elseif(request()->is('user/item/*/edit')) active
                                                @elseif(request()->routeIs('user.item.variations')) active @endif">
                                                    <a
                                                        href="{{ route('user.item.index') . '?language=' . $default->code }}">
                                                        <span class="sub-item">Items</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>


                                    <li class="submenu">
                                        <a data-toggle="collapse" href="#manageOrders"
                                            aria-expanded="{{ request()->routeIs('user.all.item.orders') || request()->routeIs('user.pending.item.orders') || request()->routeIs('user.processing.item.orders') || request()->routeIs('user.completed.item.orders') || request()->routeIs('user.rejected.item.orders') || request()->routeIs('user.item.details') || request()->path() == 'admin/product/orders/report' ? 'true' : 'false' }}">
                                            <span class="sub-item">Manage Orders</span>
                                            <span class="caret"></span>
                                        </a>
                                        <div class="collapse
                            @if (request()->routeIs('user.all.item.orders')) show
                            @elseif(request()->routeIs('user.pending.item.orders')) show
                            @elseif(request()->routeIs('user.processing.item.orders')) show
                            @elseif(request()->routeIs('user.completed.item.orders')) show
                            @elseif(request()->routeIs('user.rejected.item.orders')) show
                            @elseif(request()->routeIs('user.item.details')) show
                            @elseif(request()->path() == 'user/item/orders/report') show @endif"
                                            id="manageOrders" style="">
                                            <ul class="nav nav-collapse subnav">
                                                <li class="@if (request()->path() == 'user/item/all/orders') active @endif">
                                                    <a href="{{ route('user.all.item.orders') }}">
                                                        <span class="sub-item">All Orders</span>
                                                    </a>
                                                </li>
                                                <li class="@if (request()->path() == 'user/item/pending/orders') active @endif">
                                                    <a href="{{ route('user.pending.item.orders') }}">
                                                        <span class="sub-item">Pending Orders</span>
                                                    </a>
                                                </li>
                                                <li class="@if (request()->path() == 'user/item/processing/orders') active @endif">
                                                    <a href="{{ route('user.processing.item.orders') }}">
                                                        <span class="sub-item">Processing Orders</span>
                                                    </a>
                                                </li>
                                                <li class="@if (request()->path() == 'user/item/completed/orders') active @endif">
                                                    <a href="{{ route('user.completed.item.orders') }}">
                                                        <span class="sub-item">Completed Orders</span>
                                                    </a>
                                                </li>
                                                <li class="@if (request()->path() == 'user/item/rejected/orders') active @endif">
                                                    <a href="{{ route('user.rejected.item.orders') }}">
                                                        <span class="sub-item">Rejected Orders</span>
                                                    </a>
                                                </li>
                                                <li class="@if (request()->path() == 'user/item/orders/report') active @endif">
                                                    <a href="{{ route('user.orders.report') }}">
                                                        <span class="sub-item">Report</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        {{-- END SHOP MANAGEMENT --}}
                    @endif
                    @if (!empty($permissions) && in_array('Hotel Booking', $permissions))
                        {{-- Start Rooms management --}}

                        <li
                            class="nav-item @if (request()->routeIs('user.rooms_management.settings')) active
                            @elseif (request()->routeIs('user.rooms_management.coupons')) active
                            @elseif (request()->routeIs('user.rooms_management.amenities')) active
                            @elseif (request()->routeIs('user.rooms_management.categories')) active
                            @elseif (request()->routeIs('user.rooms_management.rooms')) active
                            @elseif (request()->routeIs('user.rooms_management.create_room')) active
                            @elseif (request()->routeIs('user.rooms_management.edit_room')) active
                            @elseif (request()->routeIs('user.room_bookings.all_bookings')) active
                            @elseif (request()->routeIs('user.room_bookings.paid_bookings')) active
                            @elseif (request()->routeIs('user.room_bookings.unpaid_bookings')) active
                            @elseif (request()->routeIs('user.room_bookings.booking_details_and_edit')) active
                            @elseif (request()->routeIs('user.room_bookings.booking_form')) active @endif">
                            <a data-toggle="collapse" href="#rooms">
                                <i class="fas fa-hotel"></i>
                                <p class="pr-2">{{ __('Hotel Management') }}</p>
                                <span class="caret"></span>
                            </a>
                            <div id="rooms"
                                class="collapse
                                @if (request()->routeIs('user.rooms_management.settings')) show
                                @elseif (request()->routeIs('user.rooms_management.coupons')) show
                                @elseif (request()->routeIs('user.rooms_management.amenities')) show
                                @elseif (request()->routeIs('user.rooms_management.categories')) show
                                @elseif (request()->routeIs('user.rooms_management.rooms')) show
                                @elseif (request()->routeIs('user.rooms_management.create_room')) show
                                @elseif (request()->routeIs('user.rooms_management.edit_room')) show
                                @elseif (request()->routeIs('user.room_bookings.all_bookings')) show
                                @elseif (request()->routeIs('user.room_bookings.paid_bookings')) show
                                @elseif (request()->routeIs('user.room_bookings.unpaid_bookings')) show
                                @elseif (request()->routeIs('user.room_bookings.booking_details_and_edit')) show
                                @elseif (request()->routeIs('user.room_bookings.booking_form')) show @endif">
                                <ul class="nav nav-collapse">
                                    <li
                                        class="{{ request()->routeIs('user.rooms_management.settings') ? 'active' : '' }}">
                                        <a href="{{ route('user.rooms_management.settings') }}">
                                            <span class="sub-item">{{ __('Settings') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ request()->routeIs('user.rooms_management.coupons') ? 'active' : '' }}">
                                        <a
                                            href="{{ route('user.rooms_management.coupons') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Coupons') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ request()->routeIs('user.rooms_management.amenities') ? 'active' : '' }}">
                                        <a
                                            href="{{ route('user.rooms_management.amenities') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Amenities') }}</span>
                                        </a>
                                    </li>

                                    @if (!is_null($roomSetting) && $roomSetting->room_category_status == 1)
                                        <li
                                            class="{{ request()->routeIs('user.rooms_management.categories') ? 'active' : '' }}">
                                            <a
                                                href="{{ route('user.rooms_management.categories') . '?language=' . $default->code }}">
                                                <span class="sub-item">{{ __('Categories') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li
                                        class="
                                            @if (request()->routeIs('user.rooms_management.rooms')) active
                                            @elseif (request()->routeIs('user.rooms_management.create_room')) active
                                            @elseif (request()->routeIs('user.rooms_management.edit_room')) active @endif">
                                        <a
                                            href="{{ route('user.rooms_management.rooms') . '?language=' . $default->code }}">
                                            <span class="sub-item">Rooms</span>
                                        </a>
                                    </li>
                                    <li
                                        class="submenu
                                            @if (request()->routeIs('user.room_bookings.all_bookings')) selected
                                            @elseif (request()->routeIs('user.room_bookings.paid_bookings')) selected
                                            @elseif (request()->routeIs('user.room_bookings.unpaid_bookings')) selected
                                            @elseif (request()->routeIs('user.room_bookings.booking_details_and_edit')) selected
                                            @elseif (request()->routeIs('user.room_bookings.booking_form')) selected @endif

                                        ">
                                        <a data-toggle="collapse" href="#roomBookings"
                                            aria-expanded="{{ request()->routeIs('user.room_bookings.all_bookings') || request()->routeIs('user.room_bookings.paid_booking') || request()->routeIs('user.room_bookings.unpaid_bookings') ? 'true' : 'false' }}">
                                            <span class="sub-item">{{ __('Room Bookings') }}</span>
                                            <span class="caret"></span>
                                        </a>
                                        <div class="collapse
                                        {{ request()->routeIs('user.room_bookings.all_bookings') || request()->routeIs('user.room_bookings.paid_bookings') || request()->routeIs('user.room_bookings.unpaid_bookings') ? 'show' : '' }}"
                                            id="roomBookings">
                                            <ul class="nav nav-collapse subnav">
                                                <li
                                                    class="{{ request()->routeIs('user.room_bookings.all_bookings') ? 'active' : '' }}">
                                                    <a href="{{ route('user.room_bookings.all_bookings') }}">
                                                        <span class="sub-item">{{ __('All Bookings') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.room_bookings.paid_bookings') ? 'active' : '' }}">
                                                    <a href="{{ route('user.room_bookings.paid_bookings') }}">
                                                        <span class="sub-item">{{ __('Paid Bookings') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.room_bookings.unpaid_bookings') ? 'active' : '' }}">
                                                    <a href="{{ route('user.room_bookings.unpaid_bookings') }}">
                                                        <span class="sub-item">{{ __('Unpaid Bookings') }}</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        {{-- End Rooms Management  --}}
                    @endif


                    @if (!empty($permissions) && in_array('Course Management', $permissions))
                        {{-- Start Course Management --}}
                        <li
                            class="nav-item
                                @if (request()->routeIs('user.course_management.settings')) active
                                @elseif (request()->routeIs('user.course_management.categories')) active
                                @elseif (request()->routeIs('user.course_management.courses')) active
                                @elseif (request()->routeIs('user.course_management.create_course')) active
                                @elseif (request()->routeIs('user.course_management.edit_course')) active
                                @elseif (request()->routeIs('user.course_management.course.faqs')) active
                                @elseif (request()->routeIs('user.course_management.course.thanks_page')) active
                                @elseif (request()->routeIs('user.course_management.course.certificate_settings')) active
                                @elseif (request()->routeIs('user.course_management.course.modules')) active
                                @elseif (request()->routeIs('user.course_management.lesson.contents')) active
                                @elseif (request()->routeIs('user.course_management.lesson.create_quiz')) active
                                @elseif (request()->routeIs('user.course_management.lesson.manage_quiz')) active
                                @elseif (request()->routeIs('user.course_management.lesson.edit_quiz')) active
                                @elseif (request()->routeIs('user.course_enrolments')) active
                                @elseif (request()->routeIs('user.course_enrolment.details')) active
                                @elseif (request()->routeIs('user.course_enrolments.report')) active
                                @elseif (request()->routeIs('user.instructors')) active
                                @elseif (request()->routeIs('user.create_instructor')) active
                                @elseif (request()->routeIs('user.edit_instructor')) active
                                @elseif (request()->routeIs('user.instructor.social_links')) active
                                @elseif (request()->routeIs('user.instructor.edit_social_link')) active
                                @elseif (request()->routeIs('user.course_management.coupons')) active @endif">
                            <a data-toggle="collapse" href="#course">
                                <i class="fas fa-book"></i>
                                <p>{{ __('Course Management') }}</p>
                                <span class="caret"></span>
                            </a>
                            <div id="course"
                                class="collapse
                                @if (request()->routeIs('user.course_management.settings')) show
                                @elseif (request()->routeIs('user.course_management.categories')) show
                                @elseif (request()->routeIs('user.course_management.courses')) show
                                @elseif (request()->routeIs('user.course_management.create_course')) show
                                @elseif (request()->routeIs('user.course_management.edit_course')) show
                                @elseif (request()->routeIs('user.course_management.course.faqs')) show
                                @elseif (request()->routeIs('user.course_management.course.thanks_page')) show
                                @elseif (request()->routeIs('user.course_management.course.certificate_settings')) show
                                @elseif (request()->routeIs('user.course_management.course.modules')) show
                                @elseif (request()->routeIs('user.course_management.lesson.contents')) show
                                @elseif (request()->routeIs('user.course_management.lesson.create_quiz')) show
                                @elseif (request()->routeIs('user.course_management.lesson.manage_quiz')) show
                                @elseif (request()->routeIs('user.course_management.lesson.edit_quiz')) show
                                @elseif (request()->routeIs('user.course_enrolments')) show
                                @elseif (request()->routeIs('user.course_enrolment.details')) show
                                @elseif (request()->routeIs('user.course_enrolments.report')) show

                                @elseif (request()->routeIs('user.instructors')) show
                                @elseif (request()->routeIs('user.create_instructor')) show
                                @elseif (request()->routeIs('user.edit_instructor')) show
                                @elseif (request()->routeIs('user.instructor.social_links')) show
                                @elseif (request()->routeIs('user.instructor.edit_social_link')) show

                                @elseif (request()->routeIs('user.course_management.coupons')) show @endif">
                                <ul class="nav nav-collapse">
                                    {{-- Start Instructor --}}
                                    <li
                                        class=" @if (request()->routeIs('user.instructors')) active
                                            @elseif (request()->routeIs('user.create_instructor')) active
                                            @elseif (request()->routeIs('user.edit_instructor')) active
                                            @elseif (request()->routeIs('user.instructor.social_links')) active
                                            @elseif (request()->routeIs('user.instructor.edit_social_link')) active @endif">
                                        <a href="{{ route('user.instructors', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('Instructors') }}</span>
                                        </a>
                                    </li>
                                    {{-- End Instructor --}}
                                    <li
                                        class="{{ request()->routeIs('user.course_management.categories') ? 'active' : '' }}">
                                        <a
                                            href="{{ route('user.course_management.categories', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('Categories') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="
                                            @if (request()->routeIs('user.course_management.courses')) active
                                            @elseif (request()->routeIs('user.course_management.create_course')) active
                                            @elseif (request()->routeIs('user.course_management.edit_course')) active
                                            @elseif (request()->routeIs('user.course_management.course.faqs')) active
                                            @elseif (request()->routeIs('user.course_management.course.thanks_page')) active
                                            @elseif (request()->routeIs('user.course_management.course.certificate_settings')) active
                                            @elseif (request()->routeIs('user.course_management.course.modules')) active
                                            @elseif (request()->routeIs('user.course_management.lesson.contents')) active
                                            @elseif (request()->routeIs('user.course_management.lesson.create_quiz')) active
                                            @elseif (request()->routeIs('user.course_management.lesson.manage_quiz')) active
                                            @elseif (request()->routeIs('user.course_management.lesson.edit_quiz')) active @endif">
                                        <a
                                            href="{{ route('user.course_management.courses', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('Courses') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ request()->routeIs('user.course_management.coupons') ? 'active' : '' }}">
                                        <a href="{{ route('user.course_management.coupons') }}">
                                            <span class="sub-item">{{ __('Coupons') }}</span>
                                        </a>
                                    </li>

                                    <li
                                        class="submenu
                                        @if (request()->routeIs('user.course_enrolments')) selected
                                        @elseif (request()->routeIs('user.course_enrolment.details')) selected
                                        @elseif (request()->routeIs('user.course_enrolments.report')) selected @endif">
                                        <a data-toggle="collapse" href="#courseEnrol"
                                            aria-expanded="{{ request()->routeIs('user.course_enrolments') || request()->routeIs('user.course_enrolment.details') || request()->routeIs('user.course_enrolments.report') ? 'true' : 'false' }}">
                                            <span class="sub-item">{{ __('Course Enrolments') }}</span>
                                            <span class="caret"></span>
                                        </a>
                                        <div class="collapse {{ request()->routeIs('user.course_enrolments') || request()->routeIs('user.course_enrolment.details') || request()->routeIs('user.course_enrolments.report') ? 'show' : '' }}"
                                            id="courseEnrol">
                                            <ul class="nav nav-collapse subnav">
                                                <li
                                                    class="{{ request()->routeIs('user.course_enrolments') && empty(request()->input('status')) ? 'active' : '' }}">
                                                    <a href="{{ route('user.course_enrolments') }}">
                                                        <span class="sub-item">{{ __('All Enrolments') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.course_enrolments') && request()->input('status') == 'completed' ? 'active' : '' }}">
                                                    <a
                                                        href="{{ route('user.course_enrolments', ['status' => 'completed']) }}">
                                                        <span class="sub-item">{{ __('Completed Enrolments') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.course_enrolments') && request()->input('status') == 'pending' ? 'active' : '' }}">
                                                    <a
                                                        href="{{ route('user.course_enrolments', ['status' => 'pending']) }}">
                                                        <span class="sub-item">{{ __('Pending Enrolments') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.course_enrolments') && request()->input('status') == 'rejected' ? 'active' : '' }}">
                                                    <a
                                                        href="{{ route('user.course_enrolments', ['status' => 'rejected']) }}">
                                                        <span class="sub-item">{{ __('Rejected Enrolments') }}</span>
                                                    </a>
                                                </li>
                                                <li
                                                    class="{{ request()->routeIs('user.course_enrolments.report') ? 'active' : '' }}">
                                                    <a href="{{ route('user.course_enrolments.report') }}">
                                                        <span class="sub-item">{{ __('Report') }}</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        {{-- End Course Management --}}
                    @endif

                    @if (!empty($permissions) && in_array('Donation Management', $permissions))
                        <li
                            class="nav-item
                                @if (request()->routeIs('user.donation.index')) active
                                @elseif (request()->routeIs('user.donation.categories')) active
                                @elseif (request()->routeIs('user.donation.create')) active
                                @elseif(request()->routeIs('user.donation.payment.log')) active
                                @elseif(request()->routeIs('user.donation.settings')) active
                                @elseif(request()->routeIs('user.donation.edit')) active
                                @elseif(request()->routeIs('user.donation.report')) active @endif">
                            <a data-toggle="collapse" href="#donation_manage">
                                <i class="fas fa-hand-holding-usd"></i>
                                <p>{{ __('Donations & Causes') }}</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse
                                    @if (request()->routeIs('user.donation.index')) show
                                    @elseif (request()->routeIs('user.donation.categories')) show
                                    @elseif (request()->routeIs('user.donation.create')) show
                                    @elseif(request()->routeIs('user.donation.payment.log')) show
                                    @elseif(request()->routeIs('user.donation.edit')) show
                                    @elseif(request()->routeIs('user.donation.settings')) show
                                    @elseif(request()->routeIs('user.donation.report')) show @endif"
                                id="donation_manage">
                                <ul class="nav nav-collapse">
                                    <li class="@if (request()->routeIs('user.donation.settings')) active @endif">
                                        <a href="{{ route('user.donation.settings') }}">
                                            <span class="sub-item">{{ __('Settings') }}</span>
                                        </a>
                                    </li>
                                    <li class="@if (request()->routeIs('user.donation.categories')) active @endif">
                                        <a
                                            href="{{ route('user.donation.categories') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Categories') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="
                                            @if (request()->routeIs('user.donation.index')) active
                                            @elseif(request()->routeIs('user.donation.edit')) active
                                            @elseif(request()->routeIs('user.donation.create')) active @endif">
                                        <a href="{{ route('user.donation.index') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('All Causes') }}</span>
                                        </a>
                                    </li>
                                    <li class="@if (request()->routeIs('user.donation.payment.log')) active @endif">
                                        <a
                                            href="{{ route('user.donation.payment.log') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Donations') }}</span>
                                        </a>
                                    </li>
                                    <li class="@if (request()->routeIs('user.donation.report')) active @endif">
                                        <a href="{{ route('user.donation.report') }}">
                                            <span class="sub-item">{{ __('Report') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif
                    @if (
                        !empty($permissions) &&
                            (in_array('Ecommerce', $permissions) ||
                                in_array('Hotel Booking', $permissions) ||
                                in_array('Donation Management', $permissions) ||
                                in_array('Course Management', $permissions)))
                        {{-- Start Payment getway --}}

                        {{-- End Payment getway --}}
                    @endif
                    @if (
                        !empty($permissions) &&
                            (in_array('Ecommerce', $permissions) ||
                                in_array('Hotel Booking', $permissions) ||
                                in_array('Donation Management', $permissions) ||
                                in_array('Course Management', $permissions)))
                        {{-- start Register       User --}}
                        <li class="nav-item  @if (request()->path() == 'user/register-user') active @endif">
                            <a href="{{ route('user.register-user', ['language' => $default->code]) }}">
                                <i class="fas fa-users"></i>
                                <p>{{ __('Registered Users') }}</p>
                            </a>
                        </li>
                        {{-- End Register User --}}
                    @endif -->
                    
                    
                        <li
                            class="nav-item ">
                            <a data-toggle="collapse" href="#store_set">
                                <i class="la flaticon-paypal"></i>
                                <p>{{ __('اعدادات المتجر') }}</p>
                                <span class="caret"></span>
                            </a>
                             <div class="collapse  @if (request()->path() == 'user/gateways') show   @elseif(request()->path() == 'user/offline/gateways') show @endif"
                                id="store_set">
                                <ul class="nav nav-collapse">

                                    
                        <!-- <li
                            class="submenu  @if (request()->path() == 'user/gateways') active   @elseif(request()->path() == 'user/offline/gateways') active @endif">
                            <a data-toggle="collapse" href="#gateways">
                                {{ __('Payment Gateways') }}
                                <span class="caret"></span>
                            </a> -->
                            <!-- <div class="collapse  @if (request()->path() == 'user/gateways') show   @elseif(request()->path() == 'user/offline/gateways') show @endif"
                                id="gateways">
                                <ul class="nav nav-collapse subnav">
                                    <li class="@if (request()->path() == 'user/gateways') active @endif">
                                        <a href="{{ route('user.gateway.index') }}">
                                            <span class="sub-item">{{ __('Online Gateways') }}</span>
                                        </a>
                                    </li>
                                    <li class="@if (request()->path() == 'user/offline/gateways') active @endif">
                                        <a
                                            href="{{ route('user.gateway.offline') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Offline Gateways') }}</span>
                                        </a>
                                    </li>
                                    
                                                    <li class="
                    @if (request()->path() == 'user/payment-log') active @endif">
                    <a href="{{ route('user.payment-log.index') }}">
                       
                        <p><span class="sub-item">{{ __('Payment Logs') }}</span></p>
                    </a>
                </li>
                
                                </ul>
                            </div> -->
                        </li> 
                      
                        @if (!is_null($package))
                <li class="@if (request()->routeIs('user.basic_settings.general-settings')) active @endif">
                                    <a href="{{ route('user.basic_settings.general-settings') }}">
                                     <p>{{ __('General Settings') }}</p>
                                    </a>
                         </li>         
                    <li class="
                    @if (request()->path() == 'user/contact') active @endif">
                        <a href="{{ route('user.contact', ['language' => $default->code]) }}">
                            <p>{{ __('Contact Page') }}</p>
                        </a>
                    </li>
                @endif
                        <li class="
                    @if (request()->path() == 'user/gateways-soon') active @endif">
                        <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}">
                            <p>{{ __('Payment Gateways') }}</p>
                        </a>
                    </li>
    

                
                <!-- <li
                    class="
                    @if (request()->path() == 'user/package-list') active
                    @elseif(request()->is('user/package/checkout/*')) active @endif">
                    <a href="{{ route('user.plan.extend.index') }}">
               
                        <p>{{ __('Buy Plan') }}</p>
                    </a>
                </li> -->

                <li class="
                    @if (request()->path() == 'user/gateways-soon') active @endif">
                        <a href="{{ route('user.gateways-soon', ['language' => $default->code]) }}">
                            <p>{{ __('Buy Plan') }}</p>
                        </a>
                    </li>
                <!-- <li class="
                    @if (request()->path() == 'user/change-password') active @endif">
                    <a href="{{ route('user.changePass') }}">
                   
                        <p>{{ __('Change Password') }}</p>
                    </a>
                </li> -->
                
                @if (!is_null($package))
                @if (!empty($permissions) && in_array('Custom Domain', $permissions))
                                    <li
                                        class="
                                    @if (request()->path() == 'user/domains') active @endif">
                                        <a href="{{ route('user-domains') }}">
                                            {{ __('Custom Domain') }}
                                        </a>
                                    </li>
                                @endif
                @endif
                
                    <li
                        class="
                    @if (request()->path() == 'user/languages') active
                    @elseif(request()->is('user/language/*/edit')) active
                    @elseif(request()->is('user/language/*/edit/keyword')) active @endif">
                        <a href="{{ route('user.language.index') }}">
                            <p>{{ __('Language Management') }}</p>
                        </a>
                    </li>
                                </ul>
                            </div>
                        </li>
                    <li
                        class="nav-item  @if (request()->path() == 'user/favicon') active
                    @elseif(request()->path() == 'user/theme/version') active
                    @elseif(request()->path() == 'user/logo') active
                    @elseif(request()->path() == 'user/preloader') active
                    @elseif (request()->routeIs('user.basic_settings.general-settings')) active
                    @elseif(request()->path() == 'user/color') active
                    @elseif(request()->path() == 'user/css') active
                    @elseif(request()->path() == 'user/social') active
                    @elseif(request()->is('user/social/*')) active
                    @elseif (request()->routeIs('user.basic_settings.mail_templates')) active
                    @elseif (request()->routeIs('user.basic_settings.edit_mail_template')) active
                    @elseif (request()->path() == 'user/mail/information/subscriber') active
                    @elseif(request()->path() == 'user/basic_settings/seo') active
                    @elseif(request()->path() == 'user/cookie-alert') active
                    @elseif(request()->is('user/breadcrumb')) active
                    @elseif (request()->routeIs('user.plugins')) active @endif">
                        <a data-toggle="collapse" href="#basic">
                            <i class="la flaticon-settings"></i>
                            <p>{{ __('Settings') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/favicon') show
                        @elseif(request()->path() == 'user/theme/version') show
                        @elseif(request()->path() == 'user/logo') show
                        @elseif(request()->path() == 'user/preloader') show
                        @elseif (request()->routeIs('user.basic_settings.general-settings')) show
                        @elseif(request()->path() == 'user/color') show
                        @elseif(request()->path() == 'user/css') show
                        @elseif(request()->path() == 'user/social') show
                        @elseif(request()->is('user/social/*')) show
                        @elseif (request()->routeIs('user.basic_settings.mail_templates')) show
                        @elseif (request()->path() == 'user/mail/information/subscriber') show
                        @elseif (request()->routeIs('user.basic_settings.edit_mail_template')) show
                        @elseif(request()->path() == 'user/basic_settings/seo') show
                        @elseif(request()->path() == 'user/cookie-alert') show
                        @elseif(request()->is('user/breadcrumb')) show
                        @elseif (request()->routeIs('user.plugins')) show @endif"
                            id="basic">
                            <ul class="nav nav-collapse">
                                @if (!is_null($package))
                                    <li
                                        class="submenu
                                @if (request()->path() == 'user/home-page-text/edit') active
                        @elseif(request()->path() == 'user/home-page/video') active
                        @elseif(request()->path() == 'user/home-page/about') active
                        @elseif(request()->path() == 'user/home_page/brand_section') active
                        @elseif(request()->path() == 'user/counter-informations') active
                        @elseif(request()->is('user/counter-information/*/edit')) active
                        @elseif(request()->is('user/counter-information/*/edit')) active
                        @elseif(request()->is('user/home_page/why-choose-us')) active
                        @elseif (request()->routeIs('user.home_page.hero.slider_version')) active
                        @elseif (request()->routeIs('user.offerBanner.index')) active
                        @elseif (request()->routeIs('user.offerBanner.edit')) active
                        @elseif (request()->routeIs('user.feature.index')) active
                        @elseif (request()->routeIs('user.feature.edit')) active
                        @elseif (request()->routeIs('user.home_page.hero.create_slider')) active
                        @elseif (request()->routeIs('user.home_page.hero.edit_slider')) active
                        @elseif (request()->routeIs('user.home_page.hero.video_version')) active
                        @elseif (request()->routeIs('user.sections.index')) active
                        @elseif (request()->routeIs('user.home_page.hero.static_version'))active
                        @elseif (request()->routeIs('user.home_page.work_process_section'))active
                        @elseif (request()->routeIs('user.home_page.work_process_section.create_work_process'))active
                        @elseif (request()->routeIs('user.home_page.work_process_section.edit_work_process'))active
                        @elseif(request()->path() == 'user/skills') active
                        @elseif(request()->is('user/skill/*/edit')) active
                        @elseif(request()->path() == 'user/testimonials') active
                        @elseif(request()->is('user/testimonial/*/edit')) active @endif">
                                        <a data-toggle="collapse" href="#home_section"
                                            aria-expanded="{{ request()->path() == 'user/mail/information/subscriber' || request()->routeIs('user.basic_settings.mail_templates') || request()->routeIs('user.basic_settings.edit_mail_template') ? 'true' : 'false' }}">
                                            <span class="sub-item">{{ __('الصفحة الرئيسية') }}</span>
                                            <span class="caret"></span>
                                        </a>
<div class="collapse
                        @if (request()->path() == 'user/home-page-text/edit') show
                        @elseif(request()->path() == 'user/home-page/video') show
                        @elseif(request()->path() == 'user/home-page/about') show
                        @elseif(request()->path() == 'user/home_page/brand_section') show
                        @elseif(request()->path() == 'user/counter-informations') show
                        @elseif(request()->is('user/counter-information/*/edit')) show
                        @elseif(request()->is('user/home_page/why-choose-us')) show
                        @elseif (request()->routeIs('user.home_page.hero.slider_version')) show
                        @elseif (request()->routeIs('user.offerBanner.index')) show
                        @elseif (request()->routeIs('user.offerBanner.edit')) show
                        @elseif (request()->routeIs('user.feature.index')) show
                        @elseif (request()->routeIs('user.feature.edit')) show
                        @elseif (request()->routeIs('user.home_page.hero.create_slider')) show
                        @elseif (request()->routeIs('user.home_page.hero.edit_slider')) show
                        @elseif (request()->routeIs('user.home_page.hero.video_version')) show
                        @elseif (request()->routeIs('user.sections.index')) show
                        @elseif (request()->routeIs('user.home_page.hero.static_version'))show
                        @elseif (request()->routeIs('user.home_page.work_process_section'))show
                        @elseif (request()->routeIs('user.home_page.work_process_section.create_work_process')) show
                        @elseif (request()->routeIs('user.home_page.work_process_section.edit_work_process'))show
                        @elseif (request()->routeIs('user.home_page.action_section')) show
                        @elseif(request()->path() == 'user/skills') show
                        @elseif(request()->is('user/skill/*/edit')) show
                        @elseif(request()->path() == 'user/testimonials') show
                        @elseif(request()->is('user/testimonial/*/edit')) show @endif "
                            id="home_section">
                            <ul class="nav nav-collapse">

                                @if (
                                    $userBs->theme == 'home_one' ||
                                        $userBs->theme == 'home_two' ||
                                        $userBs->theme == 'home_six' ||
                                        $userBs->theme == 'home_seven' ||
                                        $userBs->theme == 'home_eight' ||
                                        $userBs->theme == 'home_nine')
                                    <li
                                        class="@if (request()->routeIs('user.home_page.hero.slider_version')) active
                                    @elseif (request()->routeIs('user.home_page.hero.create_slider')) active
                                    @elseif (request()->routeIs('user.home_page.hero.edit_slider')) active @endif
                                    ">
                                        <a
                                            href="{{ route('user.home_page.hero.slider_version') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Hero Section') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if (
                                    $userBs->theme == 'home_three' ||
                                        $userBs->theme == 'home_four' ||
                                        $userBs->theme == 'home_five' ||
                                        $userBs->theme == 'home_eleven' ||
                                        $userBs->theme == 'home_twelve' ||
                                        $userBs->theme == 'home_ten')
                                    <li class=" @if (request()->routeIs('user.home_page.hero.static_version')) active @endif">
                                        <a
                                            href="{{ route('user.home_page.hero.static_version') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Hero Section') }}</span>
                                        </a>
                                    </li>
                                @endif

                                <li class="@if (request()->path() == 'user/home-page-text/edit') active @endif">
                                    <a
                                        href="{{ route('user.home.page.text.edit', ['language' => $default->code]) }}">
                                        <span class="sub-item">{{ __('Home Sections') }}</span>
                                    </a>
                                </li>
                                @if ($userBs->theme == 'home_ten')
                                    <li
                                        class="{{ request()->routeIs('user.home_page.action_section') ? 'active' : '' }}">
                                        <a
                                            href="{{ route('user.home_page.action_section', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('Call To Action Section') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($userBs->theme == 'home_ten' || $userBs->theme == 'home_eleven')
                                    <li
                                        class="  @if (request()->routeIs('user.feature.index')) active
                                        @elseif(request()->routeIs('user.feature.edit')) active @endif">
                                        <a href="{{ route('user.feature.index') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Features') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($userBs->theme == 'home_eight')
                                    <li
                                        class="  @if (request()->routeIs('user.feature.index')) active
                                        @elseif(request()->routeIs('user.feature.edit')) active @endif">
                                        <a href="{{ route('user.feature.index') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Features') }}</span>
                                        </a>
                                    </li>
                                    <li
                                        class="  @if (request()->routeIs('user.offerBanner.index')) active
                                        @elseif(request()->routeIs('user.offerBanner.edit')) active @endif">
                                        <a
                                            href="{{ route('user.offerBanner.index') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Offer Banner') }}</span>
                                        </a>
                                    </li>
                                @endif

                                @if (
                                    $userBs->theme == 'home_six' ||
                                        $userBs->theme == 'home_one' ||
                                        $userBs->theme == 'home_two' ||
                                        $userBs->theme == 'home_nine' ||
                                        $userBs->theme == 'home_eleven' ||
                                        $userBs->theme == 'home_twelve' ||
                                        $userBs->theme == 'home_three')
                                    <li class="@if (request()->routeIs('user.home.page.about')) active @endif">
                                        <a
                                            href="{{ route('user.home.page.about', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('About Section') }}</span>
                                        </a>
                                    </li>
                                @endif
                                @if ((!empty($permissions) && in_array('Counter Information', $permissions)) || $userBs->theme == 'home_eleven')
                                    @if ($userBs->theme != 'home_eight')
                                        <li class="@if (request()->path() == 'user/counter-informations') active @endif">
                                            <a href="{{ route('user.counter-information.index') }}">
                                                <span class="sub-item">{{ __('Counter Information') }}</span>
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
                                    <li class="@if (request()->routeIs('user.home.page.video')) active @endif">
                                        <a href="{{ route('user.home.page.video') }}">
                                            <span class="sub-item">{{ __('Video Section') }}</span>
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
                                    <li class="@if (request()->path() == 'user/home_page/brand_section') active @endif">
                                        <a
                                            href="{{ route('user.home_page.brand_section', ['language' => $default->code]) }}">
                                            @if ($userBs->theme == 'home_eleven')
                                                <span class="sub-item">{{ __('Donor Section') }}</span>
                                            @else
                                                <span class="sub-item">{{ __('Brand Section') }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endif
                                @if ($userBs->theme == 'home_one' || $userBs->theme == 'home_three' || $userBs->theme == 'home_nine')
                                    <li class="@if (request()->path() == 'user/home_page/why-choose-us') active @endif">
                                        <a
                                            href="{{ route('user.home_page.why_choose_us_section', ['language' => $default->code]) }}">
                                            <span class="sub-item">{{ __('Why Choose Us Section') }}</span>
                                        </a>
                                    </li>
                                @endif

                                @if (!empty($permissions) && in_array('Team', $permissions))
                    <li
                        class="
                @if (request()->routeIs('user.team_section')) active
                @elseif (request()->routeIs('user.team_section.create_member')) active
                @elseif (request()->routeIs('user.team_section.edit_member')) active @endif">
                        <a href="{{ route('user.team_section') . '?language=' . $default->code }}">
                            <p>{{ __('Team') }}</p>
                        </a>
                    </li>
                @endif

                                @if (!empty($permissions) && in_array('Skill', $permissions))
                                    @if (
                                        $userBs->theme != 'home_three' &&
                                            $userBs->theme != 'home_two' &&
                                            $userBs->theme != 'home_ten' &&
                                            $userBs->theme != 'home_nine' &&
                                            $userBs->theme != 'home_eleven' &&
                                            $userBs->theme != 'home_seven' &&
                                            $userBs->theme != 'home_eight')
                                        <li
                                            class="
                                    @if (request()->path() == 'user/skills') active
                                    @elseif(request()->is('user/skill/*/edit')) active @endif">
                                            <a
                                                href="{{ route('user.skill.index') . '?language=' . $default->code }}">
                                                <span class="sub-item">{{ __('Skills') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endif

                                @if (!empty($permissions) && in_array('Testimonial', $permissions))
                                    @if ($userBs->theme != 'home_eight')
                                        <li
                                            class="@if (request()->path() == 'user/testimonials') active
                                @elseif(request()->is('user/testimonial/*/edit')) active @endif">
                                            <a
                                                href="{{ route('user.testimonials.index') . '?language=' . $default->code }}">
                                                <span class="sub-item">{{ __('Testimonial') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endif

                                @if (isset($userBs->theme) &&
                                        ($userBs->theme === 'home_three' ||
                                            $userBs->theme === 'home_two' ||
                                            $userBs->theme === 'home_seven' ||
                                            $userBs->theme === 'home_four' ||
                                            $userBs->theme === 'home_five' ||
                                            $userBs->theme === 'home_two' ||
                                            $userBs->theme === 'home_six'))
                                    <li
                                        class="@if (request()->routeIs('user.home_page.work_process_section')) active
                            @elseif (request()->routeIs('user.home_page.work_process_section.create_work_process')) active
                            @elseif (request()->routeIs('user.home_page.work_process_section.edit_work_process')) active @endif">
                                        <a
                                            href="{{ route('user.home_page.work_process_section') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Work Process Section') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="{{ request()->routeIs('user.sections.index') ? 'active' : '' }}">
                                    <a href="{{ route('user.sections.index') }}">
                                        <span class="sub-item">{{ __('Sections Hide / Show') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                                    </li>

                                    <li class="@if (request()->path() == 'user/theme/version') active @endif">
                                    <a href="{{ route('user.theme.version') }}">
                                        <span class="sub-item">{{ __('Themes') }}</span>
                                    </a>
                                </li>

                                <!-- <li class="@if (request()->path() == 'user/favicon') active @endif">
                                    <a href="{{ route('user.favicon') }}">
                                        <span class="sub-item">{{ __('Favicon') }}</span>
                                    </a>
                                </li> -->

                                <li class="@if (request()->path() == 'user/menu-builder') active @endif">
                                    <a href="{{ route('user.menu_builder.index') . '?language=' . $default->code }}">
                                         <span class="sub-item">{{ __('منشئ القوائم') }}</span>
                                    </a>
                                </li>
                                    <li
                                        class="submenu
                                @if (request()->routeIs('user.footer.text')) active
                                  @elseif (request()->routeIs('user.footer.quick_links')) active @endif">
                                        <a data-toggle="collapse" href="#footer"
                                            aria-expanded="">
                                            <span class="sub-item">{{ __('Footer') }}</span>
                                            <span class="caret"></span>
                                        </a>
                            <div id="footer"
                                class="collapse
                        @if (request()->routeIs('user.footer.text')) show
                        @elseif (request()->routeIs('user.footer.quick_links')) show @endif">
                                <ul class="nav nav-collapse">
                                    <li class="{{ request()->routeIs('user.footer.text') ? 'active' : '' }}">
                                        <a href="{{ route('user.footer.text') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Footer Logo & Text') }}</span>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('user.footer.quick_links') ? 'active' : '' }}">
                                        <a
                                            href="{{ route('user.footer.quick_links') . '?language=' . $default->code }}">
                                            <span class="sub-item">{{ __('Quick Links') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                                    </li>
                                    <li
                                        class="submenu
                                @if (request()->routeIs('user.basic_settings.mail_templates')) selected
                                @elseif (request()->routeIs('user.basic_settings.edit_mail_template')) selected
                                @elseif (request()->routeIs('user.basic_settings.edit_mail_template')) selected
                                @elseif (request()->path() == 'user/mail/information/subscriber') selected @endif">
                                        <a data-toggle="collapse" href="#emailset"
                                            aria-expanded="{{ request()->path() == 'user/mail/information/subscriber' || request()->routeIs('user.basic_settings.mail_templates') || request()->routeIs('user.basic_settings.edit_mail_template') ? 'true' : 'false' }}">
                                            <span class="sub-item">{{ __('Email Settings') }}</span>
                                            <span class="caret"></span>
                                        </a>
                                        <div class="collapse {{ request()->routeIs('user.basic_settings.mail_templates') || request()->routeIs('user.basic_settings.edit_mail_template') || request()->path() == 'user/mail/information/subscriber' ? 'show' : '' }}"
                                            id="emailset">
                                            <ul class="nav nav-collapse subnav">
                                                <li
                                                    class="
                                                @if (request()->path() == 'user/mail/information/subscriber') active @endif">
                                                    <a href="{{ route('user.mail.information') }}">
                                                        <span class="sub-item">{{ __('Mail Information') }}</span>
                                                    </a>
                                                </li>

                                                @if (
                                                    !empty($permissions) &&
                                                        (in_array('Ecommerce', $permissions) ||
                                                            in_array('Hotel Booking', $permissions) ||
                                                            in_array('Course Management', $permissions) ||
                                                            in_array('Donation Management', $permissions)))
                                                    <li
                                                        class="
                                                    @if (request()->routeIs('user.basic_settings.mail_templates')) active
                                                    @elseif (request()->routeIs('user.basic_settings.edit_mail_template')) active @endif">
                                                        <a
                                                            href="{{ route('user.basic_settings.mail_templates', ['language' => $default->code]) }}">
                                                            <span class="sub-item">{{ __('Mail Templates') }}</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </li>
                                @endif
                                @if ($userBs->theme != 'home_twelve')
                                    <!-- <li class="@if (request()->path() == 'user/logo') active @endif">
                                        <a href="{{ route('user.logo') }}">
                                            <span class="sub-item">{{ __('Logo') }}</span>
                                        </a>
                                    </li> -->
                                @endif
                                <!-- <li class="@if (request()->path() == 'user/breadcrumb') active @endif">
                                    <a href="{{ route('user.breadcrumb') }}">
                                        <span class="sub-item">{{ __('Breadcrumb') }}</span>
                                    </a>
                                </li> -->

                                <!-- <li class="@if (request()->path() == 'user/preloader') active @endif">
                                    <a href="{{ route('user.preloader') }}">
                                        <span class="sub-item">{{ __('Preloader') }}</span>
                                    </a>
                                </li> -->

                                <!-- <li class="@if (request()->path() == 'user/color') active @endif">
                                    <a href="{{ route('user.color.index') }}">
                                        <span class="sub-item">{{ __('Color Settings') }}</span>
                                    </a>
                                </li> -->

                                <li class="@if (request()->path() == 'user/css') active @endif">
                                    <a href="{{ route('user.css.index') }}">
                                        <span class="sub-item">{{ __('Custom CSS') }}</span>
                                    </a>
                                </li>

                                @if (!empty($permissions) && in_array('Plugins', $permissions))
                                    <li class="{{ request()->routeIs('user.plugins') ? 'active' : '' }}">
                                        <a href="{{ route('user.plugins') }}">
                                            <span class="sub-item">{{ __('Plugins') }}</span>
                                        </a>
                                    </li>
                                @endif

                                <li
                                    class="@if (request()->path() == 'user/social') active
                                @elseif(request()->is('user/social/*')) active @endif">
                                    <a href="{{ route('user.social.index') }}">
                                        <span class="sub-item">{{ __('Social Links') }}</span>
                                    </a>
                                </li>

                                <li class="@if (request()->path() == 'user/basic_settings/seo') active @endif">
                                    <a href="{{ route('user.basic_settings.seo', ['language' => $default->code]) }}">
                                        <span class="sub-item">{{ __('SEO Information') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/cookie-alert') active @endif">
                                    <a href="{{ route('user.cookie.alert') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Cookie Alert') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                  
                    @if (isset($userBs->theme) && $userBs->theme != 'home_twelve')
                        {{-- footer --}}

                    @endif
                @endif

                {{-- ----------advertisement---------- --}}

                {{-- ----------advertisement---------- --}}



                @if (!empty($permissions) && in_array('Service', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/services') active
                    @elseif(request()->routeIs('user.service.edit')) active @endif">
                        <a href="{{ route('user.services.index') . '?language=' . $default->code }}">
                            <i class="fas fa-hands"></i>
                            <p>{{ __('Services') }}</p>
                        </a>
                    </li>
                @endif
                @if (!empty($permissions) && in_array('Portfolio', $permissions) && $userBs->theme === 'home_twelve')
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/experiences') active
                    @elseif(request()->is('user/experience/*/edit')) active
                    @elseif(request()->path() == 'user/job-experiences') active
                    @elseif(request()->is('user/job-experience/*/edit')) active @endif">
                        <a data-toggle="collapse" href="#experience">
                            <i class="fas fa-user-cog"></i>
                            <p>{{ __('Exprience') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/experiences') show
                        @elseif(request()->is('user/experience/*/edit')) show
                        @elseif(request()->path() == 'user/job-experiences') show
                        @elseif(request()->is('user/job-experience/*/edit')) show @endif"
                            id="experience">
                            <ul class="nav nav-collapse">
                                <li
                                    class="
                                @if (request()->path() == 'user/job-experiences') active
                                @elseif(request()->is('user/job-experience/*/edit')) active @endif">
                                    <a
                                        href="{{ route('user.job.experiences.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">Work Exprience</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/experiences') active
                                @elseif(request()->is('user/experience/*/edit')) active @endif">
                                    <a href="{{ route('user.experience.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">Training</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif
                @if (!empty($permissions) && in_array('Portfolio', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/portfolio-categories') active
                    @elseif(request()->path() == 'user/portfolios') active
                    @elseif(request()->is('user/portfolio/*/edit')) active @endif">
                        <a data-toggle="collapse" href="#portfolio">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <p>{{ __('Portfolio') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/portfolio-categories') show
                        @elseif(request()->path() == 'user/portfolios') show
                        @elseif(request()->is('user/portfolio/*/edit')) show @endif"
                            id="portfolio">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'user/portfolio-categories') active @endif">
                                    <a
                                        href="{{ route('user.portfolio.category.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Category') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/portfolios') active
                                @elseif(request()->is('user/portfolio/*/edit')) active @endif">
                                    <a href="{{ route('user.portfolio.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Portfolios') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif



                @if (!empty($permissions) && in_array('Blog', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/blog-categories') active
                    @elseif(request()->path() == 'user/blogs') active
                    @elseif(request()->is('user/blog/*/edit')) active @endif">
                        <a data-toggle="collapse" href="#blog">
                            <i class="fas fa-blog"></i>
                            <p>{{ __('Blog') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/blog-categories') show
                        @elseif(request()->path() == 'user/blogs') show
                        @elseif(request()->is('user/blog/*/edit')) show @endif"
                            id="blog">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'user/blog-categories') active @endif">
                                    <a
                                        href="{{ route('user.blog.category.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Category') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/blogs') active
                                @elseif(request()->is('user/blog/*/edit')) active @endif">
                                    <a href="{{ route('user.blog.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Blog') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif



                @if (!is_null($package))
                    {{-- faq --}}
                    <li class="nav-item {{ request()->routeIs('user.faq_management') ? 'active' : '' }}">
                        <a href="{{ route('user.faq_management') . '?language=' . $default->code }}">
                            <i class="la flaticon-round"></i>
                            <p>{{ __('FAQ Management') }}</p>
                        </a>
                    </li>
                @endif
<!-- 
                @if (!empty($permissions) && in_array('Career', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/jcategorys') active
                    @elseif(request()->path() == 'user/job/create') active
                    @elseif(request()->is('user/jcategory/*/edit')) active
                    @elseif(request()->path() == 'user/jobs') active
                    @elseif(request()->is('user/job/*/edit')) active @endif">
                        <a data-toggle="collapse" href="#career">
                            <i class="fas fa-user-md"></i>
                            <p>{{ __('Career') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/jcategorys') show
                        @elseif(request()->path() == 'user/job/create') show
                        @elseif(request()->is('user/jcategory/*/edit')) show
                        @elseif(request()->path() == 'user/jobs') show
                        @elseif(request()->is('user/job/*/edit')) show @endif"
                            id="career">
                            <ul class="nav nav-collapse subnav">
                                <li
                                    class="
                                @if (request()->path() == 'user/jcategorys') active
                                @elseif(request()->is('user/jcategory/*/edit')) active @endif">
                                    <a href="{{ route('user.jcategory.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Category') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->is('user/job/create')) active @endif">
                                    <a href="{{ route('user.job.create') }}">
                                        <span class="sub-item">{{ __('Post Job') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/jobs') active
                                @elseif(request()->is('user/job/*/edit')) active @endif">
                                    <a href="{{ route('user.job.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Job Management') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif -->






                @if (!empty($permissions) && in_array('Custom Page', $permissions))
                    {{-- Custom Pages --}}
                    <li
                        class="nav-item
                @if (request()->path() == 'user/page/create') active
                @elseif(request()->path() == 'user/pages') active
                @elseif(request()->is('user/page/*/edit')) active @endif">
                        <a data-toggle="collapse" href="#pages">
                            <i class="la flaticon-file"></i>
                            <p>{{ __('Custom Page') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                    @if (request()->path() == 'user/page/create') show
                    @elseif(request()->path() == 'user/pages') show
                    @elseif(request()->is('user/page/*/edit')) show @endif"
                            id="pages">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'user/page/create') active @endif">
                                    <a href="{{ route('user.page.create') }}">
                                        <span class="sub-item">{{ __('Create Page') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                            @if (request()->path() == 'user/pages') active
                            @elseif(request()->is('user/page/*/edit')) active @endif">
                                    <a href="{{ route('user.page.index') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Pages') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                @if (!empty($permissions) && in_array('Request a Quote', $permissions))
                    {{-- Quotes --}}
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/quote/form') active
                    @elseif(request()->is('user/quote/*/inputEdit')) active
                    @elseif(request()->path() == 'user/all/quotes') active
                    @elseif(request()->path() == 'user/pending/quotes') active
                    @elseif(request()->path() == 'user/processing/quotes') active
                    @elseif(request()->path() == 'user/completed/quotes') active
                    @elseif(request()->path() == 'user/rejected/quotes') active
                    @elseif(request()->path() == 'user/quote/visibility') active @endif">
                        <a data-toggle="collapse" href="#quote">
                            <i class="fas fa-quote-left"></i>
                            <p>{{ __('Quote Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/quote/form') show
                        @elseif(request()->is('user/quote/*/inputEdit')) show
                        @elseif(request()->path() == 'user/all/quotes') show
                        @elseif(request()->path() == 'user/pending/quotes') show
                        @elseif(request()->path() == 'user/processing/quotes') show
                        @elseif(request()->path() == 'user/completed/quotes') show
                        @elseif(request()->path() == 'user/rejected/quotes') show
                        @elseif(request()->path() == 'user/quote/visibility') show @endif"
                            id="quote">
                            <ul class="nav nav-collapse">
                                <li
                                    class="
                                @if (request()->path() == 'user/quote/visibility') active @endif">
                                    <a href="{{ route('user.quote.visibility') }}">
                                        <span class="sub-item">{{ __('Visibility') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/quote/form') active
                                @elseif(request()->is('user/quote/*/inputEdit')) active @endif">
                                    <a href="{{ route('user.quote.form') . '?language=' . $default->code }}">
                                        <span class="sub-item">{{ __('Form Builder') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/all/quotes') active @endif">
                                    <a href="{{ route('user.all.quotes') }}">
                                        <span class="sub-item">{{ __('All Quotes') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/pending/quotes') active @endif">
                                    <a href="{{ route('user.pending.quotes') }}">
                                        <span class="sub-item">{{ __('Pending Quotes') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/processing/quotes') active @endif">
                                    <a href="{{ route('user.processing.quotes') }}">
                                        <span class="sub-item">{{ __('Processing Quotes') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/completed/quotes') active @endif">
                                    <a href="{{ route('user.completed.quotes') }}">
                                        <span class="sub-item">{{ __('Completed Quotes') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/rejected/quotes') active @endif">
                                    <a href="{{ route('user.rejected.quotes') }}">
                                        <span class="sub-item">{{ __('Rejected Quotes') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                @if (!empty($permissions) && in_array('QR Builder', $permissions))
                    <li
                        class="nav-item
                    @if (request()->routeIs('user.qrcode')) active
                    @elseif(request()->routeIs('user.qrcode.index')) active @endif">
                        <a data-toggle="collapse" href="#qrcode">
                            <i class="fas fa-qrcode"></i>
                            <p>{{ __('QR Codes') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->routeIs('user.qrcode')) show
                        @elseif(request()->routeIs('user.qrcode.index')) show @endif"
                            id="qrcode">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->routeIs('user.qrcode')) active @endif">
                                    <a href="{{ route('user.qrcode') }}">
                                        <span class="sub-item">{{ __('Generate QR Code') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->routeIs('user.qrcode.index')) active @endif">
                                    <a href="{{ route('user.qrcode.index') }}">
                                        <span class="sub-item">{{ __('Saved QR Codes') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif

                @if (!empty($permissions) && in_array('vCard', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/vcard') active
                    @elseif(request()->path() == 'user/vcard/create') active
                    @elseif(request()->is('user/vcard/*/edit')) active
                    @elseif(request()->routeIs('user.vcard.services')) active
                    @elseif(request()->routeIs('user.vcard.projects')) active
                    @elseif(request()->routeIs('user.vcard.testimonials')) active
                    @elseif(request()->routeIs('user.vcard.about')) active
                    @elseif(request()->routeIs('user.vcard.preferences')) active
                    @elseif(request()->routeIs('user.vcard.color')) active
                    @elseif(request()->routeIs('user.vcard.keywords')) active @endif">
                        <a data-toggle="collapse" href="#vcard">
                            <i class="far fa-address-card"></i>
                            <p>{{ __('vCards Management') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/vcard') show
                        @elseif(request()->path() == 'user/vcard/create') show
                        @elseif(request()->is('user/vcard/*/edit')) show
                        @elseif(request()->routeIs('user.vcard.services')) show
                        @elseif(request()->routeIs('user.vcard.projects')) show
                        @elseif(request()->routeIs('user.vcard.testimonials')) show
                        @elseif(request()->routeIs('user.vcard.about')) show
                        @elseif(request()->routeIs('user.vcard.preferences')) show
                        @elseif(request()->routeIs('user.vcard.color')) show
                        @elseif(request()->routeIs('user.vcard.keywords')) show @endif"
                            id="vcard">
                            <ul class="nav nav-collapse">
                                <li
                                    class="@if (request()->path() == 'user/vcard') active
                            @elseif(request()->is('user/vcard/*/edit')) active
                            @elseif(request()->routeIs('user.vcard.services')) active
                            @elseif(request()->routeIs('user.vcard.projects')) active
                            @elseif(request()->routeIs('user.vcard.testimonials')) active
                            @elseif(request()->routeIs('user.vcard.about')) active
                            @elseif(request()->routeIs('user.vcard.preferences')) active
                            @elseif(request()->routeIs('user.vcard.color')) active
                            @elseif(request()->routeIs('user.vcard.keywords')) active @endif">
                                    <a href="{{ route('user.vcard') }}">
                                        <span class="sub-item">{{ __('vCards') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/vcard/create') active @endif">
                                    <a href="{{ route('user.vcard.create') }}">
                                        <span class="sub-item">{{ __('Add vCard') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif
<!-- 
                @if (!empty($permissions) && in_array('Follow/Unfollow', $permissions))
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/follower-list') active
                    @elseif(request()->path() == 'user/following-list') active @endif">
                        <a data-toggle="collapse" href="#follow">
                            <i class="fas fa-user-friends"></i>
                            <p>{{ __('Follower/Following') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/follower-list') show
                        @elseif(request()->path() == 'user/following-list') show @endif"
                            id="follow">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'user/follower-list') active @endif">
                                    <a href="{{ route('user.follower.list') }}">
                                        <span class="sub-item">{{ __('Follower') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="
                                @if (request()->path() == 'user/following-list') active
                                @elseif(request()->is('user/following-list')) active @endif">
                                    <a href="{{ route('user.following.list') }}">
                                        <span class="sub-item">{{ __('Following') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                @endif -->
<!-- 
                @if (!is_null($package))
                    {{-- Subscribers --}}
                    <li
                        class="nav-item
                    @if (request()->path() == 'user/subscribers') active
                    @elseif(request()->path() == 'user/mailsubscriber') active @endif">
                        <a data-toggle="collapse" href="#subscribers">
                            <i class="la flaticon-envelope"></i>
                            <p>{{ __('Subscribers') }}</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse
                        @if (request()->path() == 'user/subscribers') show
                        @elseif(request()->path() == 'user/mailsubscriber') show @endif"
                            id="subscribers">
                            <ul class="nav nav-collapse">
                                <li class="@if (request()->path() == 'user/subscribers') active @endif">
                                    <a href="{{ route('user.subscriber.index') }}">
                                        <span class="sub-item">{{ __('Subscribers') }}</span>
                                    </a>
                                </li>
                                <li class="@if (request()->path() == 'user/mailsubscriber') active @endif">
                                    <a href="{{ route('user.mailsubscriber') }}">
                                        <span class="sub-item">{{ __('Mail to Subscribers') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    {{-- Language Management Page --}}

                @endif
                @if (!is_null($package) && $userBs->theme == 'home_twelve')
                    <li class="nav-item
                    @if (request()->path() == 'user/cv-upload') active @endif">
                        <a href="{{ route('user.cv.upload', ['language' => $default->code]) }}">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>{{ $keywords['Upload_CV'] ?? __('Upload CV') }}</p>
                        </a>
                    </li>
                @endif -->

            </ul>
        </div>
    </div>
</div>
