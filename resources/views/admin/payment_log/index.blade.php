@extends('admin.layout')
@php
$selLang = \App\Models\Language::where('code', request()->input('language'))->first();
@endphp
@section('styles')
<style>
    @if(!empty($selLang) && $selLang->rtl == 1)
    form:not(.modal-form) input,
    form:not(.modal-form) textarea,
    form:not(.modal-form) select,
    select[name='language'] {
        direction: rtl;
    }
    form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
        direction: rtl;
        text-align: right;
    }
    @endif
    /* Owner Details modal: wide + fit viewport height */
    .modal.owner-details-modal .modal-dialog {
        max-width: 960px;
        width: 92%;
        margin: 1.5rem auto;
        max-height: calc(100vh - 3rem);
        display: flex;
        flex-direction: column;
    }
    .modal.owner-details-modal .modal-content {
        max-height: calc(100vh - 3rem);
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }
    .modal.owner-details-modal .modal-header {
        flex-shrink: 0;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }
    .modal.owner-details-modal .modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        min-height: 0;
    }
    .modal.owner-details-modal .modal-footer {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(0,0,0,0.08);
    }
    .owner-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.5rem;
    }
    .owner-details-section {
        background: #f8fafc;
        border-radius: 10px;
        padding: 1.25rem;
        border: 1px solid rgba(0,0,0,0.06);
    }
    [dir="rtl"] .owner-details-section { text-align: right; }
    .owner-details-section h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #ea580c;
        margin: 0 0 1rem 0;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid rgba(234, 88, 12, 0.25);
    }
    .owner-details-row {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.5rem 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .owner-details-row:last-child { border-bottom: none; }
    [dir="rtl"] .owner-details-row { flex-direction: row-reverse; }
    .owner-details-row .label {
        font-size: 0.8125rem;
        color: #64748b;
        min-width: 100px;
    }
    .owner-details-row .value {
        font-size: 0.9375rem;
        font-weight: 500;
        color: #1e293b;
    }
</style>
@endsection
@section('content')
<div class="page-header">
    <h4 class="page-title">{{__('Payment Logs')}}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{route('admin.dashboard')}}">
            <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{__('Payment')}}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{__('Payment Log Page')}}</a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">{{__('Payment Log')}}</div>
                    </div>
                    <div class="col-lg-3">
                    </div>
                    <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                        <form action="{{url()->current()}}" class="d-inline-block float-right">
                            <input class="form-control" type="text" name="search"
                                placeholder="{{__('Search by Transaction ID')}}"
                                value="{{request()->input('search') ? request()->input('search') : '' }}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (count($memberships) == 0)
                        <h3 class="text-center">{{__('NO MEMBERSHIP FOUND')}}</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col">{{__('Transaction Id')}}</th>
                                        <th scope="col">{{__('Amount')}}</th>
                                        <th scope="col">{{__('Payment Status')}}</th>
                                        <th scope="col">{{__('Payment Method')}}</th>
                                        <th scope="col">{{__('Receipt')}}</th>
                                        <th scope="col">{{__('Actions')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($memberships as $key => $membership)
                                    <tr>
                                        <td>{{strlen($membership->transaction_id) > 30 ? mb_substr($membership->transaction_id, 0, 30, 'UTF-8') . '...' : $membership->transaction_id}}</td>
                                        @php
                                        $bex = json_decode($membership->settings);
                                        @endphp
                                        <td>
                                            @if($membership->price == 0)
                                            {{__('Free')}}
                                            @else
                                            {{format_price($membership->price)}}
                                            @endif
                                        </td>
                                        <td>
                                            @if(json_decode($membership->transaction_details) !== "offline")
                                                @if ($membership->status == 1)
                                                <h3 class="d-inline-block badge badge-success">{{__('Success')}}</h3>
                                                @elseif ($membership->status == 0)
                                                <h3 class="d-inline-block badge badge-warning">{{__('Pending')}}</h3>
                                                @elseif ($membership->status == 2)
                                                <h3 class="d-inline-block badge badge-danger">{{__('Rejected')}}</h3>
                                                @endif
                                            @else
                                            <form id="statusForm{{$membership->id}}" class="d-inline-block"
                                                action="{{route('admin.payment-log.update')}}"
                                                method="post">
                                                @csrf
                                                <input type="hidden" name="id" value="{{$membership->id}}">
                                                <select class="form-control form-control-sm
                                                    @if ($membership->status == 1)
                                                    bg-success
                                                    @elseif ($membership->status == 0)
                                                    bg-warning
                                                    @elseif ($membership->status == 2)
                                                    bg-danger
                                                    @endif
                                                    " name="status"
                                                    onchange="document.getElementById('statusForm{{$membership->id}}').submit();">
                                                    <option value=0 {{$membership->status == 0 ? 'selected' : ''}}>{{__('Pending')}}</option>
                                                    <option value=1 {{$membership->status == 1 ? 'selected' : ''}}>{{__('Success')}}</option>
                                                    <option value=2 {{$membership->status == 2 ? 'selected' : ''}}>{{__('Rejected')}}</option>
                                                </select>
                                            </form>
                                            @endif
                                        </td>
                                        <td>{{$membership->payment_method}}</td>
                                        <td>
                                            @if ($membership->status == 1)
                                            <a class="btn btn-sm btn-info" href="{{route('admin.payment-log.download-invoice', $membership->id)}}" target="_blank">
                                                <i class="fas fa-download"></i> {{__('Download Invoice')}}
                                            </a>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($membership->name !== "anonymous"))
                                            <a class="btn btn-sm btn-info" href="#" data-toggle="modal"
                                                data-target="#detailsModal{{$membership->id}}">{{__('Detail')}}</a>
                                            @else
                                            -
                                            @endif
                                        </td>
                                    </tr>
                                    <div class="modal fade owner-details-modal" id="detailsModal{{$membership->id}}" tabindex="-1"
                                        role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">{{__('Owner Details')}}
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="owner-details-grid">
                                                        <div class="owner-details-section">
                                                            <h3>{{__('User details')}}</h3>
                                                            <div class="owner-details-row"><span class="label">{{__('Name')}}</span><span class="value">{{ !empty($membership->user) ? $membership->user->first_name.' '.$membership->user->last_name : '-' }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Username')}}</span><span class="value">{{ !empty($membership->user) ? $membership->user->username : '-' }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Company')}}</span><span class="value">{{ !empty($membership->user) ? $membership->user->company_name : 'N/A' }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Email')}}</span><span class="value">{{ !empty($membership->user) ? $membership->user->email : '-' }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Phone')}}</span><span class="value">{{ !empty($membership->user) ? $membership->user->phone_number : '-' }}</span></div>
                                                        </div>
                                                        <div class="owner-details-section">
                                                            <h3>{{__('Payment details')}}</h3>
                                                            @if ($membership->discount > 0)
                                                            <div class="owner-details-row"><span class="label">{{__('Package Price')}}</span><span class="value">{{ $membership->package_price == 0 ? __('Free') : $membership->package_price }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Discount')}}</span><span class="value">{{ $membership->discount }}</span></div>
                                                            @endif
                                                            <div class="owner-details-row"><span class="label">{{__('Total')}}</span><span class="value">{{ $membership->price == 0 ? __('Free') : $membership->price }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Currency')}}</span><span class="value">{{ $membership->currency }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Method')}}</span><span class="value">{{ $membership->payment_method }}</span></div>
                                                        </div>
                                                        <div class="owner-details-section">
                                                            <h3>{{__('Package Details')}}</h3>
                                                            <div class="owner-details-row"><span class="label">{{__('Title')}}</span><span class="value">{{ !empty($membership->package) ? $membership->package->title : '-' }}</span></div>
                                                            <div class="owner-details-row"><span class="label">{{__('Term')}}</span><span class="value">{{ !empty($membership->package) ? $membership->package->term : '-' }}</span></div>
                                                            <div class="owner-details-row">
                                                                <span class="label">{{__('Start Date')}}</span>
                                                                <span class="value">
                                                                    @if (\Illuminate\Support\Carbon::parse($membership->start_date)->format('Y') == '9999')
                                                                        <span class="badge badge-danger">Never Activated</span>
                                                                    @else
                                                                        {{ \Illuminate\Support\Carbon::parse($membership->start_date)->format('M-d-Y') }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                            <div class="owner-details-row">
                                                                <span class="label">{{__('Expire Date')}}</span>
                                                                <span class="value">
                                                                    @if (\Illuminate\Support\Carbon::parse($membership->start_date)->format('Y') == '9999')
                                                                        -
                                                                    @else
                                                                        @if ($membership->modified == 1)
                                                                            {{ \Illuminate\Support\Carbon::parse($membership->expire_date)->addDay()->format('M-d-Y') }}
                                                                            <span class="badge badge-primary btn-xs">modified by Admin</span>
                                                                        @else
                                                                            {{ !empty($membership->package) && $membership->package->term == 'lifetime' ? __('Lifetime') : \Illuminate\Support\Carbon::parse($membership->expire_date)->format('M-d-Y') }}
                                                                        @endif
                                                                    @endif
                                                                </span>
                                                            </div>
                                                            <div class="owner-details-row">
                                                                <span class="label">{{__('Purchase Type')}}</span>
                                                                <span class="value">
                                                                    @if($membership->is_trial == 1)
                                                                        {{__('Trial')}}
                                                                    @else
                                                                        {{ $membership->price == 0 ? __('Free') : __('Regular') }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    {{__('Close')}}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                        {{$memberships->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
