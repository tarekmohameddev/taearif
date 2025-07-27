<div class="item">
  <div class="dropdown">
    <!--Bootstrap 5 dropdown -->
    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      @if (!Auth::guard('api_customer')->check())
        {{ $keywords['Register'] ?? __('Register') }}
      @else
        {{ Auth::guard('api_customer')->user()->name }}
      @endif
    </button>

    <ul class="dropdown-menu radius-0">
      @if (in_array('Ecommerce', $packagePermissions) ||
          in_array('Hotel Booking', $packagePermissions) ||
          in_array('Course Management', $packagePermissions) ||
          in_array('Real Estate Management', $packagePermissions) ||
          in_array('Donation Management', $packagePermissions))

        @guest('api_customer')

          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
              {{ $keywords['Log-In'] ?? __('Log-In') }}
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
              {{ $keywords['Signup Now'] ?? __('Signup Now') }}
            </a>
          </li>
        @endguest

        @auth('api_customer')
          <li>
            <a class="dropdown-item" href="{{ route('customer.api_dashboard', getParam()) }}">
              {{ $keywords['Dashboard'] ?? __('Dashboard') }}
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('customer.api_logout', getParam()) }}">
              {{ $keywords['Logout'] ?? __('Logout') }}
            </a>
          </li>
        @endauth
      @endif
    </ul>
  </div>
</div>
