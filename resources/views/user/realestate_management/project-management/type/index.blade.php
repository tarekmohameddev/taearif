@extends('user.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('user.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Project Types') }}</h4>
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
                <a href="#">{{ __('Manage Project') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Project Types') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('Project Types') }}
                            </div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('user.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary btn-sm float-lg-right float-left"><i class="fas fa-plus"></i>
                                {{ __('Add Project Type') }}</a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('user.project_management.bulk_delete_type') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($types) == 0)
                                <h3 class="text-center mt-2">
                                    {{ __('NO PROPERTY TYPES FOUND') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>

                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">
                                                    {{ __('Minimum Price') . ' (' . $userBs->base_currency_text . ')' }}
                                                </th>
                                                <th scope="col">
                                                    {{ __('Minimum Area (sqft)') }}
                                                </th>
                                                <th scope="col">{{ __('Total Unit') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($types as $type)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $type->id }}">
                                                    </td>

                                                    <td>
                                                        {{ strlen($type->title) > 50 ? mb_substr($type->title, 0, 50, 'UTF-8') . '...' : $type->title }}
                                                    </td>
                                                    <td>
                                                        {{ $type->min_price }}
                                                    </td>
                                                    <td>
                                                        {{ $type->min_area }}
                                                    </td>
                                                    <td>
                                                        {{ $type->unit }}
                                                    </td>

                                                    <td>
                                                        <a class="btn btn-secondary btn-sm mr-1  mt-1 editBtn"
                                                            href="#" data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $type->id }}"
                                                            data-project_id="{{ $type->project_id }}"
                                                            @foreach ($langs as $lang) 
                                                             
                                                            data-{{ $lang->code }}_name="{{ $type->title }}"
                                                            data-{{ $lang->code }}_min_area="{{ $type->min_area }}"
                                                            data-{{ $lang->code }}_max_area="{{ $type->max_area }}"
                                                            data-{{ $lang->code }}_min_price="{{ $type->min_price }}"
                                                            data-{{ $lang->code }}_max_price="{{ $type->max_price }}"
                                                            data-{{ $lang->code }}_unit="{{ $type->unit }}" @endforeach>
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('user.project_management.delete_type') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="id"
                                                                value="{{ $type->id }}">

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
    @include('user.realestate_management.project-management.type.create')

    {{-- edit modal --}}
    @include('user.realestate_management.project-management.type.edit')
@endsection
@section('scripts')
    <script>
        var myDropzone = null;
    </script>
    <script type="text/javascript" src="{{ asset('assets/tenant/js/admin-partial.js') }}"></script>
@endsection
