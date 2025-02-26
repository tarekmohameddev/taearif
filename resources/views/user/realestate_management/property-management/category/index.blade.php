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
            form:not(.modal-content) input,
            form:not(.modal-content) textarea,
            form:not(.modal-content) select,
            select[name='userLanguage'] {
                direction: rtl;
            }

            form:not(.modal-content) .note-editor.note-frame .note-editing-area .note-editable {
                direction: rtl;
                text-align: right;
            }
        </style>
    @endsection
@endif

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Categories') }}</h4>
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
                <a href="#">{{ __('Categories') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">
                                {{ __('Property Categories') }}</div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('user.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary btn-sm float-lg-right float-left"><i class="fas fa-plus"></i>
                                {{ __('Add') }}</a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('user.property_management.bulk_delete_category') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($categories) == 0)
                                <h3 class="text-center mt-2">
                                    {{ __('NO PROPERTY CATEGORY FOUND') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                <th scope="col">{{ __('image') }}</th>
                                                <th scope="col">{{ __('Type') }}</th>
                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">{{ __('Featured') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Serial Number') }}
                                                </th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($categories as $category)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $category->id }}">
                                                    </td>
                                                    <td>
                                                        <img src="{{ asset('assets/img/property-category/' . $category->image) }}"
                                                            alt="image" class="img-fluid" style="max-width: 80px">
                                                    </td>
                                                    <td>
                                                        {{ ucfirst($category->type) }}
                                                    </td>
                                                    <td>
                                                        {{ strlen($category->name) > 50 ? mb_substr($content->name, 0, 50, 'UTF-8') . '...' : $category->name }}
                                                    </td>
                                                    <td>

                                                        <form id="featureForm{{ $category->id }}" class="d-inline-block"
                                                            action="{{ route('user.property_management.update_category_featured') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="categoryId"
                                                                value="{{ $category->id }}">

                                                            <select
                                                                class="form-control {{ $category->featured == 1 ? 'bg-success' : 'bg-danger' }} form-control-sm"
                                                                name="featured"
                                                                onchange="document.getElementById('featureForm{{ $category->id }}').submit();">
                                                                <option value="1"
                                                                    {{ $category->featured == 1 ? 'selected' : '' }}>
                                                                    {{ __('Yes') }}
                                                                </option>
                                                                <option value="0"
                                                                    {{ $category->featured == 0 ? 'selected' : '' }}>
                                                                    {{ __('No') }}
                                                                </option>
                                                            </select>
                                                        </form>

                                                    </td>
                                                    <td>
                                                        @if ($category->status == 1)
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-success">{{ __('Active') }}</span>
                                                            </h2>
                                                        @else
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-danger">{{ __('Deactive') }}</span>
                                                            </h2>
                                                        @endif
                                                    </td>
                                                    <td>{{ $category->serial_number }}</td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm mr-1  mt-1 editBtn"
                                                            href="#" data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $category->id }}" {{-- data-language="{{ $category->language_id }}" --}}
                                                            data-name="{{ $category->name }}"
                                                            data-id="{{ $category->id }}"
                                                            data-status="{{ $category->status }}" {{-- data-type="{{ $category->type }}" --}}
                                                            data-image="{{ asset('assets/img/property-category/' . $category->image) }}"
                                                            data-serial_number="{{ $category->serial_number }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('user.property_management.delete_category') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="id"
                                                                value="{{ $category->id }}">

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

                <div class="card-footer"></div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    @include('user.realestate_management.property-management.category.create')

    {{-- edit modal --}}
    @include('user.realestate_management.property-management.category.edit')
@endsection
