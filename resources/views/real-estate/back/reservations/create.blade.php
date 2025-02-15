@extends('user.layout')

@section('content')
<div class="container">
    <h2>Create Reservation</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('crm.reservations.store') }}" method="POST">
        @csrf
        <div class="card p-3 my-3">
            <h4>Reservation Details</h4>
            <div class="row">
                <div class="col-md-6">
                    <label for="property_id" class="form-label">Property</label>
                    <select class="form-control @error('property_id') is-invalid @enderror" id="property_id" name="property_id" required>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->title ?? $property->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('property_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->username ?? $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-6">
                    <label for="reservation_date" class="form-label">Reservation Date</label>
                    <input type="date" class="form-control @error('reservation_date') is-invalid @enderror" id="reservation_date" name="reservation_date" value="{{ old('reservation_date') }}" required>
                    @error('reservation_date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="0.01"
                           value="{{ old('amount') }}">
                    @error('reservation_date')
                     <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select class="form-control @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status">
                        <option value="pending" {{ old('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ old('payment_status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    @error('payment_status')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Reservation</button>
        <a href="{{ route('crm.reservations.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
