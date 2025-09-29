@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Transaction Details') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="{{ route('admin.credit.transactions.index') }}">{{ __('Credit Transactions') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Transaction Details') }}</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="card-title">{{ __('Transaction Information') }}</div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>{{ __('Reference Number') }}:</strong></td>
                                <td>{{ $transaction->reference_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('User') }}:</strong></td>
                                <td>{{ optional($transaction->user)->name ?? optional($transaction->user)->username ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Transaction Type') }}:</strong></td>
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
                            </tr>
                            <tr>
                                <td><strong>{{ __('Credits Amount') }}:</strong></td>
                                <td>
                                    @if($transaction->credits_amount > 0)
                                        <span class="text-success">+{{ $transaction->credits_amount }}</span>
                                    @else
                                        <span class="text-danger">{{ $transaction->credits_amount }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Amount Paid') }}:</strong></td>
                                <td>
                                    @if($transaction->amount_paid > 0)
                                        <span class="text-success">{{ $transaction->amount_paid }} {{ $transaction->currency }}</span>
                                    @elseif($transaction->amount_paid < 0)
                                        <span class="text-danger">{{ $transaction->amount_paid }} {{ $transaction->currency }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>{{ __('Payment Method') }}:</strong></td>
                                <td>{{ ucfirst($transaction->payment_method) }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Status') }}:</strong></td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'badge-warning',
                                            'completed' => 'badge-success',
                                            'failed' => 'badge-danger',
                                            'refunded' => 'badge-info',
                                        ];
                                        $statusLabels = [
                                            'pending' => __('Pending'),
                                            'completed' => __('Completed'),
                                            'failed' => __('Failed'),
                                            'refunded' => __('Refunded'),
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$transaction->status] ?? 'badge-secondary' }}">
                                        {{ $statusLabels[$transaction->status] ?? $transaction->status }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Created At') }}:</strong></td>
                                <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Updated At') }}:</strong></td>
                                <td>{{ $transaction->updated_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            @if($transaction->creditPackage)
                            <tr>
                                <td><strong>{{ __('Package') }}:</strong></td>
                                <td>{{ $transaction->creditPackage->name }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                
                @if($transaction->description)
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><strong>{{ __('Description') }}:</strong></h6>
                        <p>{{ $transaction->description }}</p>
                    </div>
                </div>
                @endif

                @if($transaction->metadata)
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><strong>{{ __('Metadata') }}:</strong></h6>
                        <pre class="bg-light p-3">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <div class="card-title">{{ __('Actions') }}</div>
            </div>
            <div class="card-body">
                @if($transaction->status === 'completed' && $transaction->transaction_type === 'purchase')
                    <button class="btn btn-warning btn-block mb-2" onclick="showRefundModal()">
                        <i class="fas fa-undo"></i> {{ __('Refund Transaction') }}
                    </button>
                @endif

                @if(in_array($transaction->status, ['pending', 'failed']))
                    <button class="btn btn-danger btn-block mb-2" onclick="deleteTransaction()">
                        <i class="fas fa-trash"></i> {{ __('Delete Transaction') }}
                    </button>
                @endif

                <a href="{{ route('admin.credit.transactions.index') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                </a>
            </div>
        </div>

        @if($transaction->user)
        <div class="card mt-3">
            <div class="card-header">
                <div class="card-title">{{ __('User Information') }}</div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>{{ __('Name') }}:</strong></td>
                        <td>{{ $transaction->user->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Username') }}:</strong></td>
                        <td>{{ $transaction->user->username ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Email') }}:</strong></td>
                        <td>{{ $transaction->user->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Phone') }}:</strong></td>
                        <td>{{ $transaction->user->phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
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
function showRefundModal() {
    $('#refundModal').modal('show');
}

function deleteTransaction() {
    if (confirm('{{ __("Are you sure you want to delete this transaction?") }}')) {
        let token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ route("admin.credit.transactions.destroy", $transaction->id) }}',
            type: 'DELETE',
            data: {
                _token: token
            },
            success: function (response) {
                alert('{{ __("Transaction deleted successfully") }}');
                window.location.href = '{{ route("admin.credit.transactions.index") }}';
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
            }
        });
    }
}

$('#refundForm').on('submit', function(e) {
    e.preventDefault();
    let reason = $(this).find('textarea[name="reason"]').val();
    let token = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '{{ route("admin.credit.transactions.refund", $transaction->id) }}',
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
</script>

@endsection
