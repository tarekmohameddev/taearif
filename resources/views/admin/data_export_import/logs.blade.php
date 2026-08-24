@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('سجل التصدير والاستيراد') }} / {{ __('Export / Import Log') }}</h4>
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
            <a href="{{ route('admin.data-export-import.index') }}">{{ __('تصدير واستيراد البيانات') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{ __('السجل') }} / {{ __('Log') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">
                            {{ __('سجل عمليات التصدير والاستيراد') }}
                        </div>
                    </div>
                    <div class="col-lg-8 mt-2 mt-lg-0">
                        <div class="d-flex justify-content-lg-end">
                            <a href="{{ route('admin.data-export-import.index') }}" class="btn btn-sm btn-secondary">
                                <i data-lucide="arrow-left"></i>
                                {{ __('العودة') }} / {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                </div>
                <form action="{{ route('admin.data-export-import.logs') }}" method="GET" class="mt-3">
                    <div class="row">
                        <div class="col-lg-5 col-md-6 mb-2">
                            <input type="text" name="term" class="form-control"
                                value="{{ $term }}"
                                placeholder="{{ __('Search by tenant / admin / email / phone') }}">
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <select name="operation" class="form-control">
                                <option value="">{{ __('كل العمليات') }} / {{ __('All operations') }}</option>
                                <option value="export" @selected($operation === 'export')>{{ __('تصدير') }} / {{ __('Export') }}</option>
                                <option value="import" @selected($operation === 'import')>{{ __('استيراد') }} / {{ __('Import') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-2">
                            <select name="status" class="form-control">
                                <option value="">{{ __('كل الحالات') }} / {{ __('All statuses') }}</option>
                                <option value="success" @selected($status === 'success')>{{ __('Success') }}</option>
                                <option value="partial" @selected($status === 'partial')>{{ __('Partial') }}</option>
                                <option value="failed" @selected($status === 'failed')>{{ __('Failed') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i data-lucide="search"></i>
                                {{ __('تصفية') }} / {{ __('Filter') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                @if ($logs->isEmpty())
                    <h5 class="text-center py-4">{{ __('لا توجد سجلات') }} / {{ __('No log entries found') }}</h5>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped mt-3">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('التاريخ') }} / {{ __('Date') }}</th>
                                    <th scope="col">{{ __('المشرف') }} / {{ __('Admin') }}</th>
                                    <th scope="col">{{ __('العملية') }} / {{ __('Operation') }}</th>
                                    <th scope="col">{{ __('الحساب المتأثر') }} / {{ __('Tenant') }}</th>
                                    <th scope="col">{{ __('الحالة') }} / {{ __('Status') }}</th>
                                    <th scope="col">{{ __('النتائج') }} / {{ __('Results') }}</th>
                                    <th scope="col">{{ __('IP') }}</th>
                                    <th scope="col">{{ __('تفاصيل') }} / {{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    @php
                                        $adminName = $log->admin
                                            ? (trim($log->admin->full_name) ?: $log->admin->username ?: $log->admin->email)
                                            : __('System / Deleted');
                                        $tenantName = $log->user?->basic_setting?->company_name
                                            ?? $log->user?->username
                                            ?? $log->affected_username
                                            ?? '—';
                                        $statusClass = match ($log->status) {
                                            'success' => 'badge-success',
                                            'partial' => 'badge-warning',
                                            'failed'  => 'badge-danger',
                                            default   => 'badge-secondary',
                                        };
                                        $opClass = $log->operation === 'export' ? 'badge-info' : 'badge-primary';
                                        $sheets = data_get($log->metadata, 'sheets', []);
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ optional($log->created_at)->format('Y-m-d H:i:s') ?? '—' }}</td>
                                        <td>
                                            {{ $adminName }}
                                            @if ($log->admin?->email)
                                                <br><small class="text-muted">{{ $log->admin->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $opClass }}">
                                                {{ $log->operation === 'export' ? __('تصدير') : __('استيراد') }}
                                            </span>
                                            @if ($log->operation === 'import' && $log->update_existing)
                                                <br><small class="text-muted">{{ __('تحديث الموجود') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $tenantName }}
                                            @if ($log->user_id)
                                                <br><small class="text-muted">#{{ $log->user_id }}</small>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $statusClass }}">{{ ucfirst($log->status) }}</span></td>
                                        <td class="text-nowrap">
                                            @if ($log->operation === 'import')
                                                <span class="text-success">+{{ $log->imported_count }}</span> /
                                                <span class="text-primary">~{{ $log->updated_count }}</span> /
                                                <span class="text-muted">⊘{{ $log->skipped_count }}</span>
                                                <br><small class="text-muted">{{ __('جديد / محدّث / متخطى') }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $log->ip_address ?? '—' }}</td>
                                        <td>
                                            @if ($log->message || !empty($sheets) || $log->file_name)
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-toggle="collapse" data-target="#log-detail-{{ $log->id }}">
                                                    <i data-lucide="eye"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($log->message || !empty($sheets) || $log->file_name)
                                        <tr class="collapse" id="log-detail-{{ $log->id }}">
                                            <td colspan="8" class="bg-light">
                                                @if ($log->file_name)
                                                    <div class="mb-2">
                                                        <strong>{{ __('الملف') }} / {{ __('File') }}:</strong>
                                                        {{ $log->file_name }}
                                                    </div>
                                                @endif
                                                @if ($log->message)
                                                    <div class="mb-2">
                                                        <strong>{{ __('ملاحظة') }} / {{ __('Message') }}:</strong>
                                                        {{ $log->message }}
                                                    </div>
                                                @endif
                                                @if (!empty($sheets))
                                                    <table class="table table-sm table-bordered mb-0 bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Sheet') }}</th>
                                                                <th>{{ __('Imported') }}</th>
                                                                <th>{{ __('Updated') }}</th>
                                                                <th>{{ __('Skipped') }}</th>
                                                                <th>{{ __('Errors') }}</th>
                                                                <th>{{ __('Warnings') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($sheets as $sheetKey => $sheet)
                                                                @php
                                                                    $errors = $sheet['errors'] ?? [];
                                                                    $warnings = $sheet['warnings'] ?? [];
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ ucfirst(str_replace('_', ' ', $sheetKey)) }}</td>
                                                                    <td>{{ $sheet['imported'] ?? 0 }}</td>
                                                                    <td>{{ $sheet['updated'] ?? 0 }}</td>
                                                                    <td>{{ $sheet['skipped'] ?? 0 }}</td>
                                                                    <td>
                                                                        @forelse ($errors as $err)
                                                                            <div class="text-danger small">
                                                                                @if (!is_null(data_get($err, 'row'))){{ __('Row') }} {{ data_get($err, 'row') }}: @endif{{ data_get($err, 'message', is_string($err) ? $err : '') }}
                                                                            </div>
                                                                        @empty
                                                                            <span class="text-muted">—</span>
                                                                        @endforelse
                                                                    </td>
                                                                    <td>
                                                                        @forelse ($warnings as $warn)
                                                                            <div class="text-warning small">
                                                                                @if (!is_null(data_get($warn, 'row'))){{ __('Row') }} {{ data_get($warn, 'row') }}: @endif{{ data_get($warn, 'message', is_string($warn) ? $warn : '') }}
                                                                            </div>
                                                                        @empty
                                                                            <span class="text-muted">—</span>
                                                                        @endforelse
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="d-inline-block mx-auto">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
