@extends('user.layout')

@section('content')
    <h1>Contracts</h1>
    <a href="{{ route('contracts.create') }}" class="btn btn-primary mb-3">Create New Contract</a>
    <table class="table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Customer</th>
                <th>Value</th>
                <th>Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Signed</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contracts as $contract)
                <tr>
                    <td>{{ $contract->subject }}
                    <div class="row-options">
                    <a href="{{ route('contracts.show', $contract) }}" target="_blank">View</a> |
                    <a href="{{ route('contracts.edit', $contract) }}">Edit </a>
                </div>
                </td>
                    <td>{{ $contract->customer->username ?? 'N/A' }}</td>
                    <td>{{ $contract->contract_value }}</td>
                    <td>{{ $contract->contract_type }}</td>
                    <td>{{ $contract->start_date }}</td>
                    <td>{{ $contract->end_date }}</td>
                    <td>
                        @php
                            $status = $contract->contract_status;
                        @endphp
                        @if($status == 'draft')
                            <span class="badge bg-warning">Draft</span>
                        @elseif($status == 'signed')
                            <span class="badge bg-success">Signed</span>
                        @elseif($status == 'expired')
                            <span class="badge bg-danger">Expired</span>
                        @else
                            <span class="badge bg-secondary">Unknown</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('contracts.destroy', $contract) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
