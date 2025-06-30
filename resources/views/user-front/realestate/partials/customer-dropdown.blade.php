<div class="item">
    <div class="dropdown">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    {{ $keywords['Signup'] ?? __('Signup') }}
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
