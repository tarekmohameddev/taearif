@extends('admin.layout')

@section('content')
<style>
.status-dropdown {
    min-width: 120px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 14px;
    background-color: #fff;
    color: #333;
}

.status-dropdown:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

.status-dropdown option {
    padding: 8px 12px;
}

/* Status-specific styling for better visual feedback */
.status-dropdown[data-status="completed"] {
    border-color: #28a745;
    background-color: #f8fff9;
}

.status-dropdown[data-status="pending"] {
    border-color: #ffc107;
    background-color: #fffdf5;
}

.status-dropdown[data-status="failed"] {
    border-color: #dc3545;
    background-color: #fff8f8;
}

.status-dropdown[data-status="refunded"] {
    border-color: #17a2b8;
    background-color: #f5fcff;
}
</style>
<div class="page-header">
    <h4 class="page-title">{{ __('Credit Transactions') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Credit Management') }}</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <form action="{{ route('admin.credit.transactions.index') }}" class="form-inline float-right">
                    <input name="user_name" class="form-control mr-2" type="text" placeholder="{{__('Search by User')}}" value="{{ request()->input('user_name') }}">
                    <select name="transaction_type" class="form-control mr-2">
                        <option value="">{{__('All Types')}}</option>
                        <option value="purchase" {{ request()->input('transaction_type') == 'purchase' ? 'selected' : '' }}>{{__('Purchase')}}</option>
                        <option value="usage" {{ request()->input('transaction_type') == 'usage' ? 'selected' : '' }}>{{__('Usage')}}</option>
                        <option value="refund" {{ request()->input('transaction_type') == 'refund' ? 'selected' : '' }}>{{__('Refund')}}</option>
                        <option value="admin_add" {{ request()->input('transaction_type') == 'admin_add' ? 'selected' : '' }}>{{__('Admin Add')}}</option>
                        <option value="admin_remove" {{ request()->input('transaction_type') == 'admin_remove' ? 'selected' : '' }}>{{__('Admin Remove')}}</option>
                    </select>
                    <select name="status" class="form-control mr-2">
                        <option value="">{{__('All Status')}}</option>
                        <option value="pending" {{ request()->input('status') == 'pending' ? 'selected' : '' }}>{{__('Pending')}}</option>
                        <option value="completed" {{ request()->input('status') == 'completed' ? 'selected' : '' }}>{{__('Completed')}}</option>
                        <option value="failed" {{ request()->input('status') == 'failed' ? 'selected' : '' }}>{{__('Failed')}}</option>
                        <option value="refunded" {{ request()->input('status') == 'refunded' ? 'selected' : '' }}>{{__('Refunded')}}</option>
                    </select>
                    <select name="payment_method" class="form-control mr-2">
                        <option value="">{{__('All Methods')}}</option>
                        <option value="arb" {{ request()->input('payment_method') == 'arb' ? 'selected' : '' }}>{{__('ARB')}}</option>
                        <option value="myfatoorah" {{ request()->input('payment_method') == 'myfatoorah' ? 'selected' : '' }}>{{__('MyFatoorah')}}</option>
                        <option value="test" {{ request()->input('payment_method') == 'test' ? 'selected' : '' }}>{{__('Test')}}</option>
                        <option value="admin" {{ request()->input('payment_method') == 'admin' ? 'selected' : '' }}>{{__('Admin')}}</option>
                    </select>
                    <input name="from_date" class="form-control mr-2" type="date" placeholder="{{__('From Date')}}" value="{{ request()->input('from_date') }}">
                    <input name="to_date" class="form-control mr-2" type="date" placeholder="{{__('To Date')}}" value="{{ request()->input('to_date') }}">
                    <button type="submit" class="btn btn-primary btn-sm">{{__('Search')}}</button>
                </form>
                <div class="card-title">{{ __('All Credit Transactions') }}</div>
            </div>

            <div class="card-body">
                @if ($transactions->isEmpty())
                    <h4 class="text-center">{{ __('NO TRANSACTIONS FOUND') }}</h4>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Credits') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Payment Method') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->reference_number }}</td>
                                    <td>{{ optional($transaction->user)->name ?? optional($transaction->user)->username ?? '-' }}</td>
                                    <td>
                                        @php
                                            $typeColors = [
                                                'purchase' => 'badge-primary',
                                                'usage' => 'badge-info',
                                                'refund' => 'badge-warning',
                                                'admin_add' => 'badge-success',
                                                'admin_remove' => 'badge-danger',
                                            ];
                                            $typeLabels = [
                                                'purchase' => __('Purchase'),
                                                'usage' => __('Usage'),
                                                'refund' => __('Refund'),
                                                'admin_add' => __('Admin Add'),
                                                'admin_remove' => __('Admin Remove'),
                                            ];
                                        @endphp
                                        <span class="badge {{ $typeColors[$transaction->transaction_type] ?? 'badge-secondary' }}">
                                            {{ $typeLabels[$transaction->transaction_type] ?? $transaction->transaction_type }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($transaction->credits_amount > 0)
                                            <span class="text-success">+{{ $transaction->credits_amount }}</span>
                                        @else
                                            <span class="text-danger">{{ $transaction->credits_amount }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->amount_paid > 0)
                                            <span class="text-success">{{ $transaction->amount_paid }} {{ $transaction->currency }}</span>
                                        @elseif($transaction->amount_paid < 0)
                                            <span class="text-danger">{{ $transaction->amount_paid }} {{ $transaction->currency }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($transaction->payment_method) }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-warning text-white',
                                                'completed' => 'bg-success text-white',
                                                'failed' => 'bg-danger text-white',
                                                'refunded' => 'bg-info text-white',
                                            ];
                                            $statusLabels = [
                                                'pending' => __('Pending'),
                                                'completed' => __('Completed'),
                                                'failed' => __('Failed'),
                                                'refunded' => __('Refunded'),
                                            ];
                                        @endphp
                                        <select class="form-control status-dropdown" data-id="{{ $transaction->id }}" data-status="{{ $transaction->status }}">
                                            @foreach ($statusLabels as $value => $label)
                                                <option value="{{ $value }}" {{ $transaction->status === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.credit.transactions.show', $transaction->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($transaction->status === 'completed' && $transaction->transaction_type === 'purchase')
                                                <button class="btn btn-warning btn-sm refund-btn" data-id="{{ $transaction->id }}">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            @endif
                                            @if(in_array($transaction->status, ['pending', 'failed']))
                                                <button class="btn btn-danger btn-sm delete-btn" data-id="{{ $transaction->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card-footer">
                {{ $transactions->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Refund Transaction') }}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="refundForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Refund Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('Refund') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Status update
    $('.status-dropdown').on('change', function () {
        let $this = $(this);
        let id = $this.data('id');
        let status = $this.val();
        let token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ route("admin.credit.transactions.updateStatus", ":id") }}'.replace(':id', id),
            type: 'PATCH',
            data: {
                _token: token,
                status: status
            },
            success: function (response) {
                // Update the data-status attribute for future reference
                $this.attr('data-status', status);
                alert(response.message);
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
                location.reload();
            }
        });
    });

    // Refund button
    $('.refund-btn').on('click', function() {
        let id = $(this).data('id');
        $('#refundForm').data('id', id);
        $('#refundModal').modal('show');
    });

    // Refund form
    $('#refundForm').on('submit', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let reason = $(this).find('textarea[name="reason"]').val();
        let token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ route("admin.credit.transactions.refund", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: token,
                reason: reason
            },
            success: function (response) {
                alert(response.message);
                $('#refundModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
            }
        });
    });

    // Delete button
    $('.delete-btn').on('click', function() {
        if (confirm('{{ __("Are you sure you want to delete this transaction?") }}')) {
            let id = $(this).data('id');
            let token = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: '{{ route("admin.credit.transactions.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: token
                },
                success: function (response) {
                    alert('{{ __("Transaction deleted successfully") }}');
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
                }
            });
        }
    });
});
</script>

@endsection
