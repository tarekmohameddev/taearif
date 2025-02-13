@extends('user.layout')

@section('content')
<div class="user-area-section section-gap">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                @if (Session::has('warning'))
                <div class="alert alert-danger text-danger">{{ Session::get('warning') }}</div>
                @endif
                @if (Session::has('sendmail'))
                    <div class="alert alert-success mb-4">
                        <p>{{ __(Session::get('sendmail')) }}</p>
                    </div>
                @endif
                </div>
        </div>
    </div>
</div>



    <h2>Create New Customer</h2>
    <form action="{{ route('crm.customers.store',$user) }}" method="POST">
        @csrf

        <!-- First Name -->
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
            @error('first_name')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Last Name -->
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
            @error('last_name')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
        <!-- username -->
        <div class="mb-3">
            <label for="last_name" class="form-label">username</label>
            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required>
            @error('username')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Phone -->
        <div class="mb-3">
            <label for="contact_number" class="form-label">Phone</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
            @error('contact_number')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Address -->
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}">
            @error('address')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="Password"  class="form-label">{{ $keywords['Password'] ?? 'Password' }} **</label>
            <input type="password" class="form-control" placeholder="{{ $keywords['Enter_Password'] ?? 'Enter_Password' }}"  name="password"
                value="{{ old('password') }}">
            @error('password')
                <p class="text-danger">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-3">
            <label for="Confirm_Password"  class="form-label">{{ $keywords['Confirm_Password'] ?? 'Confirm Password' }} **</label>
            <input type="password" class="form-control" placeholder="{{ $keywords['Enter_Password_Again'] ?? 'Enter Password Again' }}"
                name="password_confirmation" value="{{ old('password_confirmation') }}">
            @error('password_confirmation')
                <p class="text-danger">{{ $message }}</p>
            @enderror
        </div>
        <!-- Status -->
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="1" selected>Active</option>
                <option value="0">Inactive</option>
            </select>
            @error('status')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Create Customer</button>
    </form>
@endsection
