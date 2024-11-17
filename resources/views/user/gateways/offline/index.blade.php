@extends('user.layout')
@php
$selLang = \App\Models\User\Language::where([['code', request()->input('language')], ['user_id', \Illuminate\Support\Facades\Auth::guard('web')->user()->id]])->first();
$userDefaultLang = \App\Models\User\Language::where([['user_id', \Illuminate\Support\Facades\Auth::guard('web')->user()->id], ['is_default', 1]])->first();

$userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::guard('web')->user()->id)->get();
@endphp
@if (!empty($selLang) && $selLang->rtl == 1)
    @section('styles')
        <style>
            form:not(.modal-form) input,
            form:not(.modal-form) textarea,
            form:not(.modal-form) select,
            select[name='userLanguage'] {
                direction: rtl;
            }

            form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
                direction: rtl;
                text-align: right;
            }

        </style>
    @endsection
@endif
@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Offline Gateways') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('user-dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Payment Gateways') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Offline Gateways') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card-title d-inline-block">{{ __('Offline Gateways') }}</div>
                        </div>
                        <div class="col-lg-6 mt-2 mt-lg-0">
                            <a href="#" class="btn btn-primary float-lg-right float-left btn-sm" data-toggle="modal"
                                data-target="#createModal"><i class="fas fa-plus"></i> {{ __('Add Gateway') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($ogateways) == 0)
                                <h3 class="text-center">{{ __('NO OFFLINE PAYMENT GATEWAY FOUND') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">{{ __('Name') }}</th>
                                                @if ($shopsettings->catalog_mode == 0)
                                                    <th scope="col">Status</th>
                                                @endif
                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($ogateways as $key => $ogateway)
                                                <tr>
                                                    <td>
                                                        {{ $ogateway->name }}
                                                    </td>
                                                    @if ($shopsettings->is_shop == 1 && $shopsettings->catalog_mode == 0)
                                                        <td>
                                                            <form id="ItemForm{{ $ogateway->id }}" class="d-inline-block"
                                                                action="{{ route('user.offline.status') }}"
                                                                method="post">
                                                                @csrf
                                                                <input type="hidden" name="ogateway_id"
                                                                    value="{{ $ogateway->id }}">
                                                                <input type="hidden" name="type" value="item">
                                                                <select
                                                                    class="form-control form-control-sm {{ $ogateway->item_checkout_status == 1 ? 'bg-success' : 'bg-danger' }}"
                                                                    name="item_checkout_status"
                                                                    onchange="document.getElementById('ItemForm{{ $ogateway->id }}').submit();">
                                                                    <option value="1"
                                                                        {{ $ogateway->item_checkout_status == 1 ? 'selected' : '' }}>
                                                                        Active</option>
                                                                    <option value="0"
                                                                        {{ $ogateway->item_checkout_status == 0 ? 'selected' : '' }}>
                                                                        Deactive</option>
                                                                </select>
                                                            </form>
                                                        </td>
                                                    @endif
                                                    <td>{{ $ogateway->serial_number }}</td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm editbtn" href="#editModal"
                                                            data-toggle="modal" data-ogateway_id="{{ $ogateway->id }}"
                                                            data-name="{{ $ogateway->name }}"
                                                            data-short_description="{{ $ogateway->short_description }}"
                                                            data-instructions="{{ replaceBaseUrl($ogateway->instructions) }}"
                                                            data-is_receipt="{{ $ogateway->is_receipt }}"
                                                            data-serial_number="{{ $ogateway->serial_number }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                            {{ __('Edit') }}
                                                        </a>
                                                        <form class="deleteform d-inline-block"
                                                            action="{{ route('user.offline.gateway.delete') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="ogateway_id"
                                                                value="{{ $ogateway->id }}">
                                                            <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Create Offline Gateway Modal -->
    @includeIf('user.gateways.offline.create')



    <!-- Edit Package Modal -->
    @includeIf('user.gateways.offline.edit')


@endsection
