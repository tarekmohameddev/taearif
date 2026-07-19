@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Pipedrive CRM Settings') }}</h4>
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
                <a href="#">{{ __('Integrations') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Pipedrive CRM') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">

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

            <div class="card">
                <form id="pipedriveForm" action="{{ route('admin.pipedrive.update') }}" method="POST">
                    @csrf

                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div class="card-title">{{ __('Pipedrive CRM Integration') }}</div>
                                <p class="text-muted mb-0" style="font-size:13px;">
                                    {{ __('Automatically sync new tenant registrations to Pipedrive as Persons and Deals.') }}
                                </p>
                            </div>
                            <div class="col-lg-4 text-right">
                                <a href="https://pipedrive.com" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-external-link-alt mr-1"></i> Pipedrive.com
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-5 pb-5">
                        <div class="row">
                            <div class="col-lg-6 offset-lg-3">

                                {{-- Enable / Disable --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('Auto-Sync Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="enabled" value="1"
                                                class="selectgroup-input"
                                                {{ !empty($data['enabled']) ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Enabled') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="enabled" value="0"
                                                class="selectgroup-input"
                                                {{ empty($data['enabled']) ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Disabled') }}</span>
                                        </label>
                                    </div>
                                    <small class="text-muted">{{ __('When enabled, every new tenant registration is queued for automatic sync.') }}</small>
                                </div>

                                <hr>

                                {{-- API Token --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('API Token') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_token" class="form-control @error('api_token') is-invalid @enderror"
                                        value="{{ old('api_token', $data['api_token'] ?? '') }}"
                                        placeholder="{{ __('Leave unchanged to keep existing token') }}">
                                    @error('api_token')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        {{ __('Find your API token in Pipedrive → Settings → Personal Preferences → API.') }}
                                        @if (!empty($data['api_token']))
                                            <br><span class="text-success"><i class="fas fa-check-circle"></i> {{ __('Token is configured.') }}</span>
                                        @endif
                                    </small>
                                </div>

                                {{-- Base URL --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('Pipedrive Base URL') }} <span class="text-danger">*</span></label>
                                    <input type="url" name="base_url" class="form-control @error('base_url') is-invalid @enderror"
                                        value="{{ old('base_url', $data['base_url'] ?? '') }}"
                                        placeholder="https://yourcompany.pipedrive.com">
                                    @error('base_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Your Pipedrive account URL, e.g. https://mycompany.pipedrive.com') }}</small>
                                </div>

                                <hr>

                                {{-- Pipeline ID --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('Pipeline ID') }}</label>
                                    <input type="number" name="pipeline_id" class="form-control @error('pipeline_id') is-invalid @enderror"
                                        value="{{ old('pipeline_id', $data['pipeline_id'] ?? '') }}"
                                        placeholder="e.g. 1" min="1">
                                    @error('pipeline_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('The numeric ID of the Pipedrive pipeline where new deals will be created.') }}</small>
                                </div>

                                {{-- Stage ID --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('Stage ID') }}</label>
                                    <input type="number" name="stage_id" class="form-control @error('stage_id') is-invalid @enderror"
                                        value="{{ old('stage_id', $data['stage_id'] ?? '') }}"
                                        placeholder="e.g. 1" min="1">
                                    @error('stage_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('The numeric ID of the pipeline stage for newly created deals.') }}</small>
                                </div>

                                {{-- Deal Title Prefix --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('Deal Title Prefix') }}</label>
                                    <input type="text" name="deal_title_prefix" class="form-control @error('deal_title_prefix') is-invalid @enderror"
                                        value="{{ old('deal_title_prefix', $data['deal_title_prefix'] ?? 'New Website Lead - ') }}"
                                        placeholder="New Website Lead - " maxlength="100">
                                    @error('deal_title_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">{{ __('Prefix added to deal titles. Final title: "[Prefix][Tenant Name]"') }}</small>
                                </div>

                                {{-- Connection test result --}}
                                @if (isset($connectionStatus))
                                    <div class="alert alert-{{ $connectionStatus ? 'success' : 'danger' }}">
                                        <i class="fas fa-{{ $connectionStatus ? 'check-circle' : 'times-circle' }} mr-1"></i>
                                        {{ $connectionStatus ? __('Connection to Pipedrive is working.') : __('Could not connect to Pipedrive. Please check your API token and base URL.') }}
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success mr-2">
                                    <i class="fas fa-save mr-1"></i> {{ __('Save Settings') }}
                                </button>
                                <button type="submit" name="test_connection" value="1" class="btn btn-outline-info">
                                    <i class="fas fa-plug mr-1"></i> {{ __('Save & Test Connection') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Sync Stats Card --}}
            @if (!empty($syncStats))
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">{{ __('Sync Statistics') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="text-success">{{ $syncStats['success'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">{{ __('Successful Syncs') }}</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-danger">{{ $syncStats['failed'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">{{ __('Failed') }}</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning">{{ $syncStats['skipped'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">{{ __('Skipped') }}</p>
                            </div>
                            <div class="col-md-3">
                                <h3>{{ $syncStats['total'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">{{ __('Total Attempts') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
