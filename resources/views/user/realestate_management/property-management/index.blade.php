@extends('user.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('user.partials.rtl-style')

@section('content')

    <div class="page-header">
        <h4 class="page-title">{{ __('Properties') }}</h4>
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
                <a href="#">{{ __('Properties') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="card-title d-inline-block">{{ __('Properties') }}</div>
                        </div>

                        <div class="col-lg-3">
                            <form action="{{ route('user.property_management.properties') }}" method="get"
                                id="carSearchForm">
                                <div class="row">

                                    {{-- <div class="col-lg-12"> --}}
                                    <input type="text" name="title" value="{{ request()->input('title') }}"
                                        class="form-control" placeholder="{{ __('Enter title') }}">
                                    {{-- </div> --}}
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-3">
                            @includeIf('user.partials.languages')
                        </div>

                        <div class="col-lg-3 mt-2 mt-lg-0">
                            <a href="{{ route('user.property_management.type') }}"
                                class="btn btn-primary btn-sm float-right"><i class="fas fa-plus"></i>
                                {{ __('Add Property') }} </a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('user.property_management.bulk_delete_property') }}"><i
                                    class="flaticon-interface-5"></i>
                                {{ __('Delete') }} </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($properties) == 0)
                                <h3 class="text-center">
                                    {{ __('NO PROPERTIES ARE FOUND!') }} </h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                <th scope="col">{{ __('Title') }}</th>
                                                <th scope="col">{{ __('Type') }}</th>
                                                <th scope="col">{{ __('City') }}</th>
                                                <th scope="col">{{ __('Featured') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($properties as $property)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $property->id }}">
                                                    </td>
                                                    <td>
                                                        {{ strlen($property->title) > 50 ? mb_substr($property->title, 0, 50, 'utf-8') . '...' : $property->title }}

                                                    </td>

                                                    <td>
                                                        {{ $property->type }}
                                                    </td>
                                                    <td>

                                                        {{ $property->cityName }}
                                                    </td>
                                                    <td>
                                                        <form id="featureForm{{ $property->id }}" class="d-inline-block"
                                                            action="{{ route('user.property_management.update_featured') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="requestId"
                                                                value="{{ $property->id }}">

                                                            <select
                                                                class="form-control {{ $property->featured == 1 ? 'bg-success' : 'bg-danger' }} form-control-sm"
                                                                name="featured"
                                                                onchange="document.getElementById('featureForm{{ $property->id }}').submit();">
                                                                <option value="1"
                                                                    {{ $property->featured == 1 ? 'selected' : '' }}>
                                                                    {{ __('Yes') }}
                                                                </option>
                                                                <option value="0"
                                                                    {{ $property->featured == 0 ? 'selected' : '' }}>
                                                                    {{ __('No') }}
                                                                </option>
                                                            </select>
                                                        </form>



                                                    </td>

                                                    <td>
                                                        <form id="statusForm{{ $property->id }}" class="d-inline-block"
                                                            action="{{ route('user.property_management.update_status') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="propertyId"
                                                                value="{{ $property->id }}">

                                                            <select
                                                                class="form-control {{ $property->status == 1 ? 'bg-success' : 'bg-danger' }} form-control-sm"
                                                                name="status"
                                                                onchange="document.getElementById('statusForm{{ $property->id }}').submit();">
                                                                <option value="1"
                                                                    {{ $property->status == 1 ? 'selected' : '' }}>
                                                                    {{ __('Active') }}
                                                                </option>
                                                                <option value="0"
                                                                    {{ $property->status == 0 ? 'selected' : '' }}>
                                                                    {{ __('Deactive') }}
                                                                </option>
                                                            </select>
                                                        </form>
                                                    </td>

                                                    <td>
                                                        <a class="btn btn-secondary  mt-1 btn-sm mr-1"
                                                            href="{{ route('user.property_management.edit', $property->id) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('user.property_management.delete_property') }}"
                                                            method="post">
                                                            @csrf
                                                            <input type="hidden" name="property_id"
                                                                value="{{ $property->id }}">

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

                <div class="card-footer">
                    {{ $properties->appends([
                            'vendor_id' => request()->input('vendor_id'),
                            'title' => request()->input('title'),
                        ])->links() }}
                </div>

            </div>
        </div>
    </div>



@endsection

@section('script')
    <script src="{{ asset('assets/js/feature-payment.js') }}"></script>
@endsection
