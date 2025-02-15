@extends('user.layout')

@section('content')
<div class="container">
    <h2>Edit Reservation</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('crm.reservations.update', $reservation->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card p-3 my-3">
            <h4>Reservation Details</h4>
            <div class="row">
            <div class="col-md-6">
                <label for="property" class="form-label">Property</label>
                <input type="text" class="form-control" id="property" value="{{ $reservation->property->title ?? 'N/A' }}" >
                <input type="hidden" name="property_id" value="{{ $reservation->property_id }}">
                @error('property_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>


                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" {{ $reservation->customer_id == $customer->id ? 'selected' : '' }}>
                                {{ $customer->username }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="reservation_date" class="form-label">Reservation Date</label>
                    <input type="date" class="form-control @error('reservation_date') is-invalid @enderror" id="reservation_date" name="reservation_date"
                           value="{{ old('reservation_date', $reservation->reservation_date ? \Carbon\Carbon::parse($reservation->reservation_date)->format('Y-m-d') : '') }}" required>
                    @error('reservation_date') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="pending" {{ $reservation->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $reservation->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ $reservation->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="0.01"
                           value="{{ old('amount', $reservation->amount) }}">
                    @error('amount') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select class="form-control @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status">
                        <option value="pending" {{ $reservation->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ $reservation->payment_status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ $reservation->payment_status == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="refunded" {{ $reservation->payment_status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                        <option value="partially_paid" {{ $reservation->payment_status == 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="cancelled" {{ $reservation->payment_status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="chargeback" {{ $reservation->payment_status == 'chargeback' ? 'selected' : '' }}>Chargeback</option>
                    </select>
                    @error('payment_status') <div class="text-danger">{{ $message }}</div> @enderror
                </div>


            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Reservation</button>
        <a href="{{ route('crm.reservations.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
