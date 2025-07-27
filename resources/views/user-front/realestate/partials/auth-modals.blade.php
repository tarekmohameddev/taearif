<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">{{ $keywords['Login'] ?? __('Log In') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <form action="{{ route('customer.api_login.submit', getParam()) }}" method="POST">
          @csrf
          <input type="hidden" name="user_id" value="{{ $user->id }}">

          <div class="form-group">
            <label>{{ $keywords['Email_or_Phone'] ?? __('Email or Phone Number') }} *</label>
            <input type="text" class="form-control" name="identifier"
              placeholder="{{ $keywords['Enter_Email_or_Phone'] ?? __('Enter Email or Phone Number') }}"
              value="{{ old('identifier') }}">
            @error('identifier')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label>{{ $keywords['Password'] ?? __('Password') }} *</label>
            <input type="password" class="form-control" name="password"
              placeholder="{{ $keywords['Enter_Password'] ?? __('Enter Password') }}">
            @error('password')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          @if ($userBs->is_recaptcha == 1)
            <div class="mb-3">
              {!! NoCaptcha::renderJs() !!}
              {!! NoCaptcha::display() !!}
              @if ($errors->has('g-recaptcha-response'))
                <p class="text-danger mt-2">{{ $errors->first('g-recaptcha-response') }}</p>
              @endif
            </div>
          @endif

          <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('customer.api_forgot_password', getParam()) }}">
              {{ $keywords['Lost_your_password'] ?? __('Lost your password') }}?
            </a>
          </div>

          <button type="submit" class="btn btn-primary w-100 " >
            {{ $keywords['Login_Now'] ?? __('Login Now') }}
          </button>

          <div class="text-center mt-3">
            <p class="text-muted">
              {{ $keywords['New_user'] ?? 'New user' }}?
              <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">
                {{ $keywords['Donot_have_an_account'] ?? "Don't have an account?" }}
              </a>
            </p>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>



<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="registerModalLabel">{{ $keywords['Signup'] ?? __('Signup') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <form action="{{ route('customer.api_signup.submit', getParam()) }}" method="POST">
          @csrf
          <input type="hidden" name="user_id" value="{{ $user->id }}">

          <div class="form-group">
            <label>{{ $keywords['Name'] ?? 'Name' }} *</label>
            <input type="text" class="form-control" name="name"
              placeholder="{{ $keywords['Enter_Name'] ?? 'Enter Name' }}" value="{{ old('name') }}">
            @error('name')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label>{{ $keywords['Email_Address'] ?? 'Email Address' }}</label>
            <input type="email" class="form-control" name="email"
              placeholder="{{ $keywords['Enter_Email_Address'] ?? 'Enter Email Address' }}" value="{{ old('email') }}">
            @error('email')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label>{{ $keywords['Phone_Number'] ?? 'Phone Number' }}</label>
            <input type="text" class="form-control" name="phone_number"
              placeholder="{{ $keywords['Enter_Phone_Number'] ?? __('Enter Phone Number') }}" value="{{ old('phone_number') }}">
            @error('phone_number')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label>{{ $keywords['Password'] ?? 'Password' }} *</label>
            <input type="password" class="form-control" name="password"
              placeholder="{{ $keywords['Enter_Password'] ?? __('Enter Password') }}">
            @error('password')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group">
            <label>{{ $keywords['Confirm_Password'] ?? 'Confirm Password' }} *</label>
            <input type="password" class="form-control" name="password_confirmation"
              placeholder="{{ $keywords['Enter_Password_Again'] ?? __('Enter Password Again') }}">
            @error('password_confirmation')
              <p class="text-danger">{{ $message }}</p>
            @enderror
          </div>

          @if ($userBs->is_recaptcha == 1)
            <div class="mb-3">
              {!! NoCaptcha::renderJs() !!}
              {!! NoCaptcha::display() !!}
              @if ($errors->has('g-recaptcha-response'))
                <p class="text-danger mt-2">{{ $errors->first('g-recaptcha-response') }}</p>
              @endif
            </div>
          @endif

          <button type="submit" class="btn btn-primary w-100 ">
            {{ $keywords['Signup'] ?? 'Signup!' }}
          </button>

          <div class="text-center mt-3">
            <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">
              {{ $keywords['Back_to_Login'] ?? __('Back to Login') }}
            </a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

