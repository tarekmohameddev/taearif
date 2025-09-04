@php
    // Get customer dropdown settings from the current user/tenant
    $user = getUser();
    $dropdownSettings = null;
    
    if ($user) {
        try {
            $dropdownSettings = \App\Models\Api\CustomerDropdownSetting::where('user_id', $user->id)->first();
        } catch (Exception $e) {
            // Fallback to default settings if there's an error
            $dropdownSettings = null;
        }
    }
    
    // Default settings if none found
    $isVisible = $dropdownSettings ? $dropdownSettings->is_visible : true;
    $showLogin = $dropdownSettings ? $dropdownSettings->show_login : true;
    $showRegister = $dropdownSettings ? $dropdownSettings->show_register : true;
    $showDashboard = $dropdownSettings ? $dropdownSettings->show_dashboard : true;
    $showLogout = $dropdownSettings ? $dropdownSettings->show_logout : true;
@endphp

@if($isVisible)
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
          @if($showLogin)
          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
              {{ $keywords['Log-In'] ?? __('Log-In') }}
            </a>
          </li>
          @endif
          
          @if($showRegister)
          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
              {{ $keywords['Signup Now'] ?? __('Signup Now') }}
            </a>
          </li>
          @endif
        @endguest

        @auth('api_customer')
          @if($showDashboard)
          <li>
            <a class="dropdown-item" href="{{ route('customer.api_dashboard', getParam()) }}">
              {{ $keywords['Dashboard'] ?? __('Dashboard') }}
            </a>
          </li>
          @endif
          
          @if($showLogout)
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
