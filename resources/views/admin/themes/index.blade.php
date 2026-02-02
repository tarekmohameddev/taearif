@extends('admin.layout')

@section('styles')
<style>
    .theme-filter-select {
        min-height: 34px !important;
        height: 34px !important;
        line-height: 1.4 !important;
        padding: 0.35rem 0.75rem !important;
        display: inline-flex !important;
        align-items: center !important;
    }
</style>
@endsection

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Themes Management') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Themes Management') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-lg-3">
                            <h5 class="card-title mb-0">{{ __('Themes') }}</h5>
                        </div>
                        <div class="col-lg-6">
                            <form action="{{ route('admin.themes.index') }}" method="GET" class="form-inline flex-wrap align-items-center">
                                <label class="mb-0 mr-1">{{ __('Category') }}:</label>
                                <select name="category" class="form-control form-control-sm mb-1 mb-md-0 theme-filter-select" style="min-width: 140px; min-height: 34px;">
                                    <option value="" {{ !request()->filled('category') ? 'selected' : '' }}>{{ __('All Categories') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" {{ request()->input('category') == $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                                <label class="mb-0 mr-1 ml-2">{{ __('Status') }}:</label>
                                <select name="is_enabled" class="form-control form-control-sm mb-1 mb-md-0 theme-filter-select" style="min-width: 120px; min-height: 34px;">
                                    <option value="" {{ !request()->filled('is_enabled') ? 'selected' : '' }}>{{ __('All') }}</option>
                                    <option value="1" {{ request()->input('is_enabled') === '1' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                                    <option value="0" {{ request()->input('is_enabled') === '0' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                                </select>
                                <label class="mb-0 mr-1 ml-2">{{ __('Free') }}:</label>
                                <select name="is_free" class="form-control form-control-sm mb-1 mb-md-0 theme-filter-select" style="min-width: 100px; min-height: 34px;">
                                    <option value="" {{ !request()->filled('is_free') ? 'selected' : '' }}>{{ __('All') }}</option>
                                    <option value="1" {{ request()->input('is_free') === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                    <option value="0" {{ request()->input('is_free') === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm ml-2 mb-1 mb-md-0">
                                    <i class="fas fa-filter"></i> {{ __('Filter') }}
                                </button>
                                @if(request()->hasAny(['category', 'is_enabled', 'is_free']))
                                    <a href="{{ route('admin.themes.index') }}" class="btn btn-secondary btn-sm ml-1 mb-1 mb-md-0">
                                        {{ __('Clear') }}
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-lg-3 text-left text-lg-right mt-2 mt-lg-0">
                            <a href="{{ route('admin.themes.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> {{ __('Add new theme') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-striped mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">{{ __('Theme ID') }}</th>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th scope="col">{{ __('Category') }}</th>
                                            <th scope="col">{{ __('Price') }}</th>
                                            <th scope="col">{{ __('Free') }}</th>
                                            <th scope="col">{{ __('Enabled') }}</th>
                                            <th scope="col">{{ __('Popular') }}</th>
                                            <th scope="col">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($themes as $index => $theme)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $theme->theme_id }}</td>
                                                <td>{{ $theme->name }}</td>
                                                <td>{{ $theme->category ?? __('Not set') }}</td>
                                                <td>
                                                    @if($theme->is_free)
                                                        <span class="badge badge-success">{{ __('Free') }}</span>
                                                    @else
                                                        {{ $theme->price ?? '0' }} {{ $theme->currency ?? 'SAR' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->is_free)
                                                        <span class="badge badge-success">{{ __('Yes') }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ __('No') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->is_enabled)
                                                        <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($theme->popular)
                                                        <span class="badge badge-info">{{ __('Popular') }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ __('Normal') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a class="btn btn-sm btn-info" href="{{ route('admin.themes.edit', $theme->theme_id) }}" title="{{ __('Edit') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form class="d-inline-block" action="{{ route('admin.themes.toggle-enabled') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="theme_id" value="{{ $theme->theme_id }}">
                                                        <button type="submit" class="btn btn-sm {{ $theme->is_enabled ? 'btn-warning' : 'btn-success' }}"
                                                            title="{{ $theme->is_enabled ? __('Disable') : __('Enable') }}">
                                                            <i class="fas {{ $theme->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form class="d-inline-block" action="{{ route('admin.themes.delete') }}" method="POST"
                                                        onsubmit="return confirm('{{ __('Are you sure you want to delete this theme?') }}');">
                                                        @csrf
                                                        <input type="hidden" name="theme_id" value="{{ $theme->theme_id }}">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">{{ __('No themes found') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
