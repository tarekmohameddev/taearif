@extends('user.layout')
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
                <h2 class="fs-4 fw-semibold mb-2">{{ __('Language Management') }}</h2>
                <p class="text-muted mb-0" style="font-size: 15px; line-height: 1.6;">
                    جعل موقعك متعدد اللغات امر مهم, يمكنك اضافات لغات جديدة من هنا
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
                    
                    <a href="#" class="btn btn-primary float-right" data-toggle="modal" data-target="#createModal">
                        <i class="fas fa-plus"></i>
                        {{ __('Add Language') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($languages) == 0)
                                <h3 class="text-center">{{ __('NO LANGUAGE FOUND') }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">{{ __('Code') }}</th>
                                                <th scope="col">{{ __('Appearance in Website') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($languages as $key => $language)
                                                <tr>
                                                    <td>{{ $loop->iteration + 1 }}</td>
                                                    <td>{{ $language->name }}</td>
                                                    <td>{{ $language->code }}</td>
                                                    <td>
                                                        @if ($language->is_default == 1)
                                                            <strong
                                                                class="badge badge-success">{{ __('Default') }}</strong>
                                                        @else
                                                            <form class="d-inline-block"
                                                                action="{{ route('user.language.default', $language->id) }}"
                                                                method="post">
                                                                @csrf
                                                                <button class="btn btn-primary btn-sm" type="submit"
                                                                    name="button">{{ __('Make Default') }}</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm"
                                                            href="{{ route('user.language.editKeyword', $language->id) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                            {{ __('Edit Keyword') }}
                                                        </a>
                                                        <a class="btn btn-secondary btn-sm"
                                                            href="{{ route('user.language.edit', $language->id) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                            {{ __('Edit') }}
                                                        </a>
                                                        <form class="deleteform d-inline-block"
                                                            action="{{ route('user.language.delete', $language->id) }}"
                                                            method="post">
                                                            @csrf
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
    <!-- Create Language Modal -->
    @includeif('user.language.create')
@endsection
