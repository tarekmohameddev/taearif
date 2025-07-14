@extends('admin.layout')

@section('title', __('Affiliate Payments').' – '.$affiliate->fullname)

@section('content')
{{-- ─────────────── Header ─────────────── --}}
<div class="page-header">
    <h4 class="page-title">
        {{ $affiliate->fullname }} – {{ __('Affiliate Payments') }}
    </h4>
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-secondary btn-sm">
        {{ __('Back') }}
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
@if($pending_amount > 0)
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">{{ __('Quick Actions') }}</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-success"
            data-toggle="modal"
        data-target="#approvePendingModal"
        data-transaction-id="{{ $pendingTx->id ?? '' }}">
                <i class="fas fa-check"></i> {{ __('Approve Pending Amount') }}
            </button>
        </div>
    </div>
@endif

{{-- ─────────────── Transaction history ─────────────── --}}
<div class="card mt-4">
    <div class="card-header"><h5>{{ __('Transaction History') }}</h5></div>
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

                                    @case('pending')  <span class="badge badge-info">{{ __('pending') }}</span> @break
                                    @default         <span class="badge badge-success">{{ __('Collected') }}</span>

                                @endswitch
                            </td>
                            <td>{{ number_format($t->amount, 2) }} {{ __('SAR') }}</td>
                            <td>{{ $t->note }}</td>
                            <td>
                                @if($t->image)
                                    <a href="{{ asset($t->image) }}" target="_blank">
                                        <img src="{{ asset($t->image) }}"
                                             class="img-thumbnail"
                                             style="width:50px;height:50px;object-fit:cover" alt="">
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
<div class="modal fade" id="approvePendingModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.affiliates.approvePending', ['affiliate'=>$affiliate->id]) }}"
              method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <input type="hidden"
       id="transaction_id"
       name="transaction_id"
       value="{{ $pendingTx->id ?? '' }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Approve Pending Amount') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Amount to collect') }}</label>
                        <input type="number" name="amount" class="form-control"
                               value="{{ $pending_amount }}" max="{{ $pending_amount }}"
                               step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Note (optional)') }}</label>
                        <input type="text" name="note" class="form-control" maxlength="255" placeholder="{{ __('Enter a note (optional)') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('Receipt (optional)') }}</label>
                        <input type="file" name="image"
                               class="form-control-file" accept="image/*">
                    </div>

                    {{-- preview --}}
                    <div id="imagePreview" class="mt-2" style="display:none;">
                        <img id="previewImg" class="img-thumbnail" style="max-width:200px;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-success" type="submit">{{ __('Approve') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
/* for live image preview */
document.addEventListener('change', e => {
    if (e.target.matches('input[name="image"]')) {
        const file   = e.target.files[0];
        const imgBox = document.getElementById('imagePreview');
        const imgTag = document.getElementById('previewImg');

        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                imgTag.src = ev.target.result;
                imgBox.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imgBox.style.display = 'none';
        }
    }
});

$('#approvePendingModal').on('show.bs.modal', function(e) {
  const id = $(e.relatedTarget).data('transaction-id');
  $(this).find('input#transaction_id').val(id);
});
</script>
@endsection
