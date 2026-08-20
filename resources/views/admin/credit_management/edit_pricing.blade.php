@extends('admin.layout')

@section('title', 'Edit Channel Pricing')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Edit Channel Pricing') }}</h4>
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
            <a href="#">Edit Channel Pricing</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title d-flex justify-content-between align-items-center">
                    <h4>Edit Channel: {{ ucfirst($pricing->channel_type) }}</h4>
                    <a href="{{ route('admin.credit-management.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.credit-management.pricing.update', $pricing->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="channel_type">Channel Type</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $pricing->channel_type_name }}" readonly>
                                <small class="text-muted">Channel type cannot be changed</small>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="message_category">Message Category</label>
                                @php
                                    $cats = $messageCategories ?? \App\Models\Api\marketing\MarketingChannelPricing::getMessageCategories();
                                    $catLabel = $cats[$pricing->message_category]['en'] ?? $pricing->message_category;
                                @endphp
                                <input type="text" class="form-control bg-light"
                                       value="{{ $catLabel }}" readonly>
                                <small class="text-muted">Category cannot be changed (unique per channel)</small>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="label_ar">Arabic Label</label>
                                <input type="text" class="form-control @error('label_ar') is-invalid @enderror"
                                       id="label_ar" name="label_ar"
                                       value="{{ old('label_ar', $pricing->label_ar) }}"
                                       maxlength="100">
                                @error('label_ar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="credits_per_message">Credits per Message <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('credits_per_message') is-invalid @enderror"
                                       id="credits_per_message" name="credits_per_message"
                                       value="{{ old('credits_per_message', $pricing->credits_per_message) }}"
                                       min="0" required>
                                @error('credits_per_message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="price_per_credit">Price per Credit <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('price_per_credit') is-invalid @enderror"
                                       id="price_per_credit" name="price_per_credit"
                                       value="{{ old('price_per_credit', $pricing->price_per_credit) }}"
                                       step="0.0001" min="0" required>
                                @error('price_per_credit')
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
                                <label>Effective Price/Message (Calculated)</label>
                                <input type="text" class="form-control bg-light"
                                       id="effective_price_display"
                                       value="{{ number_format($pricing->effective_price_per_message, 4) }} {{ $pricing->currency }}"
                                       readonly>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <select class="form-control" id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', $pricing->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $pricing->is_active) ? '' : 'selected' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="is_billable">Billable</label>
                                <select class="form-control" id="is_billable" name="is_billable">
                                    <option value="1" {{ old('is_billable', $pricing->is_billable) ? 'selected' : '' }}>Yes — deduct credits</option>
                                    <option value="0" {{ !old('is_billable', $pricing->is_billable) ? 'selected' : '' }}>No — always free</option>
                                </select>
                                <small class="text-muted">Set to "No" for Service (Meta charges $0 for service messages)</small>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label>Created</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $pricing->created_at->format('Y-m-d H:i') }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="description_ar">Description (Arabic)</label>
                                <textarea class="form-control @error('description_ar') is-invalid @enderror"
                                          id="description_ar" name="description_ar" rows="3">{{ old('description_ar', $pricing->description_ar) }}</textarea>
                                @error('description_ar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> How Pricing Works</h6>
                                <ul class="mb-0">
                                    <li><strong>Credits per Message:</strong> How many credits are deducted for each message sent</li>
                                    <li><strong>Price per Credit:</strong> The cost of each credit in the selected currency</li>
                                    <li><strong>Effective Price per Message:</strong> Credits per Message × Price per Credit = {{ number_format($pricing->effective_price_per_message, 4) }} {{ $pricing->currency }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center mt-4">
                        <a href="{{ route('admin.credit-management.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Pricing
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
    // Calculate effective price on input change
    document.getElementById('credits_per_message').addEventListener('input', calculateEffectivePrice);
    document.getElementById('price_per_credit').addEventListener('input', calculateEffectivePrice);
    document.getElementById('currency').addEventListener('change', calculateEffectivePrice);

    function calculateEffectivePrice() {
        const creditsPerMessage = parseFloat(document.getElementById('credits_per_message').value) || 0;
        const pricePerCredit = parseFloat(document.getElementById('price_per_credit').value) || 0;
        const currency = document.getElementById('currency').value;

        const effectivePrice = (creditsPerMessage * pricePerCredit).toFixed(4);
        document.getElementById('effective_price_display').value = effectivePrice + ' ' + currency;
    }
</script>
@endsection
