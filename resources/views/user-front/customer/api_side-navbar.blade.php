@php
    $shopSettings = App\Models\User\UserShopSetting::where('user_id', $user->id)->first();

    $donation = DB::table('user_donation_settings')
        ->where('user_id', $user->id)
        ->first();
    $room = DB::table('user_room_settings')
        ->where('user_id', $user->id)
        ->first();

@endphp
<div class="col-lg-3">
    <div class="user-sidebar mb-40">
        <ul class="links">
            <li>
                <a class="@if (request()->routeIs('customer.api_dashboard')) active @endif"
                    href="{{ route('customer.api_dashboard', getParam()) }}"><i class="fal fa-tachometer-alt"></i>
                    {{ $keywords['Dashboard'] ?? __('Dashboard') }}</a>
            </li>
            <li class="d-none">
                <a class=" @if (request()->routeIs('customer.edit_profile')) active @endif"
                    href="{{ route('customer.edit_profile', getParam()) }}"><i class="fal fa-user"></i>
                    {{ $keywords['my_profile'] ?? __('My Profile') }}</a>
            </li>
            <li class="d-none">
                <a class=" @if (request()->routeIs('customer.change_password')) active @endif"
                    href="{{ route('customer.change_password', getParam()) }}"><i class="fal fa-unlock-alt"></i>
                    {{ $keywords['Change_Password'] ?? __('Change Password') }} </a>
            </li>
            <li>
                <a href="{{ route('customer.api_logout', getParam()) }}"><i class="fal fa-sign-out"></i>
                    {{ $keywords['Signout'] ?? __('Sign out') }}</a>
            </li>
        </ul>
    </div>
</div>
