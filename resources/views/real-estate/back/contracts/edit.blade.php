@extends('user.layout')

@section('content')
    <h1>Edit Contract</h1>
    <form action="{{ route('contracts.update', $contract->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="customer_id">Customer</label>
            <select name="customer_id" id="customer_id" class="form-control" required>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ $contract->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->username }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" value="{{ $contract->subject }}" required>
        </div>

        <div class="form-group">
            <label for="contract_value">Contract Value</label>
            <input type="number" step="0.01" name="contract_value" id="contract_value" class="form-control" value="{{ $contract->contract_value }}" required>
        </div>

        <div class="form-group">
            <label for="contract_type">Contract Type</label>
                <select name="contract_type" id="contract_type" class="form-control" required>
                    <option value="Standard" {{ $contract->contract_type == 'Standard' ? 'selected' : '' }}>Standard</option>
                    <option value="Contracts under Seal" {{ $contract->contract_type == 'Contracts under Seal' ? 'selected' : '' }}>Contracts under Seal</option>
                    <option value="Lease Agreement" {{ $contract->contract_type == 'Lease Agreement' ? 'selected' : '' }}>Lease Agreement</option>
                    <option value="Other" {{ $contract->contract_type == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $contract->start_date }}" required>
        </div>

        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $contract->end_date }}" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control"style="height: 120px;">{{ $contract->description }}</textarea>
        </div>


        <div>
            <label for="is_signed">Is Signed</label>
            <input type="hidden" name="contract_status" value="0">
            <select name="contract_status" required>
                <option value="draft" {{ old('contract_status', $contract->contract_status) === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="signed" {{ old('contract_status', $contract->contract_status) === 'signed' ? 'selected' : '' }}>Signed</option>
                <option value="expired" {{ old('contract_status', $contract->contract_status) === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>

        </div>



        <button type="submit" class="btn btn-primary">Update</button>
    </form>
@endsection
