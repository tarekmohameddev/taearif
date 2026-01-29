@extends('admin.layout')

@section('title', 'Edit Credit Package')

@section('content')
<div class="page-header">
    <h4 class="page-title">Edit Credit Package</h4>
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
            <a href="{{ route('admin.credit-management.index') }}">Credit Management</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">Edit Package</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title d-flex justify-content-between align-items-center">
                    <h4>Edit Package: {{ $package->name }}</h4>
                    <a href="{{ route('admin.credit-management.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.credit-management.packages.update', $package->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="name">Package Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $package->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="credits">Credits <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('credits') is-invalid @enderror"
                                       id="credits" name="credits" value="{{ old('credits', $package->credits) }}"
                                       min="1" required>
                                @error('credits')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="price">Price <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('price') is-invalid @enderror"
                                       id="price" name="price" value="{{ old('price', $package->price) }}"
                                       step="0.01" min="0" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="currency">Currency <span class="text-danger">*</span></label>
                                <select class="form-control @error('currency') is-invalid @enderror"
                                        id="currency" name="currency" required>
                                    <option value="SAR" selected>SAR</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Price per Credit (Calculated)</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ number_format($package->price_per_credit, 4) }} {{ $package->currency }}"
                                       readonly>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <select class="form-control" id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', $package->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $package->is_active) ? '' : 'selected' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="sort_order">Sort Order</label>
                                <input type="number" class="form-control"
                                       id="sort_order" name="sort_order"
                                       value="{{ old('sort_order', $package->sort_order) }}"
                                       min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="supports_marketing_channels" name="supports_marketing_channels"
                                           value="1" {{ old('supports_marketing_channels', $package->supports_marketing_channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="supports_marketing_channels">
                                        <strong>Support Marketing Channels</strong>
                                        <br>
                                        <small class="text-muted">Enable this package to be used for marketing channel messages</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($package->supports_marketing_channels)
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Estimated Messages per Channel</h5>
                                <div class="row">
                                    @foreach($package->getEstimatedMessagesPerChannel() as $channelType => $estimate)
                                    <div class="col-md-4">
                                        <p class="mb-1">
                                            <strong>{{ $estimate['channel_name'] }}:</strong>
                                            {{ $estimate['estimated_messages'] }} messages
                                            <small class="text-muted">({{ $estimate['credits_per_message'] }} credits/msg)</small>
                                        </p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="form-group text-center mt-4">
                        <a href="{{ route('admin.credit-management.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Calculate price per credit on input change
    document.getElementById('credits').addEventListener('input', calculatePricePerCredit);
    document.getElementById('price').addEventListener('input', calculatePricePerCredit);

    function calculatePricePerCredit() {
        const credits = parseFloat(document.getElementById('credits').value) || 0;
        const price = parseFloat(document.getElementById('price').value) || 0;
        const currency = document.getElementById('currency').value;

        if (credits > 0) {
            const pricePerCredit = (price / credits).toFixed(4);
            document.querySelector('input[readonly]').value = pricePerCredit + ' ' + currency;
        }
    }
</script>
@endsection
