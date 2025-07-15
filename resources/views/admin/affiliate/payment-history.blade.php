@extends('admin.layout')

@section('title', __('Affiliate Payments').' – '.$affiliate->fullname)

@section('content')
{{-- ─────────────── Header ─────────────── --}}

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h4 class="page-title mb-0">
        {{ $affiliate->fullname }} – {{ __('Affiliate Payments') }}
    </h4>
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-info btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> {{ __('Back') }}
    </a>
</div>

{{-- flash messages --}}
@foreach (['success','error'] as $flash)
@if(session($flash))
<div class="alert alert-{{ $flash === 'success' ? 'success' : 'danger' }}">
    {{ session($flash) }}
</div>
@endif
@endforeach

{{-- ─────────────── Metric card ─────────────── --}}
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-warning">
                    {{ number_format($pending_amount, 2) }} {{ __('SAR') }}
                </h5>
                <small><i class="fas fa-clock"></i> {{ __('Pending Amount') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────── Quick actions ─────────────── --}}
{{-- Quick-actions card --}}
@if($pending_amount > 0)
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">{{ __('Quick Actions') }}</h5>
    </div>
    <div class="card-body">
        {{-- bulk button opens its own modal --}}
        <button class="btn btn-success ml-2" data-toggle="modal" data-target="#approveAllModal">
            <i class="fas fa-check-double "></i>
            {{ __('Approve All Pending') }}
        </button>
    </div>
</div>
@endif

{{-- ─────────────── Transaction history ─────────────── --}}
<div class="card mt-4">
    <div class="card-header">
        <h5>{{ __('Transaction History') }}</h5>
    </div>
    <div class="card-body">
        @if ($transactions->isEmpty())
        <p class="text-center mb-0">{{ __('No transaction history available') }}</p>
        @else
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Note') }}</th>
                    <th>{{ __('Image') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $t)
                <tr>
                    <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        @switch($t->type)

                        @case('pending') <span class="badge badge-info">{{ __('pending') }}</span> @break
                        @default <span class="badge badge-success">{{ __('Collected') }}</span>

                        @endswitch
                    </td>
                    <td>{{ number_format($t->amount, 2) }} {{ __('SAR') }}</td>
                    <td>{{ $t->note }}</td>
                    <td>
                        @if($t->image)
                        <a href="{{ asset($t->image) }}" target="_blank">
                            <img src="{{ asset($t->image) }}" class="img-thumbnail" style="width:50px;height:50px;object-fit:cover" alt="">
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $transactions->links('pagination::bootstrap-4', ['pageName' => 'transactions_page']) }}
        @endif
    </div>
</div>

{{-- ─────────────── Modal · Approve pending ─────────────── --}}
{{-- Modal · Approve all pending --}}
<div class="modal fade" id="approveAllModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.affiliates.approveAll', ['affiliate'=>$affiliate->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Approve All Pending') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Note (optional)') }}</label>
                        <input type="text" name="note" class="form-control" placeholder="{{ __('Enter a note (optional)') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('Receipt (optional)') }}</label>
                        <input type="file" name="image" class="form-control-file" accept="image/*">
                    </div>

                    <div id="bulkImagePreview" class="mt-2" style="display:none;">
                        <img id="bulkPreviewImg" class="img-thumbnail" style="max-width:200px;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-danger" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-success" type="submit">{{ __('Approve All') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Show modal for approving pending transactions
    $('#approvePendingModal').on('show.bs.modal', function(e) {
        const txId = $(e.relatedTarget).data('transaction-id');
        $(this).find('input#transaction_id').val(txId);
    });

    // Live preview for bulk approval modal
    document.querySelector('#approveAllModal input[name="image"]').addEventListener('change', e => {
        const file = e.target.files[0];
        const box = document.getElementById('bulkImagePreview');
        const img = document.getElementById('bulkPreviewImg');

        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                img.src = ev.target.result;
                box.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            box.style.display = 'none';
        }
    });
</script>

@endsection
