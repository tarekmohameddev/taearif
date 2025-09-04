@if($customer_dropdown_visible)
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
      @guest('api_customer')
        @if($customer_dropdown_show_login)
        <li>
          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
            {{ $keywords['Log-In'] ?? __('Log-In') }}
          </a>
        </li>
        @endif
        
        @if($customer_dropdown_show_register)
        <li>
          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
            {{ $keywords['Signup Now'] ?? __('Signup Now') }}
          </a>
        </li>
        @endif
      @endguest

      @auth('api_customer')
        @if($customer_dropdown_show_dashboard)
        <li>
          <a class="dropdown-item" href="{{ route('customer.api_dashboard', getParam()) }}">
            {{ $keywords['Dashboard'] ?? __('Dashboard') }}
          </a>
        </li>
        @endif
        
        @if($customer_dropdown_show_logout)
        <li>
          <a class="dropdown-item" href="{{ route('customer.api_logout', getParam()) }}">
            {{ $keywords['Logout'] ?? __('Logout') }}
          </a>
        </li>
        @endif
      @endauth
    </ul>
  </div>
</div>
@endif
