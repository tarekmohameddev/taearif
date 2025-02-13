@extends('user.layout')

@section('content')
<h1>Sales</h1>
    <a href="{{ route('crm.sales.create') }}" class="btn btn-primary mb-3">Create New Sale</a>

<div class="container">
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <th>Property title </th>
                    <th class="d-none">User</th>
                    <th>Contract</th>
                    <th>Sale Price</th>
                    <th>Sale Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sales as $sale)
                    <tr>
                        <td>{{ $sale->property->contents->first()?->title ?? 'N/A' }}</td>
                        <td class="d-none">{{ $sale->user->username ?? 'N/A' }}</td>
                        <td>{{ $sale->contract->subject ?? 'N/A' }}</td>
                        <td>${{ number_format($sale->sale_price, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d') }}</td>
                        <td>{{ ucfirst($sale->status) }}</td>
                        <td>
                            <a href="{{ route('crm.sales.edit', $sale->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('crm.sales.destroy', $sale->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
