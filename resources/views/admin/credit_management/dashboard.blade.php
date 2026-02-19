@extends('admin.layout')

@section('title', __('Credit Management Dashboard'))

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 border">
                <div>
                    <h1 class="h3 mb-1 text-dark fw-bold">
                        <i class="fas fa-coins text-warning me-2"></i>
                        {{ __('Credit Management Dashboard') }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ __('manage credit packages and channel pricing') }}
                    </p>
                </div>
                <div>
                    <!-- Auto-sync enabled - manual sync not needed -->
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 h-100 stat-card" style="background: #3498db !important;">
                <div class="card-body p-4" style="color: #ffffff !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-2 text-uppercase small" style="opacity: 0.9; letter-spacing: 1px; color: #ffffff !important;">{{ __('Total Packages') }}</p>
                            <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #ffffff !important;">{{ $totalPackages }}</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-box fa-3x" style="opacity: 0.3; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 h-100 stat-card" style="background: #2ecc71 !important;">
                <div class="card-body p-4" style="color: #ffffff !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-2 text-uppercase small" style="opacity: 0.9; letter-spacing: 1px; color: #ffffff !important;">{{ __('Active Packages') }}</p>
                            <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #ffffff !important;">{{ $activePackagesCount }}</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle fa-3x" style="opacity: 0.3; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 h-100 stat-card" style="background: #f39c12 !important;">
                <div class="card-body p-4" style="color: #ffffff !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-2 text-uppercase small" style="opacity: 0.9; letter-spacing: 1px; color: #ffffff !important;">{{ __('Channel Types') }}</p>
                            <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #ffffff !important;">{{ $totalChannels }}</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-broadcast-tower fa-3x" style="opacity: 0.3; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 h-100 stat-card" style="background: #e74c3c !important;">
                <div class="card-body p-4" style="color: #ffffff !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-2 text-uppercase small" style="opacity: 0.9; letter-spacing: 1px; color: #ffffff !important;">{{ __('Active Channels') }}</p>
                            <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #ffffff !important;">{{ $activeChannelsCount }}</h2>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-signal fa-3x" style="opacity: 0.3; color: #ffffff !important;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- Left Panel - Credit Packages -->
        <div class="col-lg-6">
            <div class="card border h-100">
                <div class="card-header text-white border-0" style="background: #3498db;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-box me-2"></i> {{ __('Credit Packages') }}
                        </h5>
                        <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#createPackageModal">
                            <i class="fas fa-plus me-1"></i> {{ __('Add Package') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Package Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm border-0 bg-light" id="packageStatusFilter">
                                <option value="" {{ request('package_status') == '' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                                <option value="active" {{ request('package_status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('package_status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm border-0 bg-light" id="marketingSupportFilter">
                                <option value="" {{ request('marketing_support') == '' ? 'selected' : '' }}>{{ __('All Packages') }}</option>
                                <option value="yes" {{ request('marketing_support') == 'yes' ? 'selected' : '' }}>{{ __('Marketing Support') }}</option>
                                <option value="no" {{ request('marketing_support') == 'no' ? 'selected' : '' }}>{{ __('No Marketing') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm border-0 bg-light" id="packageSearch" placeholder="🔍 {{ __('Search packages...') }}" value="{{ request('package_search') }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary btn-sm w-100" id="resetPackageFilters" title="{{ __('Reset all filters') }}">
                                <i class="fas fa-redo me-1"></i> {{ __('Reset') }}
                            </button>
                        </div>
                    </div>

                    <!-- Packages List -->
                    <div id="packagesList">
                        @forelse($packages as $package)
                        <div class="package-item bg-white border p-4 mb-3" data-package-id="{{ $package->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                @php
                                    // Color code packages by price tier
                                    $packageColor = '#95a5a6'; // Default gray
                                    if ($package->supports_marketing_channels) {
                                        $packageColor = '#f39c12'; // Orange for marketing
                                    } elseif ($package->price >= 100) {
                                        $packageColor = '#9b59b6'; // Purple for premium
                                    } elseif ($package->price >= 50) {
                                        $packageColor = '#3498db'; // Blue for standard
                                    } else {
                                        $packageColor = '#2ecc71'; // Green for starter
                                    }
                                @endphp
                                <div class="d-flex align-items-center">
                                    <div class="text-white p-3 me-3" style="background: {{ $packageColor }}; min-width: 50px; text-align: center;">
                                        <i class="fas fa-box fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 me-3 fw-bold text-dark px-2">{{ $package->getLocalizedName('ar') }}</h6>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-sm btn-edit-package" data-package-id="{{ $package->id }}" title="{{ __('Edit Package') }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-{{ $package->is_active ? 'warning' : 'success' }} btn-sm btn-toggle-package"
                                            data-package-id="{{ $package->id }}" title="{{ $package->is_active ? __('Deactivate') : __('Activate') }}">
                                        <i class="fas fa-{{ $package->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm btn-delete-package" data-package-id="{{ $package->id }}" title="{{ __('Delete Package') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                @if($package->is_active)
                                    <span class="badge bg-success px-3 py-2">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2">{{ __('Inactive') }}</span>
                                @endif
                                @if($package->supports_marketing_channels)
                                    <span class="badge bg-info ms-2 px-3 py-2">{{ __('Marketing') }}</span>
                                @endif
                            </div>
                            <div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="bg-light p-2 border">
                                                <small class="text-muted d-block">{{ __('Credits') }}</small>
                                                <strong class="text-primary">{{ number_format($package->credits) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-light p-2 border">
                                                <small class="text-muted d-block">{{ __('Price') }}</small>
                                                <strong class="text-success">{{ $package->currency }} {{ number_format($package->price, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="bg-light p-2 border">
                                            <small class="text-muted d-block">{{ __('Price per credit') }}</small>
                                            <strong class="text-warning">{{ $package->currency }} {{ number_format($package->price_per_credit, 4) }}</strong>
                                        </div>
                                    </div>
                            </div>

                            <!-- Package Estimates -->
                            @if($package->supports_marketing_channels && isset($packageEstimates[$package->id]) && count($packageEstimates[$package->id]) > 0)
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-chart-bar me-2"></i>{{ __('Estimated Messages per Channel') }}
                                </h6>
                                <div class="row g-2">
                                    @foreach($packageEstimates[$package->id] as $channelType => $estimate)
                                    <div class="col-6 col-md-4">
                                        <div class="bg-light p-2 text-center border">
                                            <div class="fw-bold text-primary">{{ $estimate['estimated_messages'] ?? 0 }}</div>
                                            <small class="text-muted">{{ $estimate['channel_name'] ?? ucfirst($channelType) }}</small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @empty
                        <!-- Empty State -->
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-box fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                            <h5 class="text-muted">{{ __('No packages found') }}</h5>
                            @if(request()->hasAny(['package_status', 'marketing_support', 'package_search']))
                                <p class="text-muted mb-3">{{ __('Try adjusting your filters or search terms') }}</p>
                                <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('resetPackageFilters').click()">
                                    <i class="fas fa-redo me-1"></i> {{ __('Clear Filters') }}
                                </button>
                            @else
                                <p class="text-muted mb-3">{{ __('Create your first credit package to get started') }}</p>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#createPackageModal">
                                    <i class="fas fa-plus me-1"></i> {{ __('Create Package') }}
                                </button>
                            @endif
                        </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        <div class="pagination-wrapper">
                            {{ $packages->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Channel Pricing -->
        <div class="col-lg-6">
            <div class="card border h-100">
                <div class="card-header text-white border-0" style="background: #f39c12;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-broadcast-tower me-2"></i> {{ __('Channel Pricing') }}
                        </h5>
                        <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#createPricingModal">
                            <i class="fas fa-plus me-1"></i> {{ __('Add Channel') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Channel Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm border-0 bg-light" id="channelStatusFilter">
                                <option value="" {{ request('channel_status') == '' ? 'selected' : '' }}>{{ __('All Status') }}</option>
                                <option value="active" {{ request('channel_status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('channel_status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control form-control-sm border-0 bg-light" id="channelSearch" placeholder="🔍 {{ __('Search channels...') }}" value="{{ request('channel_search') }}">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary btn-sm w-100" id="resetChannelFilters" title="{{ __('Reset all filters') }}">
                                <i class="fas fa-redo me-1"></i> {{ __('Reset') }}
                            </button>
                        </div>
                    </div>

                    <!-- Channel Pricing List -->
                    <div id="pricingList">
                        @forelse($channelPricing as $pricing)
                        <div class="pricing-item bg-white border p-4 mb-3" data-pricing-id="{{ $pricing->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                @php
                                    $channelConfig = [
                                        'whatsapp' => ['icon' => 'fab fa-whatsapp', 'color' => '#25D366'],
                                        'facebook' => ['icon' => 'fab fa-facebook', 'color' => '#1877F2'],
                                        'telegram' => ['icon' => 'fab fa-telegram', 'color' => '#0088cc'],
                                        'instagram' => ['icon' => 'fab fa-instagram', 'color' => '#E4405F'],
                                        'sms' => ['icon' => 'fas fa-sms', 'color' => '#FF6B6B'],
                                        'email' => ['icon' => 'fas fa-envelope', 'color' => '#EA4335'],
                                    ];
                                    $channelKey = strtolower($pricing->channel_type);
                                    $config = $channelConfig[$channelKey] ?? ['icon' => 'fas fa-comment', 'color' => '#95a5a6'];
                                @endphp
                                <div class="d-flex align-items-center">
                                    <div class="text-white p-3 me-3" style="background: {{ $config['color'] }}; min-width: 50px; text-align: center;">
                                        <i class="{{ $config['icon'] }} fa-lg"></i>
                                    </div>
                                    <h6 class="mb-0 me-3 fw-bold text-dark px-2">{{ ucfirst($pricing->channel_type) }}</h6>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-sm btn-edit-pricing" data-pricing-id="{{ $pricing->id }}" title="{{ __('Edit Pricing') }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-{{ $pricing->is_active ? 'warning' : 'success' }} btn-sm btn-toggle-pricing"
                                            data-pricing-id="{{ $pricing->id }}" title="{{ $pricing->is_active ? __('Deactivate') : __('Activate') }}">
                                        <i class="fas fa-{{ $pricing->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm btn-delete-pricing" data-pricing-id="{{ $pricing->id }}" title="{{ __('Delete Pricing') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
@if($pricing->is_active)
                                <span class="badge bg-success px-3 py-2">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2">{{ __('Inactive') }}</span>
                                @endif
                            </div>
                            <div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="bg-light p-2 border">
                                                <small class="text-muted d-block">{{ __('Credits per message') }}</small>
                                                <strong class="text-primary">{{ $pricing->credits_per_message }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-light p-2 border">
                                                <small class="text-muted d-block">{{ __('Price per credit') }}</small>
                                                <strong class="text-warning">{{ $pricing->currency }} {{ number_format($pricing->price_per_credit, 4) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-white p-2" style="background: #1abc9c;">
                                            <small class="d-block">{{ __('Effective price per message') }}</small>
                                            <strong class="text-white">{{ $pricing->currency }} {{ number_format($pricing->effective_price_per_message, 4) }}</strong>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        @empty
                        <!-- Empty State -->
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-broadcast-tower fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                            <h5 class="text-muted">{{ __('No channel pricing found') }}</h5>
                            @if(request()->hasAny(['channel_status', 'channel_search']))
                                <p class="text-muted mb-3">{{ __('Try adjusting your filters or search terms') }}</p>
                                <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('resetChannelFilters').click()">
                                    <i class="fas fa-redo me-1"></i> {{ __('Clear Filters') }}
                                </button>
                            @else
                                <p class="text-muted mb-3">{{ __('Create your first channel pricing to get started') }}</p>
                                <button class="btn btn-warning" data-toggle="modal" data-target="#createPricingModal">
                                    <i class="fas fa-plus me-1"></i> {{ __('Add Channel') }}
                                </button>
                            @endif
                        </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        <div class="pagination-wrapper">
                            {{ $channelPricing->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Package Modal -->
<div class="modal fade" id="createPackageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create Credit Package') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createPackageForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Package Name') }}</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Package Name (Arabic)') }}</label>
                        <input type="text" class="form-control" name="name_ar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description (Arabic)') }}</label>
                        <textarea class="form-control" name="description_ar" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Credits') }}</label>
                                <input type="number" class="form-control" name="credits" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Price') }}</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Currency') }}</label>
                        <select class="form-control" name="currency" required>
                            <option value="SAR" selected>SAR</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="supports_marketing_channels" value="1">
                        <label class="form-check-label">{{ __('Support Marketing Channels') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Package') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Pricing Modal -->
<div class="modal fade" id="createPricingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create Channel Pricing') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createPricingForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Channel Type') }}</label>
                        <input type="text" class="form-control" name="channel_type" id="channel_type_input" list="channelTypes" required placeholder="{{ __('Enter or select channel type (e.g., whatsapp, twitter, email)') }}">
                        <datalist id="channelTypes">
                            @foreach($channelTypes as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </datalist>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> {{ __('You can select from existing types or enter a new custom channel type.') }}
                        </small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Credits per Message') }}</label>
                                <input type="number" class="form-control" name="credits_per_message" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Price per Credit') }}</label>
                                <input type="number" class="form-control" name="price_per_credit" step="0.0001" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Currency') }}</label>
                        <select class="form-control" name="currency" required>
                            <option value="SAR" selected>SAR</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description (Arabic)') }}</label>
                        <textarea class="form-control" name="description_ar" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Create Pricing') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style id="credit-management-styles">
    /* Flat Design - No Shadows, No Rounded Corners */
    .badge,
    span.badge {
        font-size: 11px !important;
        font-weight: 600 !important;
        padding: 6px 12px !important;
        border-radius: 0 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-block !important;
        box-shadow: none !important;
        transition: none !important;
        border: 1px solid !important;
    }

    /* Flat Success Badge */
    .badge.bg-success,
    span.badge.bg-success {
        background: #2ecc71 !important;
        color: white !important;
        border-color: #27ae60 !important;
        box-shadow: none !important;
    }

    .badge.bg-success:hover,
    span.badge.bg-success:hover {
        background: #27ae60 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Flat Secondary Badge */
    .badge.bg-secondary,
    span.badge.bg-secondary {
        background: #9ca3af !important;
        color: white !important;
        border-color: #6b7280 !important;
        box-shadow: none !important;
    }

    .badge.bg-secondary:hover,
    span.badge.bg-secondary:hover {
        background: #6b7280 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Flat Info Badge */
    .badge.bg-info,
    span.badge.bg-info {
        background: #3498db !important;
        color: white !important;
        border-color: #2980b9 !important;
        box-shadow: none !important;
    }

    .badge.bg-info:hover,
    span.badge.bg-info:hover {
        background: #2980b9 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Flat Color Palette */
    .bg-gradient-primary {
        background: #3498db !important;
    }

    .bg-gradient-success {
        background: #2ecc71 !important;
    }

    .bg-gradient-light {
        background: #ecf0f1 !important;
    }

    .bg-gradient-orange {
        background: #f39c12 !important;
    }

    .bg-gradient-red {
        background: #e74c3c !important;
    }

    .bg-gradient-turquoise {
        background: #1abc9c !important;
    }

    /* Flat items with subtle hover effects */
    .package-item, .pricing-item {
        transition: all 0.2s ease !important;
        border-left: 3px solid transparent !important;
        box-shadow: none !important;
        cursor: pointer;
    }

    .package-item:hover, .pricing-item:hover {
        transform: none !important;
        box-shadow: none !important;
        border-left-color: #3498db !important;
        background-color: #f8f9fa !important;
    }

    /* Statistics cards hover */
    .stat-card {
        transition: all 0.3s ease !important;
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-5px) !important;
        opacity: 0.9;
    }

    .stat-icon {
        position: relative;
    }

    /* Remove all rounded corners */
    .rounded-lg {
        border-radius: 0 !important;
    }

    .rounded-3 {
        border-radius: 0 !important;
    }

    .rounded {
        border-radius: 0 !important;
    }

    .rounded-circle {
        border-radius: 0 !important;
    }

    /* Remove all shadows */
    .shadow,
    .shadow-sm,
    .shadow-lg {
        box-shadow: none !important;
    }

    /* Flat cards */
    .card {
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    /* Flat buttons */
    .btn {
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .btn:hover {
        box-shadow: none !important;
    }

    .btn-rounded-pill {
        border-radius: 50px !important;
    }

    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-1px);
    }

    .badge {
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .form-control:focus, .form-select:focus {
        outline: 2px solid #007bff;
        box-shadow: none !important;
        border-color: #007bff;
    }

    .text-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
    }

    .pagination-wrapper .pagination {
        margin: 0;
    }

    .pagination-wrapper .page-link {
        border-radius: 50%;
        margin: 0 2px;
        border: none;
        color: #667eea;
        font-weight: 500;
    }

    .pagination-wrapper .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
    }

    .pagination-wrapper .page-link:hover {
        background-color: #f8f9fa;
        color: #667eea;
    }

    @media (max-width: 768px) {
        .package-item, .pricing-item {
            margin-bottom: 1rem;
        }

        .btn-group-vertical {
            margin-top: 1rem;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
    }
</style>
@endsection

@section('scripts')
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard JavaScript loaded');

    // Check if CSRF token exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found in page!');
    } else {
        console.log('CSRF token found:', csrfToken.getAttribute('content').substring(0, 10) + '...');
    }

    // Event delegation for package buttons
    document.body.addEventListener('click', function(e) {
        // Edit package
        if (e.target.closest('.btn-edit-package')) {
            const btn = e.target.closest('.btn-edit-package');
            const packageId = btn.getAttribute('data-package-id');
            editPackage(packageId);
        }

        // Toggle package status
        if (e.target.closest('.btn-toggle-package')) {
            const btn = e.target.closest('.btn-toggle-package');
            const packageId = btn.getAttribute('data-package-id');
            togglePackageStatus(packageId);
        }

        // Delete package
        if (e.target.closest('.btn-delete-package')) {
            const btn = e.target.closest('.btn-delete-package');
            const packageId = btn.getAttribute('data-package-id');
            deletePackage(packageId);
        }

        // Edit pricing
        if (e.target.closest('.btn-edit-pricing')) {
            const btn = e.target.closest('.btn-edit-pricing');
            const pricingId = btn.getAttribute('data-pricing-id');
            editPricing(pricingId);
        }

        // Toggle pricing status
        if (e.target.closest('.btn-toggle-pricing')) {
            const btn = e.target.closest('.btn-toggle-pricing');
            const pricingId = btn.getAttribute('data-pricing-id');
            togglePricingStatus(pricingId);
        }

        // Delete pricing
        if (e.target.closest('.btn-delete-pricing')) {
            const btn = e.target.closest('.btn-delete-pricing');
            const pricingId = btn.getAttribute('data-pricing-id');
            deletePricing(pricingId);
        }
    });

    console.log('Event delegation set up for all buttons');
});

// AJAX functions for quick actions
function togglePackageStatus(packageId) {
    console.log('Toggle package status:', packageId);

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        return;
    }

    fetch('{{ url("admin/credit-management/packages") }}/' + packageId + '/toggle-status', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            alert('Package status updated!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating package status: ' + error.message);
    });
}

function togglePricingStatus(pricingId) {
    console.log('Toggle pricing status:', pricingId);

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('Security token not found. Please refresh the page.');
        return;
    }

    fetch('{{ url("admin/credit-management/pricing") }}/' + pricingId + '/toggle-status', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            alert('Channel pricing status updated!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating pricing status: ' + error.message);
    });
}

function deletePackage(packageId) {
    if (confirm('Are you sure you want to delete this package?')) {
        console.log('Delete package:', packageId);

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            alert('Security token not found. Please refresh the page.');
            return;
        }

        fetch('{{ url("admin/credit-management/packages") }}/' + packageId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.status === 'success') {
                alert('Package deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting package: ' + error.message);
        });
    }
}

function deletePricing(pricingId) {
    if (confirm('Are you sure you want to delete this channel pricing?')) {
        console.log('Delete pricing:', pricingId);

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            alert('Security token not found. Please refresh the page.');
            return;
        }

        fetch('{{ url("admin/credit-management/pricing") }}/' + pricingId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.status === 'success') {
                alert('Channel pricing deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting pricing: ' + error.message);
        });
    }
}

// Form submissions - wrapped in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const createPackageForm = document.getElementById('createPackageForm');
    if (createPackageForm) {
        createPackageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Create package form submitted');

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('Security token not found. Please refresh the page.');
                return;
            }

            fetch('{{ route("admin.credit-management.packages.quick-create") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    // Close modal using Bootstrap 4 syntax
                    $('#createPackageModal').modal('hide');
                    alert('Package created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating package: ' + error.message);
            });
        });
    }

    const createPricingForm = document.getElementById('createPricingForm');
    if (createPricingForm) {
        createPricingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Create pricing form submitted');

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            // Sanitize channel type: convert to lowercase and replace spaces with underscores
            if (data.channel_type) {
                data.channel_type = data.channel_type.toLowerCase().trim().replace(/\s+/g, '_');
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('Security token not found. Please refresh the page.');
                return;
            }

            fetch('{{ route("admin.credit-management.pricing.quick-create") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    // Close modal using Bootstrap 4 syntax
                    $('#createPricingModal').modal('hide');
                    alert('Channel pricing created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating pricing: ' + error.message);
            });
        });
    }

    // Filter functionality
    const packageStatusFilter = document.getElementById('packageStatusFilter');
    if (packageStatusFilter) {
        packageStatusFilter.addEventListener('change', applyFilters);
    }

    const marketingSupportFilter = document.getElementById('marketingSupportFilter');
    if (marketingSupportFilter) {
        marketingSupportFilter.addEventListener('change', applyFilters);
    }

    const packageSearch = document.getElementById('packageSearch');
    if (packageSearch) {
        packageSearch.addEventListener('input', applyFilters);
    }

    const channelStatusFilter = document.getElementById('channelStatusFilter');
    if (channelStatusFilter) {
        channelStatusFilter.addEventListener('change', applyFilters);
    }

    const channelSearch = document.getElementById('channelSearch');
    if (channelSearch) {
        channelSearch.addEventListener('input', applyFilters);
    }

    // Reset buttons functionality
    const resetPackageFilters = document.getElementById('resetPackageFilters');
    if (resetPackageFilters) {
        resetPackageFilters.addEventListener('click', function() {
            document.getElementById('packageStatusFilter').value = '';
            document.getElementById('marketingSupportFilter').value = '';
            document.getElementById('packageSearch').value = '';
            applyFilters();
        });
    }

    const resetChannelFilters = document.getElementById('resetChannelFilters');
    if (resetChannelFilters) {
        resetChannelFilters.addEventListener('click', function() {
            document.getElementById('channelStatusFilter').value = '';
            document.getElementById('channelSearch').value = '';
            applyFilters();
        });
    }

    console.log('All event listeners attached');
});

function applyFilters() {
    const params = new URLSearchParams();

    const packageStatus = document.getElementById('packageStatusFilter').value;
    const marketingSupport = document.getElementById('marketingSupportFilter').value;
    const packageSearch = document.getElementById('packageSearch').value;
    const channelStatus = document.getElementById('channelStatusFilter').value;
    const channelSearch = document.getElementById('channelSearch').value;

    if (packageStatus) params.append('package_status', packageStatus);
    if (marketingSupport) params.append('marketing_support', marketingSupport);
    if (packageSearch) params.append('package_search', packageSearch);
    if (channelStatus) params.append('channel_status', channelStatus);
    if (channelSearch) params.append('channel_search', channelSearch);

    window.location.href = '{{ route("admin.credit-management.index") }}?' + params.toString();
}

// Edit functions - redirect to edit pages
function editPackage(packageId) {
    console.log('Edit package:', packageId);
    window.location.href = '{{ url("admin/credit-management/packages") }}/' + packageId + '/edit';
}

function editPricing(pricingId) {
    console.log('Edit pricing:', pricingId);
    window.location.href = '{{ url("admin/credit-management/pricing") }}/' + pricingId + '/edit';
}
</script>
@endsection
