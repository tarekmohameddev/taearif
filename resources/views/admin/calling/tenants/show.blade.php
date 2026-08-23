@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Calling') }} — {{ $tenant->username }}</h4>
    <ul class="breadcrumbs">
        <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="{{ route('admin.calling.tenants.index') }}">{{ __('Calling') }}</a></li>
        <li class="separator"><i class="flaticon-right-arrow"></i></li>
        <li class="nav-item"><a href="#">{{ $tenant->username }}</a></li>
    </ul>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<div class="row">
    {{-- Settings --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Calling settings') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    {{ $tenant->company_name ?: trim(($tenant->first_name ?? '') . ' ' . ($tenant->last_name ?? '')) ?: $tenant->username }} — {{ $tenant->email }}
                </p>
                <form method="POST" action="{{ route('admin.calling.tenants.settings.update', $tenant->id) }}">
                    @csrf
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1"
                                   {{ old('enabled', $settings->enabled) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">{{ __('Enable calling for this tenant') }}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="record_by_default" name="record_by_default" value="1"
                                   {{ old('record_by_default', $settings->record_by_default) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="record_by_default">{{ __('Record calls by default') }}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="play_recording_announcement" name="play_recording_announcement" value="1"
                                   {{ old('play_recording_announcement', $settings->play_recording_announcement) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="play_recording_announcement">{{ __('Play recording announcement') }}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="max_channels">{{ __('Max concurrent channels') }}</label>
                        <input type="number" class="form-control" id="max_channels" name="max_channels"
                               min="1" max="50" value="{{ old('max_channels', $settings->max_channels) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Save settings') }}</button>
                </form>
            </div>
        </div>

        {{-- Extensions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Agent extensions') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('SIP') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($extensions as $ext)
                                <tr>
                                    <td>
                                        {{ trim(($ext->user->first_name ?? '') . ' ' . ($ext->user->last_name ?? '')) ?: ($ext->user->username ?? '-') }}
                                        <div class="text-muted small">{{ $ext->user->email ?? '' }}</div>
                                    </td>
                                    <td><code>{{ $ext->sip_username }}</code></td>
                                    <td>
                                        @if($ext->is_active)
                                            <span class="badge badge-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($ext->is_active)
                                            <form method="POST"
                                                  action="{{ route('admin.calling.tenants.extensions.destroy', [$tenant->id, $ext->id]) }}"
                                                  onsubmit="return confirm('{{ __('Deactivate this extension?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-xs">{{ __('Deactivate') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">{{ __('No extensions yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        {{-- Trunks --}}
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">{{ __('Trunks') }}</h5>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createTrunkModal">
                            <i class="fas fa-plus"></i> {{ __('Add trunk') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Ownership') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Numbers') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trunks as $trunk)
                                <tr>
                                    <td>{{ $trunk->name }}</td>
                                    <td>
                                        @if($trunk->type === 'yeastar_gsm')
                                            <span class="badge badge-info">Yeastar GSM</span>
                                        @else
                                            <span class="badge badge-primary">STC SIP</span>
                                        @endif
                                    </td>
                                    <td>{{ $trunk->ownership }}</td>
                                    <td>
                                        <span class="badge badge-{{ $trunk->status === 'registered' ? 'success' : 'warning' }}">
                                            {{ $trunk->status }}
                                        </span>
                                    </td>
                                    <td>{{ $trunk->sim_lines_count }}</td>
                                    <td>
                                        @if($trunk->type === 'yeastar_gsm')
                                            <button type="button" class="btn btn-success btn-sm"
                                                    data-toggle="modal" data-target="#addGsmNumberModal"
                                                    data-trunk-id="{{ $trunk->id }}"
                                                    data-trunk-name="{{ $trunk->name }}">
                                                <i class="fas fa-sim-card"></i> {{ __('Add number') }}
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-sm"
                                                    data-toggle="modal" data-target="#addStcNumberModal"
                                                    data-trunk-id="{{ $trunk->id }}"
                                                    data-trunk-name="{{ $trunk->name }}">
                                                <i class="fas fa-phone"></i> {{ __('Add number') }}
                                            </button>
                                        @endif
                                        <form method="POST" class="d-inline"
                                              action="{{ route('admin.calling.trunks.destroy', [$tenant->id, $trunk->id]) }}"
                                              onsubmit="return confirm('{{ __('Remove this trunk and deactivate its numbers?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('Delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        {{ __('No trunks yet. Create a trunk before adding phone numbers.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Phone numbers --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Phone numbers (SIM lines)') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Label') }}</th>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Trunk') }}</th>
                                <th>{{ __('Port') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($simLines as $line)
                                <tr>
                                    <td>{{ $line->label }}</td>
                                    <td><code>{{ $line->msisdn }}</code></td>
                                    <td>{{ $line->trunk->name ?? '-' }}</td>
                                    <td>{{ $line->port_index ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $line->is_active ? 'success' : 'secondary' }}">
                                            {{ $line->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm"
                                                data-toggle="modal" data-target="#editSimLineModal"
                                                data-id="{{ $line->id }}"
                                                data-label="{{ $line->label }}"
                                                data-msisdn="{{ $line->msisdn }}"
                                                data-user-id="{{ $line->user_id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline"
                                              action="{{ route('admin.calling.sim-lines.toggle', $line->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-{{ $line->is_active ? 'warning' : 'success' }} btn-sm"
                                                    title="{{ $line->is_active ? __('Deactivate') : __('Activate') }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        {{ __('No phone numbers yet. Add a number from a trunk above.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create trunk modal --}}
<div class="modal fade" id="createTrunkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('admin.calling.trunks.store', $tenant->id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add trunk') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" required maxlength="100"
                               placeholder="e.g. Yeastar Office / STC Main">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Type') }}</label>
                        <select name="type" class="form-control" required>
                            <option value="yeastar_gsm">Yeastar GSM</option>
                            <option value="stc_sip">STC SIP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Ownership') }}</label>
                        <select name="ownership" class="form-control" required>
                            <option value="customer_owned">{{ __('Customer owned') }}</option>
                            <option value="company_hosted">{{ __('Company hosted') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Registration mode') }}</label>
                        <select name="registration_mode" class="form-control">
                            <option value="register">register (Yeastar)</option>
                            <option value="ip_auth">ip_auth (STC)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Add Yeastar GSM number --}}
<div class="modal fade" id="addGsmNumberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="addGsmNumberForm" action="#">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add GSM number') }} — <span id="gsmTrunkName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Port index') }} (1–8)</label>
                        <input type="number" name="port_index" class="form-control" min="1" max="8" required value="1">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Phone number (MSISDN)') }}</label>
                        <input type="text" name="msisdn" class="form-control" required
                               placeholder="05XXXXXXXX or +9665XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Label') }} ({{ __('optional') }})</label>
                        <input type="text" name="label" class="form-control" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Dedicated agent') }} ({{ __('optional') }})</label>
                        <select name="user_id" class="form-control">
                            <option value="">{{ __('Shared (any agent)') }}</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">
                                    {{ trim(($agent->first_name ?? '') . ' ' . ($agent->last_name ?? '')) ?: ($agent->username ?? '-') }} ({{ $agent->account_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-muted small mb-0">
                        {{ __('After provisioning, configure the Yeastar device with the SIP endpoint and password shown in the success message.') }}
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Provision number') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Add STC number --}}
<div class="modal fade" id="addStcNumberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="addStcNumberForm" action="#">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add STC number') }} — <span id="stcTrunkName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Phone number (MSISDN)') }}</label>
                        <input type="text" name="msisdn" class="form-control" required
                               placeholder="05XXXXXXXX or +9665XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>{{ __('STC host') }}</label>
                        <input type="text" name="stc_host" class="form-control" required
                               placeholder="e.g. sip.stc.com.sa">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Label') }} ({{ __('optional') }})</label>
                        <input type="text" name="label" class="form-control" maxlength="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Provision number') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit SIM line --}}
<div class="modal fade" id="editSimLineModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="editSimLineForm" action="#">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit phone number') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('Label') }}</label>
                        <input type="text" name="label" id="editLabel" class="form-control" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Phone number (MSISDN)') }}</label>
                        <input type="text" name="msisdn" id="editMsisdn" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Dedicated agent') }}</label>
                        <select name="user_id" id="editUserId" class="form-control">
                            <option value="">{{ __('Shared (any agent)') }}</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">
                                    {{ trim(($agent->first_name ?? '') . ' ' . ($agent->last_name ?? '')) ?: ($agent->username ?? '-') }} ({{ $agent->account_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var gsmUrlTpl = @json(route('admin.calling.trunks.provision-gsm', [$tenant->id, '__TRUNK__']));
    var stcUrlTpl = @json(route('admin.calling.trunks.provision-stc', [$tenant->id, '__TRUNK__']));
    var editUrlTpl = @json(route('admin.calling.sim-lines.update', '__ID__'));

    $('#addGsmNumberModal').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#gsmTrunkName').text(btn.data('trunk-name'));
        $('#addGsmNumberForm').attr('action', gsmUrlTpl.replace('__TRUNK__', btn.data('trunk-id')));
    });

    $('#addStcNumberModal').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#stcTrunkName').text(btn.data('trunk-name'));
        $('#addStcNumberForm').attr('action', stcUrlTpl.replace('__TRUNK__', btn.data('trunk-id')));
    });

    $('#editSimLineModal').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#editLabel').val(btn.data('label'));
        $('#editMsisdn').val(btn.data('msisdn'));
        $('#editUserId').val(btn.data('user-id') || '');
        $('#editSimLineForm').attr('action', editUrlTpl.replace('__ID__', btn.data('id')));
    });
})();
</script>
@endsection
