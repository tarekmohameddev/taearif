@extends('user.layout')

@section('content')
<h1>{{ __('Customers') }}</h1>
    <a href="{{route('crm.customers.create')}}" class="btn btn-primary mb-3">{{ __('Create New Customer') }}</a>

<div class="contaner">
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('First Name') }} </th>
                    <th>{{ __('Last Name') }} </th>
                    <th>{{ __('Username') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Contact Number') }} </th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
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
                            <a href="{{route('crm.customers.edit',$customer->id)}}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                            <form action="{{route('crm.customers.delete',$customer->id)}}" method="get" style="display:inline;">

                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">{{ __('Delete') }}</button>
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
