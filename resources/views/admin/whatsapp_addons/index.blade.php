@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp add-on requests') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Credit Management') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('WhatsApp Add-ons') }}</a></li>
    </ul>
    @php
        $statusLabels = [
            'pending' => __('Pending'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
        ];
    @endphp
    <div class="ml-auto">
        <form class="form-inline" method="GET" action="{{ route('admin.whatsapp-addons.index') }}">
            <div class="form-group mr-2">
                <label class="mr-2 mb-0">{{ __('Status') }}</label>
                <select name="status" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>
                            {{ $statusLabels[$option] ?? $option }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Requests list') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('WhatsApp Number') }}</th>
                                <th>{{ __('Number status') }}</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Package') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created At') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($addons as $addon)
                                <tr>
                                    <td>{{ $addon->id }}</td>
                                    <td>{{ $addon->whatsapp_number ?? '-' }}</td>
                                    <td>
                                        @if($addon->whatsapp_status)
                                            @if($addon->whatsapp_status === 'active')
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td>{{ $addon->tenant_username ?? '-' }}</td>
                                    <td>{{ $addon->tenant_email ?? '-' }}</td>
                                    <td>{{ $addon->plan_name ?? '-' }}</td>
                                    <td>{{ $addon->qty }}</td>
                                    <td>{{ number_format((float) $addon->amount, 2) }}</td>
                                    <td>
                                        @php $label = $statusLabels[$addon->status] ?? $addon->status; @endphp
                                        @switch($addon->status)
                                            @case('approved')
                                                <span class="badge badge-success">{{ $label }}</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge badge-danger">{{ $label }}</span>
                                                @break
                                            @default
                                                <span class="badge badge-warning">{{ $label }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ optional($addon->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDetails({{ $addon->id }})" title="{{ __('Details') }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($addon->status === 'pending')
                                                <button type="button" class="btn btn-success btn-sm" onclick="approveAddon({{ $addon->id }})" title="{{ __('Approve') }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm" onclick="rejectAddon({{ $addon->id }})" title="{{ __('Reject') }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteAddon({{ $addon->id }})" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">{{ __('No requests yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($addons->hasPages())
                <div class="card-footer">
                    {{ $addons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">{{ __('Add-on request details') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@php
    $addonStatusLabels = [
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected'),
    ];
@endphp
<script>
    const addonStatusLabels = @json($addonStatusLabels);
    const addonI18n = {
        loading: @json(__('Loading...')),
        loadFailed: @json(__('Failed to load details')),
        requestDetails: @json(__('Request details')),
        requestId: @json(__('Request ID')),
        whatsappNumber: @json(__('WhatsApp Number')),
        numberName: @json(__('Number name')),
        numberStatus: @json(__('Number status')),
        package: @json(__('Package')),
        quantity: @json(__('Quantity')),
        amount: @json(__('Amount')),
        status: @json(__('Status')),
        tenantInformation: @json(__('Tenant information')),
        username: @json(__('Username')),
        email: @json(__('Email')),
        changeHistory: @json(__('Change history')),
        changedFrom: @json(__('Changed from')),
        to: @json(__('to')),
        by: @json(__('by')),
        noChanges: @json(__('No changes yet.')),
        active: @json(__('Active')),
        inactive: @json(__('Inactive')),
        confirmApprove: @json(__('Are you sure you want to approve this request?')),
        confirmReject: @json(__('Are you sure you want to reject this request?')),
        confirmDelete: @json(__('Are you sure you want to delete this request? This action cannot be undone.')),
        operationFailed: @json(__('Operation failed.')),
        operationError: @json(__('An error occurred while executing the operation.')),
    };

    function addonStatusBadge(status) {
        const cls = { pending: 'badge-warning', approved: 'badge-success', rejected: 'badge-danger' };
        const c = cls[status] || 'badge-secondary';
        const t = addonStatusLabels[status] || status;
        return '<span class="badge ' + c + '">' + t + '</span>';
    }

    function waNumberStatusBadge(isActive) {
        return isActive === 'active'
            ? '<span class="badge badge-success">' + addonI18n.active + '</span>'
            : '<span class="badge badge-secondary">' + addonI18n.inactive + '</span>';
    }

    function viewDetails(addonId) {
        $('#detailModal').modal('show');
        $('#detailModalBody').html('<div class="text-center py-4"><div class="spinner-border" role="status"><span class="sr-only">' + addonI18n.loading + '</span></div></div>');

        $.ajax({
            url: '{{ route("admin.whatsapp-addons.show", ":id") }}'.replace(':id', addonId),
            method: 'GET',
            success: function(response) {
                if(response.success) {
                    const addon = response.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">${addonI18n.requestDetails}</h6>
                                <table class="table table-sm">
                                    <tr><th>${addonI18n.requestId}:</th><td>${addon.id}</td></tr>
                                    <tr><th>${addonI18n.whatsappNumber}:</th><td>${addon.whatsapp_user?.number || '-'}</td></tr>
                                    <tr><th>${addonI18n.numberName}:</th><td>${addon.whatsapp_user?.name || '-'}</td></tr>
                                    <tr><th>${addonI18n.numberStatus}:</th><td>${waNumberStatusBadge(addon.whatsapp_user?.status)}</td></tr>
                                    <tr><th>${addonI18n.package}:</th><td>${addon.plan?.name || '-'}</td></tr>
                                    <tr><th>${addonI18n.quantity}:</th><td>${addon.qty}</td></tr>
                                    <tr><th>${addonI18n.amount}:</th><td>${addon.amount}</td></tr>
                                    <tr><th>${addonI18n.status}:</th><td>${addonStatusBadge(addon.status)}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">${addonI18n.tenantInformation}</h6>
                                <table class="table table-sm">
                                    <tr><th>${addonI18n.username}:</th><td>${addon.whatsapp_user?.user?.username || '-'}</td></tr>
                                    <tr><th>${addonI18n.email}:</th><td>${addon.whatsapp_user?.user?.email || '-'}</td></tr>
                                </table>

                                <h6 class="mb-3 mt-4">${addonI18n.changeHistory}</h6>
                                <div class="audit-history" style="max-height: 200px; overflow-y: auto;">
                    `;

                    if(addon.audits && addon.audits.length > 0) {
                        html += '<ul class="list-unstyled">';
                        addon.audits.forEach(function(audit) {
                            html += `<li class="mb-2"><small>
                                <strong>${audit.changed_at}</strong><br>
                                ${addonI18n.changedFrom} <span class="badge badge-secondary">${audit.old_status || '-'}</span>
                                ${addonI18n.to} ${addonStatusBadge(audit.new_status)}
                                ${audit.admin ? ' ' + addonI18n.by + ' ' + audit.admin.username : ''}
                                ${audit.note ? '<br><em>' + audit.note + '</em>' : ''}
                            </small></li>`;
                        });
                        html += '</ul>';
                    } else {
                        html += '<p class="text-muted">' + addonI18n.noChanges + '</p>';
                    }

                    html += `
                                </div>
                            </div>
                        </div>
                    `;

                    $('#detailModalBody').html(html);
                }
            },
            error: function() {
                $('#detailModalBody').html('<div class="alert alert-danger">' + addonI18n.loadFailed + '</div>');
            }
        });
    }

    function approveAddon(addonId) {
        if(!confirm(addonI18n.confirmApprove)) return;

        $.ajax({
            url: '{{ route("admin.whatsapp-addons.approve", ":id") }}'.replace(':id', addonId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || addonI18n.operationFailed);
                }
            },
            error: function() {
                alert(addonI18n.operationError);
            }
        });
    }

    function rejectAddon(addonId) {
        if(!confirm(addonI18n.confirmReject)) return;

        $.ajax({
            url: '{{ route("admin.whatsapp-addons.reject", ":id") }}'.replace(':id', addonId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || addonI18n.operationFailed);
                }
            },
            error: function() {
                alert(addonI18n.operationError);
            }
        });
    }

    function deleteAddon(addonId) {
        if(!confirm(addonI18n.confirmDelete)) return;

        $.ajax({
            url: '{{ route("admin.whatsapp-addons.destroy", ":id") }}'.replace(':id', addonId),
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message || addonI18n.operationFailed);
                }
            },
            error: function() {
                alert(addonI18n.operationError);
            }
        });
    }
</script>
@endsection
