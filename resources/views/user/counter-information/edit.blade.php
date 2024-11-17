@extends('user.layout')

@if (!empty($counterInformation->language) && $counterInformation->language->rtl == 1)
    @section('styles')
        <style>
            form input,
            form textarea,
            form select {
                direction: rtl;
            }

            form .note-editor.note-frame .note-editing-area .note-editable {
                direction: rtl;
                text-align: right;
            }
        </style>
    @endsection
@endif

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Edit Counter Information') }}</h4>
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
                <a href="#">{{ __('Counter Information Page') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Edit Counter Information') }} </a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">
                        {{ __('Edit Counter Information') }}</div>
                    <a class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('user.counter-information.index') . '?language=' . $counterInformation->language->code }}">
                        <span class="btn-label">
                            <i class="fas fa-backward"></i>
                        </span>
                        {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="ajaxForm" class="" action="{{ route('user.counter-information.update') }}"
                                method="post" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="counter_information_id" value="{{ $counterInformation->id }}">

                                <div class="form-group">
                                    <label for="">{{ __('Title') }} **</label>
                                    <input type="text" class="form-control" name="title"
                                        value="{{ $counterInformation->title }}" placeholder="{{ __('Enter title') }}">
                                    <p id="errtitle" class="mb-0 text-danger em"></p>
                                </div>
                                @if (
                                    $userBs->theme != 'home_four' &&
                                        $userBs->theme != 'home_five' &&
                                        $userBs->theme != 'home_ten' &&
                                        $userBs->theme != 'home_twelve')
                                    <div class="form-group">
                                        <label for="">{{ __('Icon') . '*' }}</label>
                                        <div class="btn-group d-block">
                                            <button type="button" class="btn btn-primary iconpicker-component"><i
                                                    class="{{ $counterInformation->icon ?? 'fa fa-fw fa-heart' }}"></i></button>
                                            <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"
                                                data-selected="fa-car" data-toggle="dropdown"></button>
                                            <div class="dropdown-menu"></div>
                                        </div>
                                        <input type="hidden" id="inputIcon" name="icon">
                                        <p id="editErr_icon" class="mt-1 mb-0 text-danger em"></p>
                                        <div class="text-warning mt-2">
                                            <small>{{ __('Click on the dropdown icon to select a icon.') }}</small>
                                        </div>
                                    </div>
                                @else
                                @endif
                                <div class="form-group">
                                    <label for="count">{{ __('Count') }}**</label>
                                    <input id="count" type="number" class="form-control ltr" name="count"
                                        value="{{ $counterInformation->count }}"
                                        placeholder="{{ __('Enter achievement count') }}">
                                    <p id="errcount" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ __('Serial Number') . '*' }}
                                        **</label>
                                    <input type="number" class="form-control ltr" name="serial_number"
                                        value="{{ $counterInformation->serial_number }}"
                                        placeholder="{{ __('Enter Serial Number') }}">
                                    <p id="errserial_number" class="mb-0 text-danger em"></p>
                                    <p class="text-warning">
                                        <small>{{ __('The higher the serial number is, the later the Achievement will be shown.') }}</small>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="form">
                        <div class="form-group from-show-notify row">
                            <div class="col-12 text-center">
                                <button type="submit" id="submitBtn" class="btn btn-success">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
