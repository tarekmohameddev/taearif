@extends('user.layout')

@section('content')
    <h1>Create Contract</h1>

    <form action="{{ route('contracts.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="customer_id">Customer</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->username }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="contract_value">Contract Value</label>
            <input type="number" step="0.01" name="contract_value" id="contract_value" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="contract_type">Contract Type</label>
            <select name="contract_type" id="contract_type" class="form-control" required>
                    <option value="Standard">Standard</option>
                    <option value="Contracts under Seal">Contracts under Seal</option>
                    <option value="Lease Agreement">Lease Agreement</option>
                    <option value="Other">Other</option>
                </select>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control"></textarea>
        </div>

        <div>
            <label for="is_signed">Is Signed</label>
            <input type="hidden" name="is_signed" value="0">
            <select name="contract_status" required>
                <option value="draft">Draft</option>
                <option value="signed">Signed</option>
                <option value="expired">Expired</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Create</button>
    </form>
@endsection
