@extends('user.layout')

@includeIf('user.partials.rtl-style')

@section('content')

<div class="row">
<div class="col-md-12">
<div class="min-vh-100 d-flex align-items-center justify-content-center pb-3">
        <div class="feature-card p-4 d-flex flex-column flex-md-row align-items-start gap-3 mx-auto w-100" style="">
            <div class="icon-container d-flex align-items-center justify-content-center flex-shrink-0 mb-3 mb-md-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-dark">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
            </div>
            <div class="feature-card-text">
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Brand Section') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    أظهر للعملاء من هم شركاءك
                </p>
            </div>
        </div>
    </div>
    </div>
    </div>
    <style>
        .feature-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s;
        }
        .feature-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .icon-container {
            width: 3.5rem;
            height: 3.5rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
        }
        .icon-container svg {
            width: 2rem;
            height: 2rem;
        }
        .feature-card-text {
            white-space: normal !important;
        }
        .feature-card-text h2,
        .feature-card-text p {
            white-space: normal !important;
        }
        @media (min-width: 768px) {
            .feature-card-text {
                max-width: 75%;
            }
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
          <div class="col-12 col-sm-auto ms-sm-auto col-md-auto ms-md-auto">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-4"> 
            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary"><i class="fas fa-plus"></i>
                                @if ($userBs->theme == 'home_eleven')
                                    {{ __('Add Donor') }}
                                @else
                                    {{ __('Add Brand') }}
                                @endif
                            </a>
            </div>
            </div>
          </div>  
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            @if (count($brands) == 0)
                                @if ($userBs->theme == 'home_eleven')
                                    <h3 class="text-center">{{ __('NO DONOR FOUND!') }}</h3>
                                @else
                                    <h3 class="text-center">{{ __('NO BRAND FOUND!') }}</h3>
                                @endif
                            @else
                                <div class="row">
                                    @foreach ($brands as $brand)
                                        <div class="col-md-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <img src="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                                        alt="brand image" class="w-100">
                                                </div>

                                                <div class="card-footer text-center">
                                                    <a class="edit-btn btn btn-secondary btn-sm mr-2" href="#"
                                                        data-toggle="modal" data-target="#editModal"
                                                        data-id="{{ $brand->id }}"
                                                        data-brandimg="{{ asset('assets/front/img/user/brands/' . $brand->brand_img) }}"
                                                        data-brand_url="{{ $brand->brand_url }}"
                                                        data-serial_number="{{ $brand->serial_number }}">
                                                        <span class="btn-label">
                                                            <i class="fas fa-edit"></i>
                                                        </span>
                                                        {{ __('Edit') }}
                                                    </a>

                                                    <form class="deleteform d-inline-block"
                                                        action="{{ route('user.home_page.brand_section.delete_brand') }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="brand_id" value="{{ $brand->id }}">
                                                        <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                                            <span class="btn-label">
                                                                <i class="fas fa-trash"></i>
                                                            </span>
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    @include('user.home.brand_section.create')

    {{-- edit modal --}}
    @include('user.home.brand_section.edit')
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/edit.js') }}"></script>
@endsection
