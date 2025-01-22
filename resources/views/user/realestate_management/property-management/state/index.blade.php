@extends('user.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@php
    $selLang = \App\Models\User\Language::where([
        ['code', \Illuminate\Support\Facades\Session::get('currentLangCode')],
        ['user_id', \Illuminate\Support\Facades\Auth::id()],
    ])->first();
    $userDefaultLang = \App\Models\User\Language::where([
        ['user_id', \Illuminate\Support\Facades\Auth::id()],
        ['is_default', 1],
    ])->first();
    $userLanguages = \App\Models\User\Language::where('user_id', \Illuminate\Support\Facades\Auth::id())->get();
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
        <h4 class="page-title">{{ __('States') }}</h4>
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
                <a href="#">{{ __('Real Estate Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Manage Property') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('States') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('States') }}</div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('user.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary btn-sm float-lg-right float-left"><i class="fas fa-plus"></i>
                                {{ __('Add') }}</a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('user.property_management.bulk_delete_state') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($states) == 0)
                                <h3 class="text-center mt-2">{{ __('NO STATES FOUND') }}
                                </h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                @if ($userBs->property_country_status == 1)
                                                    <th scope="col">{{ __('Country Name') }}
                                                    </th>
                                                @endif
                                                <th scope="col">{{ __('State Name') }}</th>
                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($states as $state)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $state->id }}">
                                                    </td>
                                                    @if ($userBs->property_country_status == 1)
                                                        <td>
                                                            {{ strlen($state->country->name) > 50 ? mb_substr($state->country->name, 0, 50, 'UTF-8') . '...' : $state->country->name }}
                                                        </td>
                                                    @endif
                                                    <td>
                                                        {{ strlen($state->name) > 50 ? mb_substr($state->name, 0, 50, 'UTF-8') . '...' : $state->name }}
                                                    </td>
                                                    <td>
                                                        {{ $state->serial_number }}
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm mr-1  mt-1 editBtn"
                                                            href="#" data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $state->id }}"
                                                            data-serial_number="{{ $state->serial_number }}"
                                                            data-name="{{ $state->name }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('user.property_management.delete_state') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="id"
                                                                value="{{ $state->id }}">

                                                            <button type="submit"
                                                                class="btn btn-danger  mt-1 btn-sm deleteBtn">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>
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

    {{-- create modal --}}
    @include('user.realestate_management.property-management.state.create')

    {{-- edit modal --}}
    @include('user.realestate_management.property-management.state.edit')

@endsection

@section('scripts')
    <script>
        "use strict";
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $(".countryLang").on('change', function() {
                let langid = $(this).val();
                let countryUrl = "{{ route('user.property_management.get_countries', ':langid') }}"
                    .replace(':langid', langid);

                $(".request-loader").addClass("show");
                $("#country").removeAttr('disabled');
                $.get(countryUrl, function(data) {
                    let options = `<option value="" disabled selected>Select a country</option>`;
                    for (let i = 0; i < data.length; i++) {
                        options += `<option value="${data[i].id}">${data[i].name}</option>`;
                    }
                    $("#country").html(options);
                    $(".request-loader").removeClass("show");

                });

                if ($(this).parents('form').hasClass('create')) {
                    $.get(mainurl + "/user/rtlcheck/" + $(this).val(), function(data) {
                        $(".request-loader").removeClass("show");
                        if (data == 1) {
                            $("form.create input").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.create select").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.create textarea").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.create .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .addClass('rtl text-right');
                            });

                        } else {
                            $("form.create input, form.create select, form.create textarea")
                                .removeClass('rtl');

                            $("form.create .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .removeClass('rtl text-right');
                            });
                        }
                    });
                } else if ($(this).parents('form').hasClass('modal-form')) {
                    $.get(mainurl + "/user/rtlcheck/" + $(this).val(), function(data) {
                        $(".request-loader").removeClass("show");
                        if (data == 1) {
                            $("form.modal-form input").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.modal-form select").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.modal-form textarea").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form.modal-form .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .addClass('rtl text-right');
                            });

                        } else {
                            $("form.modal-form input, form.modal-form select, form.modal-form textarea")
                                .removeClass('rtl');

                            $("form.modal-form .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .removeClass('rtl text-right');
                            });
                        }
                    });
                } else {
                    // make input fields RTL
                    $.get(mainurl + "/user/rtlcheck/" + $(this).val(), function(data) {
                        $(".request-loader").removeClass("show");
                        if (data == 1) {
                            $("form input").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form select").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form textarea").each(function() {
                                if (!$(this).hasClass('ltr')) {
                                    $(this).addClass('rtl');
                                }
                            });
                            $("form .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .addClass('rtl text-right');
                            });

                        } else {
                            $("form input, form select, form textarea").removeClass('rtl');
                            $("form .summernote").each(function() {
                                $(this).siblings('.note-editor').find('.note-editable')
                                    .removeClass('rtl text-right');
                            });
                        }
                    });
                }

            })
        });
    </script>
@endsection
