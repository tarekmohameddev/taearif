@extends('user.layout')

@section('content')
<div class="container">
    <h2>Reservations</h2>
    <a href="{{ route('crm.reservations.create') }}" class="btn btn-primary">Create Reservation</a>
    <table class="table mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Property</th>
                <th>amount</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Payment Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $reservation)
                <tr>
                    <td>{{ $reservation->id }}</td>
                    <td>{{ $reservation->property->title ?? 'N/A' }}</td>
                    <td>{{ $reservation->amount ?? 'N/A' }}</td>
                    <td>{{ $reservation->customer->username ?? 'N/A' }}</td>
                    <td>{{ ucfirst($reservation->status) }}</td>
                    <td>{{ ucfirst($reservation->payment_status) }}</td>
                    <td>{{ $reservation->reservation_date }}</td>
                    <td>
                        <a href="{{ route('crm.reservations.edit', $reservation->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('crm.reservations.destroy', $reservation->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $reservations->links() }}
</div>
@endsection
