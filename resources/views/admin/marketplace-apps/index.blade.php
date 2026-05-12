@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Marketplace Apps') }}</h4>
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
                <a href="#">{{ __('Marketplace Apps') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('Marketplace Apps') }}</div>
                        </div>
                        <div class="col-lg-4">
                            <form action="{{ route('admin.marketplace-apps.index') }}" method="GET" class="form-inline">
                                <input name="search" class="form-control form-control-sm" type="text"
                                    placeholder="{{ __('Search by name or description') }}"
                                    value="{{ request()->input('search') }}">
                                <button type="submit" class="btn btn-primary btn-sm ml-2">
                                    <i class="fas fa-search"></i> {{ __('Search') }}
                                </button>
                                @if(request()->input('search'))
                                    <a href="{{ route('admin.marketplace-apps.index') }}" class="btn btn-secondary btn-sm ml-2">
                                        {{ __('Clear') }}
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-lg-4 offset-lg-0 mt-2 mt-lg-0">
                            <a href="#" class="btn btn-primary float-right btn-sm" data-toggle="modal"
                                data-target="#createModal"><i class="fas fa-plus"></i>
                                {{ __('Add Marketplace App') }}</a>
                            <button class="btn btn-danger float-right btn-sm mr-2 d-none bulk-delete"
                                data-href="{{ route('admin.marketplace-apps.bulk-delete') }}"><i class="flaticon-interface-5"></i>
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-12">
                            @if ($apps->count() == 0)
                                <h3 class="text-center">{{ __('No marketplace apps found yet.') }}</h3>
                            @else
                                @php
                                    $typeLabels = [
                                        'marketplace' => __('Marketplace'),
                                        'builtin' => __('Built-in'),
                                    ];
                                    $billingTypeLabels = [
                                        'free' => __('Free'),
                                        'paid' => __('Paid'),
                                        'paid_trial' => __('Paid with Trial'),
                                    ];
                                    $billingTypeColors = [
                                        'free' => 'badge-success',
                                        'paid' => 'badge-primary',
                                        'paid_trial' => 'badge-warning',
                                    ];
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="marketplace-apps-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                <th scope="col">{{ __('Image') }}</th>
                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">{{ __('Type') }}</th>
                                                <th scope="col">{{ __('Billing Type') }}</th>
                                                <th scope="col">{{ __('Price') }}</th>
                                                <th scope="col">{{ __('Rating') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($apps as $app)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $app->id }}">
                                                    </td>
                                                    <td>
                                                        @if($app->img)
                                                            <img src="{{ asset($app->img) }}" alt="{{ $app->name }}"
                                                                style="max-width: 50px; max-height: 50px; object-fit: cover;">
                                                        @else
                                                            <span class="text-muted">{{ __('No Image') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ strlen($app->name) > 30 ? mb_substr($app->name, 0, 30, 'UTF-8') . '...' : $app->name }}
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info text-capitalize">{{ $typeLabels[$app->type] ?? $app->type }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $billingTypeColors[$app->billing_type->value] ?? 'badge-secondary' }} text-capitalize">
                                                            {{ $billingTypeLabels[$app->billing_type->value] ?? ucfirst(str_replace('_', ' ', $app->billing_type->value)) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($app->price == 0)
                                                            {{ __('Free') }}
                                                        @else
                                                            {{ number_format($app->price, 2) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">{{ number_format($app->rating, 1) }}/5.0</span>
                                                    </td>
                                                    <td>
                                                        <button
                                                            class="btn btn-sm toggle-status-btn {{ $app->is_enabled ? 'btn-outline-success' : 'btn-secondary' }}"
                                                            data-app-id="{{ $app->id }}"
                                                            data-enabled="{{ $app->is_enabled ? '1' : '0' }}">
                                                            <i class="fas {{ $app->is_enabled ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                                            {{ $app->is_enabled ? __('Enabled') : __('Disabled') }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <a class="btn btn-secondary btn-sm"
                                                            href="{{ route('admin.marketplace-apps.edit', $app->id) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                            {{ __('Edit') }}
                                                        </a>
                                                        <form class="deleteform d-inline-block"
                                                            action="{{ route('admin.marketplace-apps.delete') }}" method="post">
                                                            @csrf
                                                            <input type="hidden" name="app_id"
                                                                value="{{ $app->id }}">
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
                                <div class="mt-3">
                                    {{ $apps->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    @includeif('admin.marketplace-apps.create')
@endsection

@section('scripts')
    @php
        $mpi18n = [
            'sending' => __('Sending...'),
            'submit' => __('Submit'),
            'errorGeneric' => __('An error occurred. Please try again.'),
            'enabled' => __('Enabled'),
            'disabled' => __('Disabled'),
            'swalEnabledTitle' => __('Enabled successfully'),
            'swalDisabledTitle' => __('Disabled successfully'),
            'ok' => __('OK'),
            'errorTitle' => __('Error'),
        ];
    @endphp
    <script>
        $(document).ready(function() {
            var mpi18n = @json($mpi18n);

            // Bulk delete functionality - toggle button visibility
            $('.bulk-check[data-val="all"]').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.bulk-check[data-val!="all"]').prop('checked', isChecked);
                toggleBulkDeleteButton();
            });

            $('.bulk-check[data-val!="all"]').on('change', function() {
                toggleBulkDeleteButton();
            });

            function toggleBulkDeleteButton() {
                var checkedCount = $('.bulk-check[data-val!="all"]:checked').length;
                if (checkedCount > 0) {
                    $('.bulk-delete').removeClass('d-none');
                } else {
                    $('.bulk-delete').addClass('d-none');
                }
            }

            // Using global bulk delete handler from custom.js (SweetAlert)

            // Function to toggle trial_days_group visibility
            function toggleTrialDaysGroup() {
                var billingType = $('#billing_type').val();
                if (billingType === 'paid_trial') {
                    $('#trial_days_group').show();
                } else {
                    $('#trial_days_group').hide();
                    $('#trial_days').val('');
                }
            }

            // Show/hide trial_days based on billing_type - use event delegation
            $(document).on('change', '#billing_type', function() {
                toggleTrialDaysGroup();
            });

            // Trigger when modal is shown to ensure proper state
            $('#createModal').on('shown.bs.modal', function() {
                toggleTrialDaysGroup();
            });

            // Image preview for file upload
            $(document).on('change', '#image', function(e) {
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

            // Prevent form from submitting normally
            $(document).on('submit', '#ajaxForm', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // AJAX form submission - use namespace to prevent duplicate handlers
            $(document).off('click', '#submitBtn.marketplace-submit').on('click', '#submitBtn.marketplace-submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var form = $('#ajaxForm');

                // Prevent double submission using a global flag
                if (window.marketplaceAppSubmitting) {
                    console.log('Submission already in progress, ignoring click');
                    return false;
                }

                // Set global flag
                window.marketplaceAppSubmitting = true;

                // Disable button and show loading state
                $btn.prop('disabled', true);
                var originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> ' + mpi18n.sending);

                var formData = new FormData(form[0]);
                var url = form.attr('action');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Reset flag immediately on success
                        window.marketplaceAppSubmitting = false;

                        if (response === 'success') {
                            $('#createModal').modal('hide');
                            location.reload();
                        } else {
                            $('#createModal').modal('hide');
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        // Reset flag on error
                        window.marketplaceAppSubmitting = false;

                        // Re-enable button on error
                        $btn.prop('disabled', false);
                        $btn.html(originalText);

                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $('.em').text('');
                            $.each(errors, function(key, value) {
                                $('#err' + key).text(value[0]);
                            });
                        } else {
                            alert(mpi18n.errorGeneric);
                        }
                    },
                    complete: function() {
                        // Safety: Always reset flag when request completes
                        setTimeout(function() {
                            window.marketplaceAppSubmitting = false;
                            $btn.prop('disabled', false);
                            $btn.html(originalText);
                        }, 1500);
                    }
                });

                return false;
            });

            // Reset form when modal is closed
            $('#createModal').on('hidden.bs.modal', function() {
                // Reset global submission flag
                window.marketplaceAppSubmitting = false;

                $('#ajaxForm')[0].reset();
                $('.em').text('');
                $('#imagePreview').hide();
                $('#trial_days_group').hide();

                // Reset submit button state
                var $btn = $('#submitBtn');
                $btn.prop('disabled', false);
                $btn.html(mpi18n.submit);

                // Reset billing_type to first option
                var firstOption = $('#billing_type option:first').val();
                $('#billing_type').val(firstOption);
            });

            // Toggle app status
            $(document).on('click', '.toggle-status-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var appId = $btn.data('app-id');
                var isEnabled = $btn.data('enabled') == '1';

                // Disable button during request
                $btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route("admin.marketplace-apps.toggle-status") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        app_id: appId
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Update button appearance
                            if (response.is_enabled) {
                                $btn.removeClass('btn-secondary').addClass('btn-outline-success');
                                $btn.find('i').removeClass('fa-times-circle').addClass('fa-check-circle');
                                $btn.html('<i class="fas fa-check-circle"></i> ' + mpi18n.enabled);
                                $btn.data('enabled', '1');
                            } else {
                                $btn.removeClass('btn-outline-success').addClass('btn-secondary');
                                $btn.find('i').removeClass('fa-check-circle').addClass('fa-times-circle');
                                $btn.html('<i class="fas fa-times-circle"></i> ' + mpi18n.disabled);
                                $btn.data('enabled', '0');
                            }

                            // Show success message with SweetAlert
                            swal({
                                title: response.is_enabled ? mpi18n.swalEnabledTitle : mpi18n.swalDisabledTitle,
                                text: response.message,
                                icon: 'success',
                                button: mpi18n.ok,
                                timer: 1500,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function(xhr) {
                        swal({
                            title: mpi18n.errorTitle,
                            text: mpi18n.errorGeneric,
                            icon: 'error',
                            button: mpi18n.ok,
                            timer: 1500,
                            timerProgressBar: true
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
