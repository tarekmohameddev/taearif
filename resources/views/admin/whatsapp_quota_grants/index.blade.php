@extends('admin.layout')

@section('styles')
<style>
    .wa-quota-table th { white-space: nowrap; }
    .wa-quota-table td { vertical-align: middle; }
    .wa-quota-identity { min-width: 210px; }
    .wa-quota-identity strong, .wa-quota-identity small { display: block; }
    .wa-quota-identity small { color: #6c757d; margin-top: 2px; }
    .wa-quota-number { min-width: 64px; text-align: center; }
    .wa-quota-number strong { font-size: 1rem; }
    .wa-quota-search { max-width: 440px; width: 100%; }
    .wa-quota-reason { max-width: 320px; white-space: normal; }
    .wa-quota-actions { width: 1%; white-space: nowrap; }
    .wa-quota-status { min-width: 84px; }
    @media (max-width: 767.98px) {
        .wa-quota-search { max-width: none; }
        .wa-quota-search .form-control { min-width: 0; }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Quota Grants') }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('Credit Management') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ __('WhatsApp Quota Grants') }}</a></li>
    </ul>
    <div class="ml-auto wa-quota-search">
        <form method="GET" action="{{ route('admin.whatsapp-quota-grants.index') }}" class="input-group">
            <input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="{{ __('Tenant ID, email, or username') }}" aria-label="{{ __('Search tenants') }}">
            <div class="input-group-append">
                @if ($search !== '')
                    <a class="btn btn-light" href="{{ route('admin.whatsapp-quota-grants.index') }}" title="{{ __('Clear') }}"><i class="fas fa-times"></i></a>
                @endif
                <button class="btn btn-primary" type="submit" title="{{ __('Search') }}"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

@if (isset($errors) && $errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ __('Tenant Quotas') }}</h5>
                <span class="badge badge-light">{{ number_format($tenants->total()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 wa-quota-table">
                        <thead>
                            <tr>
                                <th>{{ __('Tenant') }}</th>
                                <th class="text-center">{{ __('Package') }}</th>
                                <th class="text-center">{{ __('WA Add-ons') }}</th>
                                <th class="text-center">{{ __('Employee Add-ons') }}</th>
                                <th class="text-center">{{ __('Usage') }}</th>
                                <th class="text-center">{{ __('Quota') }}</th>
                                <th class="text-center">{{ __('Remaining') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($tenants as $tenant)
                            @php($quota = $tenant->quota_breakdown)
                            <tr>
                                <td class="wa-quota-identity">
                                    <strong>#{{ $tenant->id }} - {{ $tenant->username ?: __('No username') }}</strong>
                                    <small>{{ $tenant->email }}</small>
                                </td>
                                <td class="wa-quota-number">{{ $quota['base'] }}</td>
                                <td class="wa-quota-number">{{ $quota['whatsapp_addons'] }}</td>
                                <td class="wa-quota-number">{{ $quota['employee_addons'] }}</td>
                                <td class="wa-quota-number">{{ $quota['usage'] }}</td>
                                <td class="wa-quota-number"><strong>{{ $quota['quota'] }}</strong></td>
                                <td class="wa-quota-number"><span class="badge {{ $quota['is_over_limit'] ? 'badge-danger' : 'badge-success' }}">{{ $quota['remaining'] }}</span></td>
                                <td class="text-right wa-quota-actions">
                                    <button type="button" class="btn btn-success btn-sm js-open-grant" data-toggle="modal" data-target="#grantQuotaModal"
                                        data-tenant-id="{{ $tenant->id }}"
                                        data-tenant-label="#{{ $tenant->id }} - {{ $tenant->username ?: $tenant->email }}"
                                        data-current-quota="{{ $quota['quota'] }}" title="{{ __('Grant quota') }}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">{{ __('No tenants found.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($tenants->hasPages())
                <div class="card-footer d-flex justify-content-end">{{ $tenants->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ __('Grant History') }}</h5>
                <span class="badge badge-light">{{ number_format($grants->total()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 wa-quota-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Tenant') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Expiration') }}</th>
                                <th>{{ __('Administrator') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($grants as $grant)
                            @php($audit = $grant->audits->sortByDesc('changed_at')->first())
                            <tr>
                                <td>{{ $grant->id }}</td>
                                <td class="wa-quota-identity">
                                    <strong>#{{ $grant->user_id }} - {{ optional($grant->user)->username ?: __('No username') }}</strong>
                                    <small>{{ optional($grant->user)->email }}</small>
                                </td>
                                <td><strong>+{{ $grant->qty }}</strong></td>
                                <td class="wa-quota-status">
                                    @if ($grant->status === \App\Models\WhatsappAddon::STATUS_APPROVED)
                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __('Revoked') }}</span>
                                    @endif
                                </td>
                                <td>{{ optional($grant->expire_date)->format('Y-m-d') ?: __('No expiration') }}</td>
                                <td>{{ optional(optional($audit)->admin)->username ?: __('System') }}</td>
                                <td class="wa-quota-reason">{{ optional($audit)->note ?: '-' }}</td>
                                <td class="text-right wa-quota-actions">
                                    @if ($grant->status === \App\Models\WhatsappAddon::STATUS_APPROVED)
                                        <button type="button" class="btn btn-danger btn-sm js-open-revoke" data-toggle="modal" data-target="#revokeQuotaModal"
                                            data-action="{{ route('admin.whatsapp-quota-grants.revoke', $grant) }}"
                                            data-grant-label="#{{ $grant->id }} - +{{ $grant->qty }}" title="{{ __('Revoke') }}">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4">{{ __('No manual quota grants found.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($grants->hasPages())
                <div class="card-footer d-flex justify-content-end">{{ $grants->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="grantQuotaModal" tabindex="-1" role="dialog" aria-labelledby="grantQuotaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.whatsapp-quota-grants.store') }}">
                @csrf
                <input type="hidden" name="tenant_id" id="grantTenantId">
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="grantQuotaModalLabel">{{ __('Grant WhatsApp Quota') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Tenant') }}</label>
                        <input class="form-control" id="grantTenantLabel" type="text" readonly>
                    </div>
                    <div class="form-group">
                        <label for="grantQuantity">{{ __('Additional numbers') }}</label>
                        <input class="form-control" id="grantQuantity" type="number" name="quantity" min="1" max="100" required>
                        <small class="form-text text-muted"><span id="grantCurrentQuota">0</span> to <strong id="grantNewQuota">0</strong></small>
                    </div>
                    <div class="form-group">
                        <label for="grantExpiresAt">{{ __('Expiration') }}</label>
                        <input class="form-control" id="grantExpiresAt" type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}">
                    </div>
                    <div class="form-group mb-0">
                        <label for="grantReason">{{ __('Reason') }}</label>
                        <textarea class="form-control" id="grantReason" name="reason" rows="3" maxlength="1000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> {{ __('Grant quota') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="revokeQuotaModal" tabindex="-1" role="dialog" aria-labelledby="revokeQuotaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="revokeQuotaForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="revokeQuotaModalLabel">{{ __('Revoke WhatsApp Quota') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Grant') }}</label>
                        <input class="form-control" id="revokeGrantLabel" type="text" readonly>
                    </div>
                    <div class="form-group mb-0">
                        <label for="revokeReason">{{ __('Reason') }}</label>
                        <textarea class="form-control" id="revokeReason" name="reason" rows="3" maxlength="1000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> {{ __('Revoke') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var currentQuota = 0;

        $('.js-open-grant').on('click', function () {
            currentQuota = Number($(this).data('current-quota')) || 0;
            $('#grantTenantId').val($(this).data('tenant-id'));
            $('#grantTenantLabel').val($(this).data('tenant-label'));
            $('#grantCurrentQuota, #grantNewQuota').text(currentQuota);
            $('#grantQuantity, #grantExpiresAt, #grantReason').val('');
        });

        $('#grantQuantity').on('input', function () {
            $('#grantNewQuota').text(currentQuota + (Number($(this).val()) || 0));
        });

        $('.js-open-revoke').on('click', function () {
            $('#revokeQuotaForm').attr('action', $(this).data('action'));
            $('#revokeGrantLabel').val($(this).data('grant-label'));
            $('#revokeReason').val('');
        });
    });
</script>
@endsection
