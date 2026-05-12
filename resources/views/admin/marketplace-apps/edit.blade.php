@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Edit Marketplace App') }}</h4>
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
                <a href="{{ route('admin.marketplace-apps.index') }}">{{ __('Marketplace Apps') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Edit') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block">{{ __('Edit Marketplace App') }}</div>
                    <a class="btn btn-info btn-sm float-right d-inline-block" href="{{ route('admin.marketplace-apps.index') }}">
                        <span class="btn-label">
                            <i class="fas fa-backward"></i>
                        </span>
                        {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body pt-5 pb-5">
                    <div class="row">
                        <div class="col-lg-6 offset-lg-3">
                            <form id="ajaxForm" class="" action="{{ route('admin.marketplace-apps.update') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="app_id" value="{{ $app->id }}">

                                <div class="form-group">
                                    <label for="name">{{ __('App Name') }}*</label>
                                    <input id="name" type="text" class="form-control" name="name"
                                        value="{{ $app->name }}" placeholder="{{ __('Enter app name') }}">
                                    <p id="errname" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="description">{{ __('Description') }}</label>
                                    <textarea id="description" class="form-control" name="description" rows="3"
                                        placeholder="{{ __('Enter app description') }}">{{ $app->description }}</textarea>
                                    <p id="errdescription" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="price">{{ __('Price') }}*</label>
                                    <input id="price" type="number" step="0.01" class="form-control" name="price"
                                        value="{{ $app->price }}" placeholder="{{ __('Enter app price') }}">
                                    <p class="text-warning">
                                        <small>{{ __('Enter 0 for free apps') }}</small>
                                    </p>
                                    <p id="errprice" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="type">{{ __('App Type') }}*</label>
                                    <select id="type" name="type" class="form-control" required>
                                        <option value="marketplace" {{ $app->type === 'marketplace' ? 'selected' : '' }}>{{ __('Marketplace') }}</option>
                                        <option value="builtin" {{ $app->type === 'builtin' ? 'selected' : '' }}>{{ __('Built-in') }}</option>
                                    </select>
                                    <p id="errtype" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="rating">{{ __('Rating (0-5)') }}</label>
                                    <input id="rating" type="number" step="0.1" min="0" max="5" class="form-control" name="rating"
                                        value="{{ $app->rating }}" placeholder="{{ __('Enter rating') }}">
                                    <p id="errrating" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="path">{{ __('App page path') }}</label>
                                    <input id="path" type="text" class="form-control" name="path"
                                        placeholder="/dashboard/app-slug" value="{{ $app->path ?? '' }}">
                                    <p class="text-info">
                                        <small>{{ __('App page path (optional). Example: /dashboard/whatsapp-center') }}</small>
                                    </p>
                                    <p id="errpath" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    @if($app->img)
                                        <label>{{ __('Current Image') }}</label>
                                        <div class="mb-2">
                                            <img src="{{ asset($app->img) }}" alt="{{ $app->name }}"
                                                style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                        </div>
                                    @endif
                                    <label for="img">{{ __('Image URL') }}</label>
                                    <input id="img" type="text" class="form-control" name="img"
                                        placeholder="{{ __('Enter image URL') }}" value="{{ $app->img }}">
                                    <p class="text-info">
                                        <small>{{ __('OR upload a new image file below') }}</small>
                                    </p>
                                    <p id="errimg" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="image">{{ __('Upload New Image') }}</label>
                                    <input id="image" type="file" class="form-control" name="image" accept="image/*">
                                    <p class="text-info">
                                        <small>{{ __('Allowed: JPG, JPEG, PNG. Max size: 2MB') }}</small>
                                    </p>
                                    <div id="imagePreview" class="mt-2" style="display:none;">
                                        <img id="previewImg" src="" alt="{{ __('Preview') }}" style="max-width: 200px; max-height: 200px;">
                                    </div>
                                    <p id="errimage" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group">
                                    <label for="billing_type">{{ __('Billing Type') }}*</label>
                                    @php
                                        $billingTypeLabels = [
                                            'free' => __('Free'),
                                            'paid' => __('Paid'),
                                            'paid_trial' => __('Paid with Trial'),
                                        ];
                                    @endphp
                                    <select id="billing_type" name="billing_type" class="form-control" required>
                                        @foreach($billingTypes as $billingType)
                                            <option value="{{ $billingType->value }}"
                                                {{ $app->billing_type->value === $billingType->value ? 'selected' : '' }}>
                                                {{ $billingTypeLabels[$billingType->value] ?? ucfirst(str_replace('_', ' ', $billingType->value)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p id="errbilling_type" class="mb-0 text-danger em"></p>
                                </div>
                                <div class="form-group" id="trial_days_group" style="{{ $app->billing_type->value === 'paid_trial' ? '' : 'display: none;' }}">
                                    <label for="trial_days">{{ __('Trial Days') }}*</label>
                                    <input id="trial_days" type="number" min="1" class="form-control" name="trial_days"
                                        value="{{ $app->trial_days }}" placeholder="{{ __('Enter trial days') }}">
                                    <p id="errtrial_days" class="mb-0 text-danger em"></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="form">
                        <div class="form-group from-show-notify row">
                            <div class="col-12 text-center">
                                <button type="submit" id="submitBtn"
                                    class="btn btn-success">{{ __('Update') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Show/hide trial_days based on billing_type
            $('#billing_type').on('change', function() {
                if ($(this).val() === 'paid_trial') {
                    $('#trial_days_group').show();
                } else {
                    $('#trial_days_group').hide();
                    $('#trial_days').val('');
                }
            });

            // Image preview for file upload
            $('#image').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImg').attr('src', e.target.result);
                        $('#imagePreview').show();
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                }
            });

            // AJAX form submission
            $('#submitBtn').on('click', function() {
                var form = $('#ajaxForm');
                var formData = new FormData(form[0]);
                var url = form.attr('action');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response === 'success') {
                            window.location.href = '{{ route("admin.marketplace-apps.index") }}';
                        } else {
                            window.location.href = '{{ route("admin.marketplace-apps.index") }}';
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $('.em').text('');
                            $.each(errors, function(key, value) {
                                $('#err' + key).text(value[0]);
                            });
                        } else {
                            alert(@json(__('An error occurred. Please try again.')));
                        }
                    }
                });
            });
        });
    </script>
@endsection
