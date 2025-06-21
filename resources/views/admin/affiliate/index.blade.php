@extends('admin.layout')

@section('title', 'Affiliate Management')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Affiliate Management') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Affiliate Management') }}</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">

            <div class="card-header">
                <form action="{{ route('admin.affiliate.index') }}" class="form-inline float-right">
                    <input name="search" class="form-control mr-2" type="text"
                        placeholder="{{__('Search by Full Name, Bank Name, Account, IBAN, Status')}}"
                        value="{{ request()->input('search') }}"
                        style="width: 235px; font-size: 0.675rem;">
                    <button type="submit" class="btn btn-primary btn-sm">{{__('Search')}}</button>
                </form>
                <div class="card-title">{{ __('All Affiliate Requests') }}</div>
            </div>

            <div class="card-body">
                @if ($affiliates->isEmpty())
                    <h4 class="text-center">{{ __('NO AFFILIATE FOUND') }}</h4>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Full Name') }}</th>
                                    <th>{{ __('Bank Name') }}</th>
                                    <th>{{ __('Account Number') }}</th>
                                    <th>{{ __('IBAN') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($affiliates as $affiliate)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $affiliate->fullname }}</td>
                                        <td>{{ $affiliate->bank_name }}</td>
                                        <td>{{ $affiliate->bank_account_number }}</td>
                                        <td>{{ $affiliate->iban }}</td>
                                        <td>
                                            @php
                                                $status = strtolower($affiliate->request_status);
                                                $statusLabels = [
                                                    'pending' => 'Pending',
                                                    'approved' => 'Approved',
                                                    'rejected' => 'Rejected'
                                                ];
                                            @endphp

                                            <span class="badge badge-{{ $status == 'approved' ? 'success' : ($status == 'rejected' ? 'danger' : 'warning') }}">
                                                {{ $statusLabels[$status] ?? ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.affiliates.updateStatus', $affiliate->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('POST')
                                                <select name="request_status" class="form-select form-select-sm" onchange="this.className = this.options[this.selectedIndex].className; this.form.submit()">
                                                    <option class="badge bg-warning text-dark" value="pending" {{ $affiliate->request_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option class="badge bg-success" value="approved" {{ $affiliate->request_status === 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option class="badge bg-danger" value="rejected" {{ $affiliate->request_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card-footer">
                {{ $affiliates->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[name="request_status"]').forEach(function (select) {
            select.className = select.options[select.selectedIndex].className;
        });
    });
</script>
