@extends('user.layout')

@section('content')
<h1>Customers</h1>
    <a href="{{route('crm.customers.create')}}" class="btn btn-primary mb-3">Create New Customer</a>

<div class="contaner">
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                    <tr>
                        <td>{{ $customer->first_name }}</td>
                        <td>{{ $customer->last_name }}</td>
                        <td>{{ $customer->username }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->contact_number }}</td>
                        <td>{{ $customer->status ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <a href="{{route('crm.customers.edit',$customer->id)}}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{route('crm.customers.delete',$customer->id)}}" method="get" style="display:inline;">

                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        <div class="pagination">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection
